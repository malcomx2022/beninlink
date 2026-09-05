import { useCallback, useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';

import { ApiError } from '../../../../src/api/client';
import { fetchParcelDetails, reportOutcome, type StatusAction } from '../../../../src/api/deliveryman';
import type { ParcelDetails } from '../../../../src/api/types';
import { Button, Card, ChoiceGroup, ErrorText, Field, Muted, Title } from '../../../../src/components/ui';
import { colors } from '../../../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../../../src/theme/typography';
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
      await reportOutcome(parcelId, action, { cashCollection, note });
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
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
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
});
