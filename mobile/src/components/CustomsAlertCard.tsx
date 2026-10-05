import { StyleSheet, Text, View } from 'react-native';

import type { CustomsAlert } from '../api/types';
import { CustomsAlertStatus, customsLevelColorName } from '../domain/customsLevel';
import { colors } from '../theme/colors';
import { fonts, fontSizes, spacing } from '../theme/typography';
import { t } from '../i18n';
import { Button, Card, Muted, Title } from './ui';

type Props = {
  /** Les alertes DU colis, servies avec lui (`customs_alerts`, S82). */
  alerts: readonly CustomsAlert[];
  /** L'alerte dont le « marquer traitée » est en cours, ou `null`. */
  busyId: number | null;
  onResolve: (alertId: number) => void;
};

/**
 * S82 (M1) — la douane sur le colis lui-même ; S84 (M2) — extraite de l'écran de
 * détail pour être **rendue en test**, ce qu'une lecture de source ne peut pas.
 *
 * Le niveau se lit sur le bord gauche et le badge, par la même table que
 * l'écran Douane et le tableau de bord (`customsLevelColorName`, S68) : un
 * blocage ne se lit jamais comme un conseil. Rien n'est rendu pour un colis
 * domestique : la carte n'existe pas.
 */
export function CustomsAlertCard({ alerts, busyId, onResolve }: Props) {
  if (alerts.length === 0) return null;

  return (
    <Card>
      <Title>{t('customs.title')}</Title>
      {alerts.map((alert) => {
        const tone = colors[customsLevelColorName(alert.level)];
        return (
          <View key={alert.id} style={[styles.alert, { borderLeftColor: tone }]} testID={`customs-alert-${alert.id}`}>
            <View style={styles.alertTop}>
              <Text style={styles.alertRoute}>
                {alert.country_name} — {alert.category_name}
              </Text>
              <Text style={[styles.alertBadge, { color: tone }]}>{alert.level_name}</Text>
            </View>
            <Text style={styles.alertMessage}>{alert.message}</Text>
            {!!alert.required_document && (
              <Text style={styles.alertDocument}>
                {t('customs.requiredDocument')} : {alert.required_document}
              </Text>
            )}
            {alert.status === CustomsAlertStatus.PENDING ? (
              <Button
                title={t('customs.markResolved')}
                onPress={() => onResolve(alert.id)}
                loading={busyId === alert.id}
              />
            ) : (
              <Muted>{alert.status_name}</Muted>
            )}
          </View>
        );
      })}
    </Card>
  );
}

const styles = StyleSheet.create({
  alert: {
    // Le niveau se lit d'un coup d'œil sur le bord gauche, comme sur l'écran Douane.
    borderLeftWidth: 4,
    paddingLeft: spacing.sm,
    paddingVertical: spacing.xs,
    gap: spacing.xs,
  },
  alertTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
  alertRoute: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text, flexShrink: 1 },
  alertBadge: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.xs },
  alertMessage: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  alertDocument: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.textMuted },
});
