import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { fetchCodCharges, fetchDeliveryRates } from '../../src/api/merchant';
import type { CodCharge, DeliveryRate } from '../../src/api/types';
import { Card, ErrorText, Muted, Title } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { deliveryTypeLabel } from '../../src/domain/deliveryType';
import { formatAmount, formatRate } from '../../src/domain/money';
import { t } from '../../src/i18n';

/**
 * Barème de livraison du marchand — **lecture seule**.
 *
 * Le tarif appliqué n'est pas décidé ici : depuis la correction de S2, seul
 * `ChargeCalculator` (côté web/) établit les montants d'un colis. Cet écran ne
 * fait qu'afficher la grille pour que le marchand sache à quoi s'attendre ; il
 * ne recalcule rien et ne sert jamais de base à un montant envoyé au serveur.
 *
 * ⚠️ Le poids est une **valeur de tranche comparée à l'identique** par le
 * calculateur, pas un plafond : on écrit « 1 kg », jamais « jusqu'à 1 kg ».
 */

/**
 * Les 4 zones du barème, dans l'ordre de la maquette. Les libellés viennent de
 * `domain/deliveryType` — le backend n'en fournit pas pour ces colonnes.
 * `outside_city` s'écrit `outside_City` dans l'énumération du socle (faute de
 * frappe d'origine, conservée côté contrat).
 */
const ZONES = [
  { key: 'same_day', label: deliveryTypeLabel('same_day') },
  { key: 'next_day', label: deliveryTypeLabel('next_day') },
  { key: 'sub_city', label: deliveryTypeLabel('sub_city') },
  { key: 'outside_city', label: deliveryTypeLabel('outside_City') },
] as const;

export default function RatesScreen() {
  const [rates, setRates] = useState<DeliveryRate[]>([]);
  const [codCharges, setCodCharges] = useState<CodCharge[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      // Deux endpoints indépendants : en parallèle.
      const [r, c] = await Promise.all([fetchDeliveryRates(), fetchCodCharges()]);
      setRates(r);
      setCodCharges(c);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  return (
    <ScrollView
      contentContainerStyle={styles.page}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <Muted>{t('rates.subtitle')}</Muted>
      <ErrorText>{error}</ErrorText>

      {rates.length === 0 && !error && <Muted>{t('rates.empty')}</Muted>}

      {rates.map((rate) => (
        <Card key={rate.id}>
          <View style={styles.head}>
            <Title>{rate.category ?? '—'}</Title>
            {String(rate.status) === '1' && (
              <Text style={styles.badge}>{rate.statusName ?? ''}</Text>
            )}
          </View>
          <Muted>
            {t('rates.weight')} : {rate.weight ?? '—'} kg
          </Muted>
          <View style={styles.grid}>
            {ZONES.map((zone) => (
              <View key={zone.key} style={styles.zone}>
                <Text style={styles.zoneLabel}>{zone.label}</Text>
                <Text style={styles.zoneValue}>{formatAmount(rate[zone.key], false)}</Text>
              </View>
            ))}
          </View>
        </Card>
      ))}

      {codCharges.length > 0 && (
        <Card>
          <Title>{t('rates.codTitle')}</Title>
          {codCharges.map((cod) => (
            <View key={cod.name} style={styles.row}>
              <Text style={styles.rowLabel}>{cod.name}</Text>
              {/* Un taux, pas un montant : jamais de symbole monétaire ici. */}
              <Text style={styles.rowValue}>{formatRate(cod.charge)}</Text>
            </View>
          ))}
          <Muted>{t('rates.codNotice')}</Muted>
        </Card>
      )}

      <Muted>{t('rates.notice')}</Muted>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  head: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  badge: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.accentDark },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  zone: { flexGrow: 1, flexBasis: '45%', gap: spacing.xs },
  zoneLabel: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  zoneValue: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
  row: { flexDirection: 'row', justifyContent: 'space-between' },
  rowLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  rowValue: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.text },
});
