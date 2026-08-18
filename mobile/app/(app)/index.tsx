import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Link } from 'expo-router';

import { ApiError } from '../../src/api/client';
import { fetchBalanceDetails, fetchDashboard } from '../../src/api/merchant';
import type { BalanceDetails, DashboardData } from '../../src/api/types';
import { useSession } from '../../src/session/SessionProvider';
import { Card, CountTile, ErrorText, Muted, StatTile, Title } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { formatAmount } from '../../src/domain/money';
import { t } from '../../src/i18n';

export default function DashboardScreen() {
  const { user } = useSession();
  const [dashboard, setDashboard] = useState<DashboardData | null>(null);
  const [balance, setBalance] = useState<BalanceDetails | null>(null);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      // Les deux appels sont indépendants : en parallèle.
      const [d, b] = await Promise.all([fetchDashboard(), fetchBalanceDetails()]);
      setDashboard(d);
      setBalance(b);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  return (
    <ScrollView
      contentContainerStyle={styles.page}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <View>
        <Text style={styles.hello}>{user?.merchant?.business_name ?? user?.name ?? ''}</Text>
        <Muted>
          {t('auth.merchantId')} : {user?.merchant?.merchant_unique_id ?? '—'}
        </Muted>
      </View>

      <ErrorText>{error}</ErrorText>

      {/* Relevé de règlement : le net à reverser est le chiffre qui compte
          pour le marchand, d'où la mise en avant en ocre. */}
      <Card>
        <Title>{t('invoices.settlementStatement')}</Title>
        <Text style={styles.net}>{formatAmount(balance?.available_balance)}</Text>
        <Muted>{t('dashboard.balanceToSettle')}</Muted>
        <View style={styles.breakdown}>
          <Row label={t('invoices.codCollected')} value={balance?.amount_delivered} />
          <Row label={t('invoices.fees')} value={balance?.payable_delivery_charge} negative />
          <Row label={t('invoices.vat')} value={balance?.vat_amount} negative />
        </View>
        <Muted>
          {t('dashboard.clearableParcels')} : {balance?.clearable_parcels ?? 0}
        </Muted>
      </Card>

      <View style={styles.tiles}>
        <CountTile label={t('dashboard.totalParcels')} value={dashboard?.t_parcel ?? 0} />
        <CountTile label={t('parcelStage.delivered')} value={dashboard?.t_delivered ?? 0} />
        <CountTile label={t('parcelStage.returned')} value={dashboard?.t_return ?? 0} />
        <CountTile label={t('shops.title')} value={dashboard?.t_shop ?? 0} />
        <StatTile label={t('dashboard.totalSales')} value={dashboard?.t_sale} />
        <StatTile label={t('invoices.fees')} value={dashboard?.t_delivery_fee} />
      </View>

      <View style={styles.nav}>
        <Link href="/(app)/parcels" style={styles.link}>
          {t('parcels.title')}
        </Link>
        <Link href="/(app)/wallet" style={styles.link}>
          {t('wallet.title')}
        </Link>
        <Link href="/(app)/shops" style={styles.link}>
          {t('shops.title')}
        </Link>
        <Link href="/(app)/profile" style={styles.link}>
          {t('profile.title')}
        </Link>
      </View>
    </ScrollView>
  );
}

function Row({ label, value, negative }: { label: string; value: unknown; negative?: boolean }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>
        {negative ? '− ' : ''}
        {formatAmount(value)}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  hello: { fontFamily: fonts.headingBold, fontSize: fontSizes.xl, color: colors.primary },
  net: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
  breakdown: { gap: spacing.xs, marginTop: spacing.sm },
  row: { flexDirection: 'row', justifyContent: 'space-between' },
  rowLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  rowValue: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.text },
  tiles: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  nav: { gap: spacing.xs },
  link: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    textAlign: 'center',
    paddingVertical: spacing.sm,
  },
});
