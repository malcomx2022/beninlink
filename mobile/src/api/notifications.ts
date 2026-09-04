/**
 * Fil de notifications du marchand (`notifications/*`).
 *
 * Le serveur rédige déjà les textes et sait quel utilisateur regarde : l'app
 * n'a rien à filtrer. Paginé par 20 ; comme partout, la collection arrive en
 * tableau nu et une page incomplète est la dernière.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import type { AppNotification } from './types';

export const NOTIFICATIONS_PER_PAGE = 20;

export type NotificationPage = {
  notifications: AppNotification[];
  unread_count: number;
};

export async function fetchNotifications(page = 1): Promise<NotificationPage> {
  const data = await api.get<NotificationPage>(endpoints.notificationsIndex, { query: { page } });
  return {
    notifications: data?.notifications ?? [],
    unread_count: data?.unread_count ?? 0,
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
