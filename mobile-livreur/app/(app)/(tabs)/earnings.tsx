import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../../src/api/client';
import { fetchIncomeExpense, fetchParcelPaymentLogs, fetchProfile } from '../../../src/api/deliveryman';
import type { IncomeExpense, ParcelPaymentLog, ProfileData } from '../../../src/api/types';
import { Card, ErrorText, Muted, StatTile, Title } from '../../../src/components/ui';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../../src/theme/typography';
import { formatAmount } from '../../../src/domain/money';
import { t } from '../../../src/i18n';

export default function EarningsScreen() {
  const [profile, setProfile] = useState<ProfileData | null>(null);
  const [income, setIncome] = useState<IncomeExpense[]>([]);
  const [expense, setExpense] = useState<IncomeExpense[]>([]);
  const [logs, setLogs] = useState<ParcelPaymentLog[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      // Les trois appels sont indépendants : en parallèle.
      const [p, ie, l] = await Promise.all([fetchProfile(), fetchIncomeExpense(), fetchParcelPaymentLogs()]);
      setProfile(p);
      setIncome(ie.income);
      setExpense(ie.expense);
      setLogs(l);
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
      <ErrorText>{error}</ErrorText>

      {/* Les trois chiffres viennent de deliveryman/profile, calculés côté serveur. */}
      <View style={styles.tiles}>
        <StatTile label={t('earnings.balance')} value={profile?.current_balance} highlight />
        <StatTile label={t('earnings.earned')} value={profile?.deliveryman_earn} />
        <StatTile label={t('earnings.totalCod')} value={profile?.total_cod} />
      </View>

      <Card>
        <Title>{t('earnings.collections')}</Title>
        {logs.length === 0 && <Muted>{t('earnings.empty')}</Muted>}
        {logs.map((log) => (
          <Line key={log.id} label={log.date ?? log.created_at ?? ''} note={log.note} value={formatAmount(log.amount)} />
        ))}
      </Card>

      <Card>
        <Title>{t('earnings.income')}</Title>
        {income.length === 0 && <Muted>{t('earnings.empty')}</Muted>}
        {income.map((row) => (
          <Line key={row.id} label={row.date} note={row.note} value={formatAmount(row.amount)} />
        ))}
      </Card>

      <Card>
        <Title>{t('earnings.expense')}</Title>
        {expense.length === 0 && <Muted>{t('earnings.empty')}</Muted>}
        {expense.map((row) => (
          <Line key={row.id} label={row.date} note={row.note} value={formatAmount(row.amount)} />
        ))}
      </Card>
    </ScrollView>
  );
}

function Line({ label, note, value }: { label: string; note: string | null; value: string }) {
  return (
    <View style={styles.line}>
      <View style={styles.lineText}>
        <Text style={styles.lineLabel}>{label}</Text>
        {!!note && <Muted>{note}</Muted>}
      </View>
      <Text style={styles.lineValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  tiles: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  line: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md, alignItems: 'center' },
  lineText: { flexShrink: 1, gap: 2 },
  lineLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  lineValue: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.text },
});
