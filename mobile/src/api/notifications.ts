/**
 * Fil de notifications du marchand (`notifications/*`).
 *
 * Le serveur rédige déjà les textes et sait quel utilisateur regarde : l'app
 * n'a rien à filtrer. Paginé par 20 ; depuis S78 la réponse dit où finit la
 * liste (`page` à la racine), la constante n'est plus qu'un repli.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { hasNextPage } from './pagination';
import type { AppNotification } from './types';

export const NOTIFICATIONS_PER_PAGE = 20;

export type NotificationPage = {
  notifications: AppNotification[];
  unread_count: number;
  /** Il reste des pages (S78 : lu dans `page`, sinon déduit d'une page pleine). */
  hasMore: boolean;
};

export async function fetchNotifications(page = 1): Promise<NotificationPage> {
  const { data, page: pageInfo } = await api.getPaged<Omit<NotificationPage, 'hasMore'>>(
    endpoints.notificationsIndex,
    { query: { page } },
  );
  const notifications = data?.notifications ?? [];
  return {
    notifications,
    unread_count: data?.unread_count ?? 0,
    hasMore: hasNextPage(pageInfo, notifications.length, NOTIFICATIONS_PER_PAGE),
  };
}

export async function fetchUnreadCount(): Promise<number> {
  const data = await api.get<{ unread_count: number }>(endpoints.notificationsUnreadCount);
  return data?.unread_count ?? 0;
}

export async function markNotificationRead(id: string): Promise<void> {
  await api.put(endpoints.notificationRead(id));
}

export async function markAllNotificationsRead(): Promise<void> {
  await api.put(endpoints.notificationsReadAll);
}
