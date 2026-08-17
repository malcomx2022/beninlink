import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/theme/colors';
import { fonts, fontSizes, radii, spacing } from '@/theme/typography';
import { formatAmount, formatRate } from '@/domain/money';
import { toMerchantStage, BackendParcelStatus } from '@/domain/parcelStatus';
import { stageLabel, t } from '@/i18n';

/**
 * Écran de contrôle du lot 0 — vérifie de visu que le socle est en place :
 * polices de la charte, couleurs, i18n, mise en forme FCFA et correspondance
 * des statuts. À remplacer par le tableau de bord au lot 1.
 */
export default function SocleCheck() {
  const stages = [
    BackendParcelStatus.PENDING,
    BackendParcelStatus.PICKUP_ASSIGN,
    BackendParcelStatus.RECEIVED_WAREHOUSE,
    BackendParcelStatus.DELIVERY_MAN_ASSIGN,
    BackendParcelStatus.DELIVERED,
    BackendParcelStatus.PARTIAL_DELIVERED,
    BackendParcelStatus.RETURNED_MERCHANT,
    // Une annulation ramène à l'étape précédente, elle n'est pas un statut propre.
    BackendParcelStatus.DELIVERED_CANCEL,
  ];

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <Text style={styles.title}>{t('common.appName')}</Text>
      <Text style={styles.subtitle}>Socle — lot 0</Text>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>{t('dashboard.balanceToSettle')}</Text>
        <Text style={styles.amount}>{formatAmount(1250000)}</Text>
        <Text style={styles.muted}>
          {t('invoices.vat')} : {formatRate(18)}
        </Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Montants (entiers, sans décimales)</Text>
        {[0, 1500, 12000.6, -300, '2500.00'].map((value, i) => (
          <Text key={i} style={styles.row}>
            {String(value)} → <Text style={styles.numeric}>{formatAmount(value)}</Text>
          </Text>
        ))}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Statuts : 33 codes backend → 7 étapes</Text>
        {stages.map((code) => (
          <Text key={code} style={styles.row}>
            #{code} → <Text style={styles.bodyMedium}>{stageLabel(toMerchantStage(code))}</Text>
          </Text>
        ))}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  title: { fontFamily: fonts.headingBold, fontSize: fontSizes.xxl, color: colors.primary },
  subtitle: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: spacing.xs,
  },
  cardTitle: {
    fontFamily: fonts.heading,
    fontSize: fontSizes.md,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  amount: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
  row: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  numeric: { fontFamily: fonts.numeric, color: colors.primary },
  bodyMedium: { fontFamily: fonts.bodyMedium, color: colors.primaryLight },
  muted: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
});
