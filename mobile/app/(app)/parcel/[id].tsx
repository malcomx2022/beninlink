import { useCallback, useEffect, useState } from 'react';
import { Image, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useLocalSearchParams } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { resolveCustomsAlert } from '../../../src/api/customs';
import { fetchParcelTimeline } from '../../../src/api/parcels';
import type { CustomsAlert, Parcel, ParcelEvent } from '../../../src/api/types';
import { Button, Card, ErrorText, Muted, Title } from '../../../src/components/ui';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../../src/theme/typography';
import { CustomsAlertStatus, customsLevelColorName } from '../../../src/domain/customsLevel';
import { formatAmount } from '../../../src/domain/money';
import { TIMELINE_ORDER, isIncident, toMerchantStage } from '../../../src/domain/parcelStatus';
import { stageLabel, t } from '../../../src/i18n';

export default function ParcelDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [parcel, setParcel] = useState<Parcel | null>(null);
  const [events, setEvents] = useState<ParcelEvent[]>([]);
  // S82 (M1) — les alertes douanières DE CE colis, servies avec lui.
  const [customsAlerts, setCustomsAlerts] = useState<CustomsAlert[]>([]);
  const [busyAlertId, setBusyAlertId] = useState<number | null>(null);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setError('');
    const numericId = Number(id);
    if (!Number.isFinite(numericId)) {
      setError(t('errors.unexpected'));
      return;
    }
    try {
      const { parcel: p, events: e, customsAlerts: a } = await fetchParcelTimeline(numericId);
      setParcel(p);
      setEvents(e);
      setCustomsAlerts(a);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t('errors.unexpected'));
    }
  }, [id]);

  /** Le marchand déclare avoir réuni le document ; le serveur rend l'alerte à jour. */
  const resolveAlert = useCallback(async (alertId: number) => {
    setBusyAlertId(alertId);
    setError('');
    try {
      const updated = await resolveCustomsAlert(alertId);
      setCustomsAlerts((current) =>
        current.map((alert) => (alert.id === alertId && updated ? updated : alert)),
      );
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t('errors.unexpected'));
    } finally {
      setBusyAlertId(null);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const stage = parcel ? toMerchantStage(parcel.status) : null;

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <ErrorText>{error}</ErrorText>

      {parcel && (
        <>
          <Card>
            <Text style={styles.tracking}>{parcel.tracking_id}</Text>
            <Text style={styles.status}>{parcel.statusName ?? '—'}</Text>
            <Muted>{parcel.created_at ?? ''}</Muted>
          </Card>

          {/* S82 (M1) — la douane sur le colis lui-même. Le niveau se lit sur le
              bord gauche et le badge, par la même table que l'écran Douane et le
              tableau de bord (`customsLevelColorName`, S68) : un blocage ne se
              lit jamais comme un conseil. Rien n'est rendu pour un colis
              domestique : la carte n'existe pas. */}
          {customsAlerts.length > 0 && (
            <Card>
              <Title>{t('customs.title')}</Title>
              {customsAlerts.map((alert) => {
                const tone = colors[customsLevelColorName(alert.level)];
                return (
                  <View key={alert.id} style={[styles.alert, { borderLeftColor: tone }]}>
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
                        onPress={() => void resolveAlert(alert.id)}
                        loading={busyAlertId === alert.id}
                      />
                    ) : (
                      <Muted>{alert.status_name}</Muted>
                    )}
                  </View>
                );
              })}
            </Card>
          )}

          <Card>
            <Title>{t('parcels.recipient')}</Title>
            <Line label={t('parcels.name')} value={parcel.customer_name} />
            <Line label={t('auth.phone')} value={parcel.customer_phone ?? '—'} />
            <Line label={t('parcels.address')} value={parcel.customer_address ?? '—'} />
            <Line label={t('parcels.deliveryType')} value={parcel.deliveryType ?? '—'} />
            {/* D4 — la route n'apparaît que sur un colis qui en porte une : un
                colis hérité garde son seul type de livraison, sans ligne vide. */}
            {!!parcel.zone && <Line label={t('parcels.zone')} value={parcel.zone} />}
            {!!parcel.delay && <Line label={t('parcels.delay')} value={parcel.delay} />}
            <Line label={t('parcels.weight')} value={parcel.weight ?? '—'} />
          </Card>

          <Card>
            <Title>{t('parcels.amounts')}</Title>
            <Line label={t('parcels.cashCollection')} value={formatAmount(parcel.cash_collection)} />
            <Line label={t('invoices.fees')} value={formatAmount(parcel.total_delivery_amount)} />
            <Line label={t('parcels.codFee')} value={formatAmount(parcel.cod_amount)} />
            <Line label={t('invoices.vat')} value={formatAmount(parcel.vat_amount)} />
            <Line
              label={t('parcels.currentPayable')}
              value={formatAmount(parcel.current_payable)}
              strong
            />
          </Card>

          <Card>
            <Title>{t('parcels.timeline')}</Title>
            {/* Le backend n'alimente `parcelEvents` qu'aux changements de statut :
                un colis neuf n'a aucun événement. On montre alors la progression
                déduite du statut courant, pour ne pas afficher un écran vide. */}
            {events.length > 0 ? (
              events.map((event) => (
                <View key={event.id} style={styles.event}>
                  <Text style={styles.eventLabel}>{event.parcel_status_name || '—'}</Text>
                  <Muted>{[event.date, event.time_date].filter(Boolean).join(' · ')}</Muted>
                  {!!event.description && <Muted>{event.description}</Muted>}
                  {/* Preuves de livraison jointes par le livreur (photo, signature). */}
                  {!!event.delivered_image && (
                    <View style={styles.proof}>
                      <Muted>{t('parcels.proofPhoto')}</Muted>
                      <Image source={{ uri: event.delivered_image }} style={styles.proofPhoto} resizeMode="cover" />
                    </View>
                  )}
                  {!!event.signature_image && (
                    <View style={styles.proof}>
                      <Muted>{t('parcels.proofSignature')}</Muted>
                      <Image source={{ uri: event.signature_image }} style={styles.proofSignature} resizeMode="contain" />
                    </View>
                  )}
                </View>
              ))
            ) : (
              <View style={styles.steps}>
                {TIMELINE_ORDER.map((step) => {
                  const reached = stage
                    ? TIMELINE_ORDER.indexOf(step) <= TIMELINE_ORDER.indexOf(stage)
                    : false;
                  return (
                    <View key={step} style={styles.step}>
                      <View style={[styles.dot, reached && styles.dotReached]} />
                      <Text style={[styles.stepLabel, reached && styles.stepLabelReached]}>
                        {stageLabel(step)}
                      </Text>
                    </View>
                  );
                })}
                {stage && isIncident(stage) && (
                  <Text style={styles.incident}>{stageLabel(stage)}</Text>
                )}
              </View>
            )}
          </Card>
        </>
      )}
    </ScrollView>
  );
}

