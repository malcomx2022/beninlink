import { useCallback, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';

import { ApiError } from '../../src/api/client';
import {
  fetchNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from '../../src/api/notifications';
import type { AppNotification } from '../../src/api/types';
import { Button, ErrorText, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/**
 * Fil de notifications du marchand.
 *
 * Tout vient du serveur, déjà rédigé : statuts de colis, recharges confirmées,
 * relevés émis, alertes douanières, messages de BeninLink, retraits traités.
 * L'app n'assemble rien, elle affiche et marque lu. Toucher une notification
 * la marque lue et ouvre le colis quand il y en a un.
 */

/** Libellé de repli par type, quand le titre serveur manque. */
function kindLabel(kind: string): string {
  switch (kind) {
    case 'parcel_status':
      return t('notifications.kindParcel');
    case 'wallet_credit':
      return t('notifications.kindWallet');
    case 'invoice':
      return t('notifications.kindInvoice');
    case 'customs':
      return t('notifications.kindCustoms');
    case 'payout':
      return t('notifications.kindPayout');
    default:
      return t('notifications.kindMessage');
  }
}

/** Couleur du liseré : ocre pour l'argent, rouge pour la douane, vert sinon. */
function kindColor(kind: string, level?: number): string {
  if (kind === 'customs') return (level ?? 0) >= 3 ? colors.danger : colors.accent;
  if (kind === 'wallet_credit' || kind === 'payout' || kind === 'invoice') return colors.accent;
  return colors.primary;
}

export default function NotificationsScreen() {
  const router = useRouter();
  const [items, setItems] = useState<AppNotification[]>([]);
  const [unread, setUnread] = useState(0);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async (pageToLoad: number) => {
    setError('');
    try {
      const result = await fetchNotifications(pageToLoad);
      setItems((current) =>
        pageToLoad === 1 ? result.notifications : [...current, ...result.notifications],
      );
      setUnread(result.unread_count);
      setPage(pageToLoad);
      // S78 : lu dans `page` par le module d'API, repli sur la page pleine.
      setHasMore(result.hasMore);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  // Rechargé à chaque retour sur l'écran : une notification a pu arriver.
  useFocusEffect(
    useCallback(() => {
      void load(1);
    }, [load]),
  );

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load(1);
    setRefreshing(false);
  }, [load]);

  const open = useCallback(
    async (item: AppNotification) => {
      if (!item.read) {
        // Optimiste : l'état local d'abord, le serveur ensuite. En cas d'échec
        // le prochain chargement rétablira la vérité du serveur.
        setItems((current) => current.map((n) => (n.id === item.id ? { ...n, read: true } : n)));
        setUnread((n) => Math.max(0, n - 1));
        markNotificationRead(item.id).catch(() => undefined);
      }
      if (item.parcel_id) {
        router.push({ pathname: '/(app)/parcel/[id]', params: { id: String(item.parcel_id) } });
      }
    },
    [router],
  );

  const readAll = useCallback(async () => {
    setBusy(true);
    try {
      await markAllNotificationsRead();
      await load(1);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setBusy(false);
    }
  }, [load]);

  return (
    <FlatList
      data={items}
      keyExtractor={(n) => n.id}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      ListHeaderComponent={
        <View style={styles.header}>
          <View style={styles.headerRow}>
            <Muted>
              {unread} {unread > 1 ? t('notifications.unreadPlural') : t('notifications.unread')}
            </Muted>
            {unread > 0 && (
              <Text style={styles.action} onPress={() => !busy && void readAll()}>
                {t('notifications.markAllRead')}
              </Text>
            )}
          </View>
          <ErrorText>{error}</ErrorText>
        </View>
      }
      ListEmptyComponent={
        <View style={styles.empty}>
          <Muted>{t('notifications.empty')}</Muted>
        </View>
      }
      ListFooterComponent={
        hasMore ? (
          <View style={styles.footer}>
            <Button title={t('common.loadMore')} onPress={() => void load(page + 1)} />
          </View>
        ) : null
      }
      renderItem={({ item }) => (
        <Pressable
          onPress={() => void open(item)}
          style={({ pressed }) => [
            styles.row,
            { borderLeftColor: kindColor(item.kind, item.level) },
            !item.read && styles.rowUnread,
            pressed && styles.rowPressed,
          ]}
        >
          <View style={styles.rowTop}>
            <Text style={[styles.title, !item.read && styles.titleUnread]}>
              {item.title || kindLabel(item.kind)}
            </Text>
            {!item.read && <View style={styles.dot} />}
          </View>
          <Text style={styles.body}>{item.body}</Text>
          <View style={styles.rowTop}>
            <Muted>{kindLabel(item.kind)}</Muted>
            <Muted>{item.created_at ?? ''}</Muted>
          </View>
        </Pressable>
      )}
    />
  );
}

const styles = StyleSheet.create({
  list: { padding: spacing.md, gap: spacing.sm, backgroundColor: colors.background, flexGrow: 1 },
  header: { gap: spacing.xs, marginBottom: spacing.xs },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  action: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.primary },
  empty: { padding: spacing.xl, alignItems: 'center' },
  footer: { paddingTop: spacing.sm },
  row: {
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    borderLeftWidth: 4,
    padding: spacing.md,
    gap: spacing.xs,
  },
  rowUnread: { borderColor: colors.primary },
  rowPressed: { opacity: 0.85 },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
  title: { fontFamily: fonts.body, fontSize: fontSizes.md, color: colors.text, flexShrink: 1 },
  titleUnread: { fontFamily: fonts.bodyMedium },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: colors.accent },
  body: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
});
