import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { Parcel } from '../api/types';
import { formatAmount } from '../domain/money';
import { colors } from '../theme/colors';
import { fonts, fontSizes, radii, spacing } from '../theme/typography';
import { t } from '../i18n';
import { Muted } from './ui';

type Props = {
  parcel: Parcel;
  /** Ouvre la fiche du colis. */
  onOpen: () => void;
};

/**
 * S103 — la carte d'un colis dans la liste du marchand, extraite de l'écran
 * pour être rendue en test (la limite que S84 et S101 avaient notée).
 *
 * Suivi, statut (traduit par le backend, jamais réécrit ici), client et
 * adresse, montant à encaisser en FCFA entiers, type de livraison, et — S101 —
 * la pastille « Douane » quand une alerte douanière est en cours
 * (`customs_pending`, absent sur un serveur d'avant S101 : pas de pastille).
 */
export function ParcelCard({ parcel, onOpen }: Props) {
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
        <Text style={styles.amount}>{formatAmount(parcel.cash_collection)}</Text>
        <Muted>{parcel.deliveryType ?? ''}</Muted>
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
  rowBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: spacing.xs },
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
  amount: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
});