function Line({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <View style={styles.line}>
      <Text style={styles.lineLabel}>{label}</Text>
      <Text style={[styles.lineValue, strong && styles.lineValueStrong]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  tracking: { fontFamily: fonts.numeric, fontSize: fontSizes.xl, color: colors.primary },
  status: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.info },
  line: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  lineLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  lineValue: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.text,
    flexShrink: 1,
    textAlign: 'right',
  },
  lineValueStrong: { fontFamily: fonts.numeric, color: colors.accent, fontSize: fontSizes.md },
  steps: { gap: spacing.sm, marginTop: spacing.xs },
  step: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  dot: {
    width: 12,
    height: 12,
    borderRadius: radii.pill,
    backgroundColor: colors.border,
  },
  dotReached: { backgroundColor: colors.primary },
  stepLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  stepLabelReached: { fontFamily: fonts.bodyMedium, color: colors.text },
  incident: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.warning,
    marginTop: spacing.xs,
  },
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
  event: { paddingVertical: spacing.xs },
  eventLabel: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text },
  proof: { marginTop: spacing.xs, gap: spacing.xs },
  proofPhoto: { width: '100%', height: 180, borderRadius: radii.md, backgroundColor: colors.border },
  proofSignature: {
    width: '100%',
    height: 120,
    borderRadius: radii.md,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: colors.border,
  },
});
