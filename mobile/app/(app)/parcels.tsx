import { useCallback, useEffect, useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { useRouter } from 'expo-router';

import { ApiError } from '../../src/api/client';
import { fetchParcels } from '../../src/api/parcels';
import type { Parcel } from '../../src/api/types';
import { ErrorText, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../src/theme/typography';
import { formatAmount } from '../../src/domain/money';
import { TAB_STAGES, toMerchantStage } from '../../src/domain/parcelStatus';
import { t } from '../../src/i18n';

type TabKey = keyof typeof TAB_STAGES;

const TABS: { key: TabKey; label: string }[] = [
  { key: 'ongoing', label: t('parcels.tabOngoing') },
  { key: 'delivered', label: t('parcels.tabDelivered') },
  { key: 'returns', label: t('parcels.tabReturns') },
];

export default function ParcelsScreen() {
  const router = useRouter();
  const [parcels, setParcels] = useState<Parcel[]>([]);
  const [tab, setTab] = useState<TabKey>('ongoing');
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      setParcels(await fetchParcels());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  /**
   * Le backend ne fournit pas ce regroupement : il expose 10 statuts marchand
   * (et 33 codes au total). La répartition en onglets vient de
   * domain/parcelStatus.
   */
  const visible = useMemo(() => {
    const stages = TAB_STAGES[tab];
    return parcels.filter((p) => stages.includes(toMerchantStage(p.status)));
  }, [parcels, tab]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  return (
    <View style={styles.flex}>
      <View style={styles.tabs}>
        {TABS.map(({ key, label }) => {
          const active = key === tab;
          return (
            <Pressable
              key={key}
              onPress={() => setTab(key)}
              style={[styles.tab, active && styles.tabActive]}
            >
              <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>{label}</Text>
            </Pressable>
          );
        })}
      </View>

      <Pressable
        onPress={() => router.push('/(app)/parcel/new')}
        style={({ pressed }) => [styles.newButton, pressed && styles.rowPressed]}
      >
        <Text style={styles.newButtonLabel}>+ {t('parcels.newParcel')}</Text>
      </Pressable>

      <FlatList
        data={visible}
        keyExtractor={(p) => String(p.id)}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListHeaderComponent={<ErrorText>{error}</ErrorText>}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Muted>{t('parcels.empty')}</Muted>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            // Forme objet : vérifiée par les routes typées, contrairement au gabarit de chaîne.
            onPress={() => router.push({ pathname: '/(app)/parcel/[id]', params: { id: item.id } })}
            style={({ pressed }) => [styles.row, pressed && styles.rowPressed]}
          >
            <View style={styles.rowTop}>
              <Text style={styles.tracking}>{item.tracking_id}</Text>
              {/* statusName vient traduit du backend : on ne le réécrit pas. */}
              <Text style={styles.status}>{item.statusName ?? '—'}</Text>
            </View>
            <Text style={styles.customer}>{item.customer_name}</Text>
            <Muted>{item.customer_address ?? ''}</Muted>
            <View style={styles.rowBottom}>
              <Text style={styles.amount}>{formatAmount(item.cash_collection)}</Text>
              <Muted>{item.deliveryType ?? ''}</Muted>
            </View>
          </Pressable>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  tabs: { flexDirection: 'row', backgroundColor: colors.surface, borderBottomWidth: 1, borderBottomColor: colors.border },
  tab: { flex: 1, paddingVertical: spacing.md, alignItems: 'center', borderBottomWidth: 3, borderBottomColor: 'transparent' },
  tabActive: { borderBottomColor: colors.primary },
  tabLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  tabLabelActive: { fontFamily: fonts.bodyMedium, color: colors.primary },
  newButton: {
    margin: spacing.md,
    marginBottom: 0,
    paddingVertical: spacing.md,
    borderRadius: radii.md,
    backgroundColor: colors.accent,
    alignItems: 'center',
  },
  newButtonLabel: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.md,
    color: colors.textOnPrimary,
  },
  list: { padding: spacing.md, gap: spacing.sm },
  empty: { padding: spacing.xl, alignItems: 'center' },
  row: {
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: spacing.xs,
  },
  rowPressed: { opacity: 0.7 },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  rowBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.xs },
  tracking: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.primary },
  status: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.xs, color: colors.info },
  customer: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.text },
  amount: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
});
