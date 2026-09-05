import { useCallback, useEffect, useRef, useState } from 'react';
import { Image, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';

import { ApiError } from '../../../../src/api/client';
import { fetchParcelDetails, reportDelivered, reportOutcome, type StatusAction } from '../../../../src/api/deliveryman';
import { shareCurrentPosition } from '../../../../src/domain/location';
import { CameraDeniedError, takeDeliveryPhoto } from '../../../../src/domain/photo';
import { SignaturePad, type SignaturePadHandle } from '../../../../src/components/SignaturePad';
import type { ParcelDetails } from '../../../../src/api/types';
import { Button, Card, ChoiceGroup, ErrorText, Field, Muted, Title } from '../../../../src/components/ui';
import { colors } from '../../../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../../../src/theme/typography';
import { formatAmount, toAmount } from '../../../../src/domain/money';
import { t } from '../../../../src/i18n';

const ACTIONS: { value: StatusAction; label: string }[] = [
  { value: 'delivered', label: t('status.delivered') },
  { value: 'partial', label: t('status.partial') },
  { value: 'return', label: t('status.returned') },
];

export default function ParcelStatusScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const parcelId = Number(id);

  const [parcel, setParcel] = useState<ParcelDetails | null>(null);
  const [action, setAction] = useState<StatusAction | null>(null);
  const [collected, setCollected] = useState('');
  const [note, setNote] = useState('');
  const [photoUri, setPhotoUri] = useState<string | null>(null);
  const signatureRef = useRef<SignaturePadHandle>(null);
  const [hasSignature, setHasSignature] = useState(false);
  const [drawing, setDrawing] = useState(false);
  const [error, setError] = useState('');
  const [fieldError, setFieldError] = useState('');
  const [saving, setSaving] = useState(false);
  const [done, setDone] = useState('');

  const load = useCallback(async () => {
    if (!Number.isFinite(parcelId)) return;
    try {
      setParcel((await fetchParcelDetails(parcelId)).parcel);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, [parcelId]);

  useEffect(() => {
    void load();
  }, [load]);

  const expected = toAmount(parcel?.cash_collection);

  async function capture() {
    setError('');
    try {
      const uri = await takeDeliveryPhoto();
      if (uri) setPhotoUri(uri);
    } catch (e) {
      setError(e instanceof CameraDeniedError ? t('errors.cameraDenied') : t('errors.unexpected'));
    }
  }

  async function submit() {
    setError('');
    setFieldError('');
    if (!action) {
      setError(t('errors.requiredField'));
      return;
    }
    let cashCollection: number | undefined;
    if (action === 'partial') {
      const n = Number(collected.replace(/\s+/g, ''));
      if (!Number.isInteger(n) || n < 0) {
        setFieldError(t('errors.invalidAmount'));
        return;
      }
      cashCollection = n;
    }
    setSaving(true);
    try {
      if (action === 'delivered') {
        // Le PNG n'est produit qu'à l'envoi : inutile de capturer à chaque trait.
        const signatureUri = hasSignature ? await signatureRef.current?.capture() : null;
        await reportDelivered(parcelId, {
          note,
          photoUri: photoUri ?? undefined,
          signatureUri: signatureUri ?? undefined,
        });
      } else {
        await reportOutcome(parcelId, action, { cashCollection, note });
      }
      // Position au moment de la déclaration : utile au transporteur, jamais bloquante.
      void shareCurrentPosition().catch(() => undefined);
      setDone(
        action === 'delivered'
          ? t('status.successDelivered')
          : action === 'partial'
            ? t('status.successPartial')
            : t('status.successReturned'),
      );
      // Retour à la liste : les onglets se rechargent au focus.
      setTimeout(() => router.dismissTo('/(app)/(tabs)'), 600);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setSaving(false);
    }
  }

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView
        contentContainerStyle={styles.page}
        keyboardShouldPersistTaps="handled"
        // Pendant un tracé, le défilement rendrait la signature impossible.
        scrollEnabled={!drawing}
      >
        {parcel && (
          <Card>
            <Text style={styles.tracking}>{parcel.tracking_id}</Text>
            <Text style={styles.customer}>{parcel.customer_name}</Text>
            <View style={styles.expected}>
              <Text style={styles.expectedLabel}>{t('status.expected')}</Text>
              <Text style={styles.expectedValue}>{formatAmount(expected)}</Text>
            </View>
          </Card>
        )}

        <Card>
          <Title>{t('status.title')}</Title>
          <ChoiceGroup label="" options={ACTIONS} value={action} onChange={setAction} />

          {action === 'partial' && (
            <>
              <Field
                label={t('status.collected')}
                value={collected}
                onChangeText={setCollected}
                keyboardType="number-pad"
                placeholder={String(expected)}
                error={fieldError}
              />
              <Muted>{t('status.collectedHint')}</Muted>
            </>
          )}

          {action === 'delivered' && (
            <View style={styles.proof}>
              <Text style={styles.proofLabel}>{t('status.proof')}</Text>
              <Muted>{t('status.proofHint')}</Muted>
              {photoUri && <Image source={{ uri: photoUri }} style={styles.preview} resizeMode="cover" />}
              <View style={styles.proofActions}>
                <Button title={photoUri ? t('common.retakePhoto') : t('common.photo')} onPress={capture} />
                {photoUri && <Button title={t('common.removePhoto')} onPress={() => setPhotoUri(null)} />}
              </View>

              <Text style={styles.proofLabel}>{t('status.signature')}</Text>
              <Muted>{t('status.signatureHint')}</Muted>
              <SignaturePad ref={signatureRef} onChange={setHasSignature} onDrawingChange={setDrawing} />
              {hasSignature && (
                <Button title={t('common.clearSignature')} onPress={() => signatureRef.current?.clear()} />
              )}
            </View>
          )}

          <Field
            label={`${t('status.note')} (${t('common.optional')})`}
            value={note}
            onChangeText={setNote}
            placeholder={t('status.notePlaceholder')}
            multiline
          />

          <Muted>{t('status.warning')}</Muted>
          <ErrorText>{error}</ErrorText>
          {!!done && <Text style={styles.done}>{done}</Text>}
          <Button title={t('status.confirm')} onPress={submit} loading={saving} disabled={!!done} variant="accent" />
        </Card>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  page: { padding: spacing.md, gap: spacing.md },
  tracking: { fontFamily: fonts.numeric, fontSize: fontSizes.lg, color: colors.primary },
  customer: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.text },
  expected: { marginTop: spacing.sm },
  expectedLabel: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  expectedValue: { fontFamily: fonts.numeric, fontSize: fontSizes.xl, color: colors.accent },
  done: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.success },
  proof: { gap: spacing.xs },
  proofLabel: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.textMuted },
  preview: { width: '100%', height: 200, borderRadius: radii.md, backgroundColor: colors.border },
  proofActions: { gap: spacing.sm },
});
