import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { ParcelSummary } from '../api/types';
import { formatAmount } from '../domain/money';
import { colors } from '../theme/colors';
import { fonts, fontSizes, radii, spacing } from '../theme/typography';
import { t } from '../i18n';
import { Muted } from './ui';

type Props = {
  parcel: ParcelSummary;
  /** Ouvre la course. */
  onOpen: () => void;
  /** Appelle le client ; le bouton est inactif sans numéro. */
  onCall: () => void;
  /** Ouvre l'itinéraire ; le bouton est inactif sans adresse. */
  onRoute: () => void;
};

/**
 * S103 — la carte d'une course dans la liste du livreur, extraite de l'écran
 * pour être rendue en test (la limite que S84 et S101 avaient notée).
 *
 * Elle montre ce dont le livreur a besoin avant d'ouvrir la course : le suivi,
 * le statut (traduit par le backend, jamais réécrit ici), le client et son
 * adresse, le montant à encaisser en FCFA entiers, et — S101 — la pastille
 * « Douane » quand un document reste à collecter (`customs_pending`, absent
 * sur un serveur d'avant S101 : la pastille ne s'affiche alors pas).
 */
export function ParcelCard({ parcel, onOpen, onCall, onRoute }: Props) {
  return (
    <Pressable
      onPress={onOpen}
      style={({ pressed }) => [styles.row, pressed && styles.rowPressed]}
      testID={`parcel-card-${parcel.id}`}
    >
      <View style={styles.rowTop}>
        <Text style={styles.tracking}>{parcel.tracking_id}</Text>
        {/* statusName vient traduit du backend : on ne le réécrit pas. */}
        <Text style={styles.status}>{parcel.statusName ?? '—'}</Text>
      </View>
      {/* S101 — une alerte douanière en cours : le document à collecter se voit depuis la liste. */}
      {(parcel.customs_pending ?? 0) > 0 && (
        <Text style={styles.customsBadge} testID={`customs-badge-${parcel.id}`}>
          {t('customs.badge')}
        </Text>
      )}
      <Text style={styles.customer}>{parcel.customer_name}</Text>
      <Muted>{parcel.customer_address ?? ''}</Muted>
      <View style={styles.rowBottom}>
        <View>
          <Text style={styles.codLabel}>{t('parcels.cod')}</Text>
          <Text style={styles.amount}>{formatAmount(parcel.cash_collection)}</Text>
        </View>
        <View style={styles.actions}>
          <Pressable onPress={onCall} style={styles.action} disabled={!parcel.customer_phone} accessibilityRole="button">
            <Text style={styles.actionLabel}>{t('common.call')}</Text>
          </Pressable>
          <Pressable onPress={onRoute} style={styles.action} disabled={!parcel.customer_address} accessibilityRole="button">
            <Text style={styles.actionLabel}>{t('common.route')}</Text>
          </Pressable>
        </View>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
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
