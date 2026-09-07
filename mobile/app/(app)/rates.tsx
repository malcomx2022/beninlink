import { useCallback, useEffect, useMemo, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { fetchCodCharges, fetchDeliveryGrid } from '../../src/api/merchant';
import type { CodCharge, DeliveryDelay, DeliveryRate, DeliveryZone } from '../../src/api/types';
import { Card, ErrorText, Muted, Title } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
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
 * **Deux affichages, un seul écran (D4).** Le serveur sert les deux formes
 * pendant la transition : les quatre colonnes héritées, et le barème par zones.
 * L'écran choisit la seconde **dès qu'elle n'est pas vide**, et retombe sur la
 * première sinon. Un serveur qui n'a pas encore basculé, ou un transporteur qui
 * n'a pas configuré ses zones, donnent donc exactement l'affichage d'avant.
 *
 * ⚠️ Le poids est une **valeur de tranche comparée à l'identique** par le
 * calculateur, pas un plafond : on écrit « 1 kg », jamais « jusqu'à 1 kg ».
 */

export default function RatesScreen() {
  const [rates, setRates] = useState<DeliveryRate[]>([]);
  const [zones, setZones] = useState<DeliveryZone[]>([]);
  const [delays, setDelays] = useState<DeliveryDelay[]>([]);
  const [codCharges, setCodCharges] = useState<CodCharge[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      // Deux endpoints indépendants : en parallèle.
      const [grid, c] = await Promise.all([fetchDeliveryGrid(), fetchCodCharges()]);
      setRates(grid.rates);
      setZones(grid.zones);
      setDelays(grid.delays);
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

  /** Le nom de zone d'un taux COD, quand le serveur l'a rattaché (`zone_code`). */
  const zoneParCode = useMemo(
    () => new Map(zones.map((zone) => [zone.code, zone.name])),
    [zones],
  );

  const parZones = zones.length > 0;
  const vide = parZones ? false : rates.length === 0;

  return (
    <ScrollView
      contentContainerStyle={styles.page}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <Muted>{t('rates.subtitle')}</Muted>
      <ErrorText>{error}</ErrorText>

      {vide && !error && <Muted>{t('rates.empty')}</Muted>}

      {/* Le supplément dépend du délai, jamais de la zone : il est annoncé une
          fois, en tête, et ne se répète pas dans chaque case. */}
      {parZones && delays.length > 0 && (
        <Card>
          <Title>{t('rates.delaysTitle')}</Title>
          {delays.map((delay) => (
            <View key={delay.id} style={styles.row}>
              <Text style={styles.rowLabel}>{delay.name}</Text>
              <Text style={styles.rowValue}>
                {Number(delay.surcharge) > 0
                  ? `+ ${formatAmount(delay.surcharge, false)}`
                  : t('rates.noSurcharge')}
              </Text>
            </View>
          ))}
          <Muted>{t('rates.delaysNotice')}</Muted>
        </Card>
      )}

      {parZones &&
        zones.map((zone) => (
          <Card key={zone.id}>
            <View style={styles.head}>
              <Title>{zone.name}</Title>
              {zone.export && <Text style={styles.badge}>{t('rates.flatRate')}</Text>}
            </View>

            {/* Une zone d'export se facture au pays, forfait, sans regarder le
                poids. Tant qu'un pays n'est pas tarifé, aucun prix ne lui est
                appliqué — l'écran le dit plutôt que d'afficher un zéro. */}
            {zone.export ? (
              zone.countries.length > 0 ? (
                zone.countries.map((pays) => (
                  <View key={pays.code} style={styles.row}>
                    <Text style={styles.rowLabel}>
                      {pays.name} <Text style={styles.code}>({pays.code})</Text>
                    </Text>
                    <Text style={styles.rowValue}>{formatAmount(pays.flat_amount, false)}</Text>
                  </View>
                ))
              ) : (
                <Muted>{t('rates.noCountry')}</Muted>
              )
            ) : zone.rates.length > 0 ? (
              <View style={styles.grid}>
                {zone.rates.map((tarif) => (
                  <View key={`${tarif.category_id}-${tarif.weight}`} style={styles.zone}>
                    <Text style={styles.zoneLabel}>{tarif.weight} kg</Text>
                    <Text style={styles.zoneValue}>{formatAmount(tarif.amount, false)}</Text>
                  </View>
                ))}
              </View>
            ) : (
              <Muted>{t('rates.noRate')}</Muted>
            )}
          </Card>
        ))}

      {/* Le barème négocié du marchand, quand il en a un : mêmes zones, ses
          montants. Le serveur le sert déjà résolu dans `zones[].rates`, ce
          bloc ne fait que le nommer pour que le marchand sache qu'il existe. */}
      {!parZones && rates.length > 0 && <Muted>{t('rates.noZone')}</Muted>}

      {codCharges.length > 0 && (
        <Card>
          <Title>{t('rates.codTitle')}</Title>
          {codCharges.map((cod) => {
            // Le serveur rattache chaque taux à sa zone (`zone_code`) : on
            // affiche le nom de la zone plutôt que le libellé de la colonne
            // d'origine, quand les deux existent.
            const zone = cod.zone_code ? zoneParCode.get(cod.zone_code) : undefined;

            return (
              <View key={cod.name} style={styles.row}>
                <Text style={styles.rowLabel}>{zone ?? cod.name}</Text>
                {/* Un taux, pas un montant : jamais de symbole monétaire ici. */}
                <Text style={styles.rowValue}>{formatRate(cod.charge)}</Text>
              </View>
            );
          })}
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
  code: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  zone: { flexGrow: 1, flexBasis: '45%', gap: spacing.xs },
  zoneLabel: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  zoneValue: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
  row: { flexDirection: 'row', justifyContent: 'space-between' },
  rowLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  rowValue: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.text },
});
