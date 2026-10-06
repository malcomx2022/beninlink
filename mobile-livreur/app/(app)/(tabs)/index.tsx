import { useCallback, useEffect, useMemo, useState } from 'react';
import { FlatList, Linking, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { fetchDashboard } from '../../../src/api/deliveryman';
import type { DashboardData, ParcelSummary } from '../../../src/api/types';
import { ErrorText, Muted } from '../../../src/components/ui';
import { shareCurrentPosition } from '../../../src/domain/location';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../../src/theme/typography';
import { formatAmount } from '../../../src/domain/money';
import { t } from '../../../src/i18n';

type TabKey = 'ongoing' | 'returns' | 'delivered';

const TABS: { key: TabKey; label: string }[] = [
  { key: 'ongoing', label: t('parcels.tabOngoing') },
  { key: 'returns', label: t('parcels.tabReturns') },
  { key: 'delivered', label: t('parcels.tabDelivered') },
];

const EMPTY: DashboardData = {
  deliveryman_assign: [],
  deliveryman_re_schedule: [],
  return_to_courier: [],
  delivered: [],
};

/** Ouvre le numéroteur. */
function call(phone: string | null) {
  if (!phone) return;
  void Linking.openURL(`tel:${phone.replace(/\s+/g, '')}`);
}

/** Ouvre l'application de cartes sur l'adresse (le colis ne porte pas toujours de coordonnées). */
function route(address: string | null) {
  if (!address) return;
  void Linking.openURL(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`);
}

export default function ParcelsScreen() {
  const router = useRouter();
  const [data, setData] = useState<DashboardData>(EMPTY);
  const [tab, setTab] = useState<TabKey>('ongoing');
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [locationNotice, setLocationNotice] = useState('');
  const [sharing, setSharing] = useState(false);

  const share = useCallback(async () => {
    setSharing(true);
    setLocationNotice(t('location.sending'));
    try {
      const result = await shareCurrentPosition();
      setLocationNotice(
        result === 'sent' ? t('location.sent') : result === 'denied' ? t('location.denied') : t('location.unavailable'),
      );
    } catch (e) {
      setLocationNotice(e instanceof ApiError ? e.message : t('location.unavailable'));
    } finally {
      setSharing(false);
    }
  }, []);

  const load = useCallback(async () => {
    setError('');
    try {
      setData(await fetchDashboard());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  // Retour d'un changement de statut : la liste doit refléter la nouvelle issue.
  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  /**
   * Le backend fournit déjà le regroupement : quatre listes par statut.
   * « En cours » = assignés + reprogrammés ; les deux autres sont directes.
   */
  const visible = useMemo<ParcelSummary[]>(() => {
    switch (tab) {
      case 'ongoing':
        return [...data.deliveryman_assign, ...data.deliveryman_re_schedule];
      case 'returns':
        return data.return_to_courier;
      case 'delivered':
        return data.delivered;
    }
  }, [data, tab]);

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
            <Pressable key={key} onPress={() => setTab(key)} style={[styles.tab, active && styles.tabActive]}>
              <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>{label}</Text>
            </Pressable>
          );
        })}
      </View>

      <FlatList
        data={visible}
        keyExtractor={(p) => String(p.id)}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListHeaderComponent={
          <View style={styles.header}>
            <Pressable onPress={share} disabled={sharing} style={({ pressed }) => [styles.share, pressed && styles.rowPressed]}>
              <Text style={styles.shareLabel}>{t('location.share')}</Text>
            </Pressable>
            {!!locationNotice && <Muted>{locationNotice}</Muted>}
            <ErrorText>{error}</ErrorText>
          </View>
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <Muted>{t('parcels.empty')}</Muted>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => router.push({ pathname: '/(app)/parcel/[id]', params: { id: item.id } })}
            style={({ pressed }) => [styles.row, pressed && styles.rowPressed]}
          >
            <View style={styles.rowTop}>
              <Text style={styles.tracking}>{item.tracking_id}</Text>
              {/* statusName vient traduit du backend : on ne le réécrit pas. */}
              <Text style={styles.status}>{item.statusName ?? '—'}</Text>
            </View>
            {/* S101 — une alerte douanière en cours : le document à collecter se voit depuis la liste. */}
            {(item.customs_pending ?? 0) > 0 && (
              <Text style={styles.customsBadge} testID={`customs-badge-${item.id}`}>
                {t('customs.badge')}
              </Text>
            )}
            <Text style={styles.customer}>{item.customer_name}</Text>
            <Muted>{item.customer_address ?? ''}</Muted>
            <View style={styles.rowBottom}>
              <View>
                <Text style={styles.codLabel}>{t('parcels.cod')}</Text>
                <Text style={styles.amount}>{formatAmount(item.cash_collection)}</Text>
              </View>
              <View style={styles.actions}>
                <Pressable onPress={() => call(item.customer_phone)} style={styles.action} disabled={!item.customer_phone}>
                  <Text style={styles.actionLabel}>{t('common.call')}</Text>
                </Pressable>
                <Pressable onPress={() => route(item.customer_address)} style={styles.action} disabled={!item.customer_address}>
                  <Text style={styles.actionLabel}>{t('common.route')}</Text>
                </Pressable>
              </View>
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
  list: { padding: spacing.md, gap: spacing.sm },
  header: { gap: spacing.xs, marginBottom: spacing.xs },
  share: {
    alignSelf: 'flex-start',
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
    borderRadius: radii.pill,
    backgroundColor: colors.primary,
  },
  shareLabel: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.textOnPrimary },
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
  rowBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end', marginTop: spacing.xs },
  tracking: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.primary },
  status: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.xs, color: colors.info },
  customsBadge: {
    alignSelf: 'flex-start',
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.xs,
    color: colors.warning,
    borderWidth: 1,
    borderColor: colors.warning,
    borderRadius: 4,
    paddingHorizontal: 6,
    paddingVertical: 1,
  },
  customer: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.text },
  codLabel: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  amount: { fontFamily: fonts.numeric, fontSize: fontSizes.lg, color: colors.accent },
  actions: { flexDirection: 'row', gap: spacing.sm },
  action: {
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.primary,
  },
  actionLabel: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.primary },
});
