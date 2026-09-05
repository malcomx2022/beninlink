import { useCallback, useEffect, useState } from 'react';
import { Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';

import { ApiError } from '../../../../src/api/client';
import { fetchParcelDetails } from '../../../../src/api/deliveryman';
import type { ParcelDetails, ParcelEvent } from '../../../../src/api/types';
import { Button, Card, ErrorText, Muted, Title } from '../../../../src/components/ui';
import { colors } from '../../../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../../../src/theme/typography';
import { formatAmount } from '../../../../src/domain/money';
import { TAB_STAGES, toMerchantStage } from '../../../../src/domain/parcelStatus';
import { stageLabel, t } from '../../../../src/i18n';

/** Coordonnées si le colis en porte, sinon recherche par adresse. */
function mapsUrl(parcel: ParcelDetails): string | null {
  if (parcel.customer_lat && parcel.customer_long) {
    return `https://www.google.com/maps/dir/?api=1&destination=${parcel.customer_lat},${parcel.customer_long}`;
  }
  if (parcel.customer_address) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(parcel.customer_address)}`;
  }
  return null;
}

export default function ParcelDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const [parcel, setParcel] = useState<ParcelDetails | null>(null);
  const [events, setEvents] = useState<ParcelEvent[]>([]);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setError('');
    const numericId = Number(id);
    if (!Number.isFinite(numericId)) {
      setError(t('errors.unexpected'));
      return;
    }
    try {
      const { parcel: p, events: e } = await fetchParcelDetails(numericId);
      setParcel(p);
      setEvents(e);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t('errors.unexpected'));
    }
  }, [id]);

  useEffect(() => {
    void load();
  }, [load]);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  const stage = parcel ? toMerchantStage(parcel.status) : null;
  // Le statut ne se change que sur une course encore en cours.
  const canChange = stage !== null && TAB_STAGES.ongoing.includes(stage);

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <ErrorText>{error}</ErrorText>

      {parcel && (
        <>
          <Card>
            <Text style={styles.tracking}>{parcel.tracking_id}</Text>
            {/* Le détail est brut (sans libellé) : on affiche l'étape déduite du code. */}
            <Text style={styles.status}>{stage ? stageLabel(stage) : '—'}</Text>
            <View style={styles.codBox}>
              <Text style={styles.codLabel}>{t('parcels.cod')}</Text>
              <Text style={styles.codValue}>{formatAmount(parcel.cash_collection)}</Text>
            </View>
          </Card>

          <Card>
            <Title>{t('parcels.recipient')}</Title>
            <Line label={t('parcels.name')} value={parcel.customer_name} />
            <Line label={t('auth.phone')} value={parcel.customer_phone ?? '—'} />
            <Line label={t('parcels.address')} value={parcel.customer_address ?? '—'} />
            <View style={styles.actions}>
              <Button
                title={t('common.call')}
                onPress={() => {
                  if (parcel.customer_phone) void Linking.openURL(`tel:${parcel.customer_phone.replace(/\s+/g, '')}`);
                  else setError(t('errors.noPhone'));
                }}
              />
              <Button
                title={t('common.route')}
                onPress={() => {
                  const url = mapsUrl(parcel);
                  if (url) void Linking.openURL(url);
                  else setError(t('errors.noAddress'));
                }}
              />
            </View>
          </Card>

          <Card>
            <Title>{t('parcels.merchant')}</Title>
            <Line label={t('parcels.merchant')} value={parcel.merchant?.business_name ?? '—'} />
            <Line label={t('auth.phone')} value={parcel.merchant?.user?.mobile ?? parcel.pickup_phone ?? '—'} />
            <Line label={t('parcels.shop')} value={parcel.merchant_shop?.name ?? '—'} />
            <Line
              label={t('parcels.pickupAddress')}
              value={parcel.pickup_address ?? parcel.merchant_shop?.address ?? parcel.merchant?.address ?? '—'}
            />
          </Card>

          <Card>
            <Title>{t('parcels.parcelInfo')}</Title>
            <Line label={t('parcels.tracking')} value={parcel.tracking_id} />
            <Line label={t('parcels.category')} value={parcel.delivery_category?.title ?? '—'} />
            <Line label={t('parcels.weight')} value={parcel.weight ? String(parcel.weight) : '—'} />
            <Line label={t('parcels.pickupDate')} value={parcel.pickup_date ?? '—'} />
            <Line label={t('parcels.deliveryDate')} value={parcel.delivery_date ?? '—'} />
            <Line label={t('parcels.fees')} value={formatAmount(parcel.delivery_charge)} />
            <Line label={t('parcels.codFee')} value={formatAmount(parcel.cod_amount)} />
            <Line label={t('parcels.vat')} value={formatAmount(parcel.vat_amount)} />
            {!!parcel.packaging && <Line label={t('parcels.packaging')} value={parcel.packaging.name} />}
            {!!parcel.note && <Line label={t('parcels.note')} value={parcel.note} />}
          </Card>

          {events.length > 0 && (
            <Card>
              <Title>{t('parcels.timeline')}</Title>
              {events.map((event) => (
                <View key={event.id} style={styles.event}>
                  <Text style={styles.eventLabel}>
                    {event.parcel_status !== null ? stageLabel(toMerchantStage(event.parcel_status)) : '—'}
                  </Text>
                  <Muted>{event.created_at ?? ''}</Muted>
                  {!!event.note && <Muted>{event.note}</Muted>}
                </View>
              ))}
            </Card>
          )}

          {canChange && (
            <Button
              title={t('parcels.changeStatus')}
              variant="accent"
              onPress={() => router.push({ pathname: '/(app)/parcel/[id]/status', params: { id: parcel.id } })}
            />
          )}
        </>
      )}
    </ScrollView>
  );
}

function Line({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.line}>
      <Text style={styles.lineLabel}>{label}</Text>
      <Text style={styles.lineValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  tracking: { fontFamily: fonts.numeric, fontSize: fontSizes.xl, color: colors.primary },
  status: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.info },
  codBox: {
    marginTop: spacing.sm,
    padding: spacing.md,
    borderRadius: radii.md,
    backgroundColor: colors.background,
    borderWidth: 1,
    borderColor: colors.border,
  },
  codLabel: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  codValue: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
  actions: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm },
  line: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  lineLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  lineValue: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text, flexShrink: 1, textAlign: 'right' },
  event: { gap: 2, paddingVertical: spacing.xs, borderBottomWidth: 1, borderBottomColor: colors.border },
  eventLabel: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text },
});
