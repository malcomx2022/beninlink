import { StyleSheet, Text, View } from 'react-native';

import type { CustomsAlert } from '../api/types';
import { customsLevelColorName } from '../domain/customsLevel';
import { colors } from '../theme/colors';
import { fonts, fontSizes, spacing } from '../theme/typography';
import { t } from '../i18n';
import { Card, Muted, Title } from './ui';

type Props = {
  /** Les alertes DE la course, servies avec elle (`customs_alerts`, S95). */
  alerts: readonly CustomsAlert[];
};

/**
 * S95 — la douane sur la course du livreur, **en lecture seule**.
 *
 * Au ramassage d'un colis export, le livreur doit demander au marchand le
 * document que la règle douanière exige ; sans lui le colis risque un blocage
 * à la frontière. La gravité se lit sur le bord gauche et le badge, par la
 * même table que l'app marchand (`customsLevelColorName`). Rien n'est rendu
 * pour un colis domestique : la carte n'existe pas. Le livreur ne traite pas
 * une alerte (c'est au transporteur, S68) : pas de bouton.
 */
export function CustomsNotice({ alerts }: Props) {
  if (alerts.length === 0) return null;

  return (
    <Card testID="customs-notice">
      <Title>{t('customs.title')}</Title>
      <Muted>{t('customs.hint')}</Muted>
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
            {!!alert.required_document && (
              <Text style={styles.alertDocument}>
                {t('customs.requiredDocument')} : {alert.required_document}
              </Text>
            )}
            {!!alert.message && <Text style={styles.alertMessage}>{alert.message}</Text>}
            <Muted>{alert.status_name}</Muted>
          </View>
        );
      })}
    </Card>
  );
}

const styles = StyleSheet.create({
  alert: {
    borderLeftWidth: 4,
    paddingLeft: spacing.sm,
    paddingVertical: spacing.xs,
    gap: spacing.xs,
  },
  alertTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
  alertRoute: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text, flexShrink: 1 },
  alertBadge: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.xs },
  alertDocument: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text },
  alertMessage: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
});
