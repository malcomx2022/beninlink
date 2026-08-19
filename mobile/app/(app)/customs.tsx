import { useCallback, useEffect, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { fetchCustomsAlerts, resolveCustomsAlert } from '../../src/api/customs';
import type { CustomsAlert } from '../../src/api/types';
import { Button, ErrorText, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/**
 * Alertes douanières du marchand — chantier 5.
 *
 * Deux onglets, comme la maquette : « En cours » et « Traitées ». Le marchand
 * marque une alerte traitée quand il a réuni le document.
 *
 * ⚠️ Aucune règle douanière ici : le niveau, le document et le message viennent
 * du serveur, déjà traduits. L'app ne décide pas de ce qui passe la frontière —
 * même discipline que les montants depuis S2.
 */

/** 1 en cours, 2 traitée (App\Enums\CustomsAlertStatus). */
const PENDING = 1;
const RESOLVED = 2;

/** Rouge pour un blocage, ocre pour un avertissement — charte du projet. */
function levelColor(level: number): string {
  if (level >= 3) return colors.danger;
  if (level === 2) return colors.accent;
  return colors.primary;
}

export default function CustomsScreen() {
  const [tab, setTab] = useState(PENDING);
  const [alerts, setAlerts] = useState<CustomsAlert[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);

  const load = useCallback(async () => {
    setError('');
    try {
      setAlerts(await fetchCustomsAlerts(tab));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, [tab]);

  useEffect(() => {
    void load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  const resolve = useCallback(
    async (id: number) => {
      setBusyId(id);
      try {
        await resolveCustomsAlert(id);
        // L'alerte quitte l'onglet « En cours » : on recharge plutôt que de
        // deviner localement ce que le serveur a enregistré.
        await load();
      } catch (e) {
        setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
      } finally {
        setBusyId(null);
      }
    },
    [load],
  );

  return (
    <FlatList
      data={alerts}
      keyExtractor={(alert) => String(alert.id)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      ListHeaderComponent={
        <View style={styles.header}>
          <Muted>{t('customs.subtitle')}</Muted>
          <View style={styles.tabs}>
            {[
              { value: PENDING, label: t('customs.tabPending') },
              { value: RESOLVED, label: t('customs.tabResolved') },
            ].map((option) => {
              const active = option.value === tab;
              return (
                <Pressable
                  key={option.value}
                  onPress={() => setTab(option.value)}
                  style={[styles.tab, active && styles.tabActive]}
                >
                  <Text style={[styles.tabLabel, active && styles.tabLabelActive]}>
                    {option.label}
                  </Text>
                </Pressable>
              );
            })}
          </View>
          <ErrorText>{error}</ErrorText>
        </View>
      }
      ListEmptyComponent={
        <View style={styles.empty}>
          <Muted>{t('customs.empty')}</Muted>
        </View>
      }
      renderItem={({ item }) => (
        <View style={[styles.row, { borderLeftColor: levelColor(item.level) }]}>
          <View style={styles.rowTop}>
            <Text style={styles.route}>
              {item.country_name} — {item.category_name}
            </Text>
            <Text style={[styles.badge, { color: levelColor(item.level) }]}>{item.level_name}</Text>
          </View>

          {!!item.tracking_id && <Muted>{item.tracking_id}</Muted>}

          <Text style={styles.message}>{item.message}</Text>

          {!!item.required_document && (
            <Text style={styles.document}>
              {t('customs.requiredDocument')} : {item.required_document}
            </Text>
          )}

          <View style={styles.rowTop}>
            <Muted>{item.created_at ?? ''}</Muted>
            {item.status === RESOLVED && <Muted>{item.status_name}</Muted>}
          </View>

          {item.status === PENDING && (
            <Button
              title={t('customs.markResolved')}
              onPress={() => void resolve(item.id)}
              loading={busyId === item.id}
            />
          )}
        </View>
      )}
    />
  );
}

const styles = StyleSheet.create({
  list: { padding: spacing.md, gap: spacing.sm, backgroundColor: colors.background, flexGrow: 1 },
  header: { gap: spacing.sm, marginBottom: spacing.xs },
  tabs: { flexDirection: 'row', gap: spacing.sm },
  tab: {
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  tabActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  tabLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  tabLabelActive: { fontFamily: fonts.bodyMedium, color: colors.textOnPrimary },
  empty: { padding: spacing.xl, alignItems: 'center' },
  row: {
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    // Le niveau se lit d'un coup d'œil sur le bord gauche.
    borderLeftWidth: 4,
    padding: spacing.md,
    gap: spacing.xs,
  },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  route: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.text, flexShrink: 1 },
  badge: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.xs },
  message: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  document: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.textMuted },
});
