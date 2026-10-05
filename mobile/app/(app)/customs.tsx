import { useCallback, useEffect, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { fetchCustomsAlerts, resolveCustomsAlert } from '../../src/api/customs';
import type { CustomsAlert } from '../../src/api/types';
import { Button, ErrorText, Muted } from '../../src/components/ui';
import { CustomsAlertStatus, customsLevelColorName } from '../../src/domain/customsLevel';
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

const { PENDING, RESOLVED } = CustomsAlertStatus;

/**
 * La couleur d'un niveau, résolue dans la charte.
 *
 * ⚠️ Elle était écrite ici, et elle se trompait deux fois sur trois : ocre pour
 * un AVERTISSEMENT (la charte réserve l'ocre aux « actions clés ») et vert
 * primaire pour un INFO (qui se lit « tout va bien »). Or `colors.ts` nomme les
 * trois couleurs douanières. La correspondance vit maintenant dans
 * `src/domain/customsLevel.ts`, où le tableau de bord la lit aussi.
 */
function levelColor(level: number): string {
  return colors[customsLevelColorName(level)];
}

export default function CustomsScreen() {
  // `useState<number>` et non l'inférence : `CustomsAlertStatus` est `as const`,
  // donc `useState(PENDING)` figerait le type sur le littéral `1`.
  const [tab, setTab] = useState<number>(PENDING);
  const [alerts, setAlerts] = useState<CustomsAlert[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);
  /** Dernière page reçue, et s'il en reste — même forme que l'écran Relevés. */
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      const first = await fetchCustomsAlerts(tab, 1);
      setAlerts(first.items);
      setPage(1);
      // S78 : le serveur dit où finit la liste (`page`) ; le module d'API retombe
      // sur « page incomplète = dernière » si le serveur ne le dit pas.
      setHasMore(first.hasMore);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, [tab]);

  /**
   * ⚠️ L'écran ne lisait QUE la première page.
   *
   * `customs/alerts` répond en `paginate(20)` et `CUSTOMS_ALERTS_PER_PAGE` était
   * exporté depuis le début — sans être utilisé nulle part. Un marchand à plus
   * de vingt alertes en voyait vingt, sans rien qui le lui dise : ni compteur,
   * ni bouton, ni fin de liste. La perte était **silencieuse**, et c'est la
   * forme de défaut la plus coûteuse sur un écran de conformité douanière.
   */
  const loadMore = useCallback(async () => {
    if (!hasMore || loadingMore || refreshing) return;

    setLoadingMore(true);
    try {
      const next = await fetchCustomsAlerts(tab, page + 1);
      setAlerts((current) => [...current, ...next.items]);
      setPage((p) => p + 1);
      setHasMore(next.hasMore);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
      setHasMore(false);
    } finally {
      setLoadingMore(false);
    }
  }, [hasMore, loadingMore, page, refreshing, tab]);

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
        //
        // Conséquence assumée depuis la pagination : la liste revient à sa
        // première page. Retirer une ligne au milieu d'un lot déjà parcouru
        // décalerait toutes les pages suivantes côté serveur — recharger dit la
        // vérité, reconstituer localement la devinerait.
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
      onEndReached={() => void loadMore()}
      onEndReachedThreshold={0.4}
      ListFooterComponent={
        loadingMore ? (
          <View style={styles.empty}>
            <Muted>{t('common.loading')}</Muted>
          </View>
        ) : null
      }
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
