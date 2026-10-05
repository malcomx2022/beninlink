import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Link } from 'expo-router';

import { ApiError } from '../../src/api/client';
import { fetchCustomsAlerts } from '../../src/api/customs';
import { fetchBalanceDetails, fetchDashboard } from '../../src/api/merchant';
import { fetchUnreadCount } from '../../src/api/notifications';
import type { BalanceDetails, DashboardData } from '../../src/api/types';
import { useSession } from '../../src/session/SessionProvider';
import { Card, CountTile, ErrorText, Muted, StatTile, Title } from '../../src/components/ui';
import {
  CustomsAlertStatus,
  customsLevelColorName,
  highestCustomsLevel,
} from '../../src/domain/customsLevel';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { formatAmount } from '../../src/domain/money';
import { t } from '../../src/i18n';

export default function DashboardScreen() {
  const { user } = useSession();
  const [dashboard, setDashboard] = useState<DashboardData | null>(null);
  const [balance, setBalance] = useState<BalanceDetails | null>(null);
  const [unread, setUnread] = useState(0);
  /**
   * Alertes douanières EN COURS — `mobile/CLAUDE.md` les liste au tableau de
   * bord, et seul un lien y menait.
   *
   * On compte la première page : au-delà on affiche « 20+ » plutôt qu'un
   * chiffre faux. Il n'existe pas d'endpoint de comptage, et on n'en invente
   * pas — règle d'or, `web/` est le contrat.
   */
  const [customsPending, setCustomsPending] = useState(0);
  const [customsLevel, setCustomsLevel] = useState(0);
  const [customsCapped, setCustomsCapped] = useState(false);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      // Les quatre appels sont indépendants : en parallèle. Les deux derniers
      // sont des conforts — leur échec n'empêche pas le tableau de s'afficher.
      const [d, b, n, alerts] = await Promise.all([
        fetchDashboard(),
        fetchBalanceDetails(),
        fetchUnreadCount().catch(() => 0),
        fetchCustomsAlerts(CustomsAlertStatus.PENDING, 1).catch(() => ({ items: [], hasMore: false })),
      ]);
      setDashboard(d);
      setBalance(b);
      setUnread(n);
      setCustomsPending(alerts.items.length);
      setCustomsLevel(highestCustomsLevel(alerts.items));
      // « 20+ » : la première page est pleine et le serveur dit qu'il en reste (S78).
      setCustomsCapped(alerts.hasMore);
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
        <Link href="/(app)/notifications" style={styles.link}>
          {t('notifications.title')}
          {unread > 0 ? ` (${unread})` : ''}
        </Link>
        <Link href="/(app)/parcels" style={styles.link}>
          {t('parcels.title')}
        </Link>
        <Link href="/(app)/wallet" style={styles.link}>
          {t('wallet.title')}
        </Link>
        <Link href="/(app)/invoices" style={styles.link}>
          {t('invoices.title')}
        </Link>
        {/* Le nombre ET la gravité : un blocage à la frontière se lit en rouge
            sans ouvrir l'écran. La couleur vient de la même table que la liste
            des alertes (`src/domain/customsLevel.ts`). */}
        <Link
          href="/(app)/customs"
          style={[
            styles.link,
            customsPending > 0 && { color: colors[customsLevelColorName(customsLevel)] },
          ]}
        >
          {t('customs.title')}
          {customsPending > 0 ? ` (${customsPending}${customsCapped ? '+' : ''})` : ''}
        </Link>
        <Link href="/(app)/shops" style={styles.link}>
          {t('shops.title')}
        </Link>
        <Link href="/(app)/rates" style={styles.link}>
          {t('rates.title')}
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
