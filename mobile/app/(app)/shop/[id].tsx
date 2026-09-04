import { useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { useLocalSearchParams, useNavigation, useRouter } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { createShop, deleteShop, fetchShop, updateShop } from '../../../src/api/shops';
import { Button, ErrorText, Field, Muted } from '../../../src/components/ui';
import { spacing } from '../../../src/theme/typography';
import { t } from '../../../src/i18n';

/**
 * Création (`shop/new`) et modification (`shop/{id}`) d'une boutique.
 *
 * La suppression demande une confirmation **dans l'écran** (deux touches),
 * plutôt qu'une boîte de dialogue native : `Alert.alert` n'a pas de boutons
 * sur la cible web, et le geste reste identique partout.
 */
export default function ShopFormScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const navigation = useNavigation();
  const isNew = id === 'new';
  const shopId = isNew ? null : Number(id);

  const [values, setValues] = useState({ name: '', contact_no: '', address: '' });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [loading, setLoading] = useState(!isNew);
  const [saving, setSaving] = useState(false);
  const [confirmingDelete, setConfirmingDelete] = useState(false);

  const set = (key: keyof typeof values) => (value: string) =>
    setValues((v) => ({ ...v, [key]: value }));

  useEffect(() => {
    navigation.setOptions({ title: isNew ? t('shops.newShop') : t('shops.editShop') });
  }, [navigation, isNew]);

  useEffect(() => {
    if (shopId === null || !Number.isFinite(shopId)) return;
    let cancelled = false;
    fetchShop(shopId)
      .then((shop) => {
        if (cancelled) return;
        setValues({
          name: shop.name ?? '',
          contact_no: shop.contact_no ?? '',
          address: shop.address ?? '',
        });
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [shopId]);

  async function save() {
    setError('');
    setInfo('');
    setFieldErrors({});
    const payload = {
      name: values.name.trim(),
      contact_no: values.contact_no.replace(/\s/g, ''),
      address: values.address.trim(),
    };
    if (!payload.name || !payload.contact_no || !payload.address) {
      setError(t('errors.requiredField'));
      return;
    }
    setSaving(true);
    try {
      if (shopId === null) await createShop(payload);
      else await updateShop(shopId, payload);
      router.back();
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
        setFieldErrors(e.errors);
      } else {
        setError(t('errors.unexpected'));
      }
    } finally {
      setSaving(false);
    }
  }

  async function remove() {
    if (shopId === null) return;
    setError('');
    setSaving(true);
    try {
      await deleteShop(shopId);
      router.back();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
      setConfirmingDelete(false);
    } finally {
      setSaving(false);
    }
  }

  const busy = loading || saving;

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <Field
          label={t('shops.name')}
          value={values.name}
          onChangeText={set('name')}
          placeholder="Ex. Boutique Ganhi"
          error={fieldErrors.name?.[0]}
          editable={!busy}
        />
        <Field
          label={t('shops.phone')}
          value={values.contact_no}
          onChangeText={set('contact_no')}
          keyboardType="phone-pad"
          placeholder="22997000000"
          error={fieldErrors.contact_no?.[0]}
          editable={!busy}
        />
        <Muted>{t('shops.phoneHint')}</Muted>
        <Field
          label={t('shops.address')}
          value={values.address}
          onChangeText={set('address')}
          placeholder="Cotonou, Ganhi, rue 123"
          error={fieldErrors.address?.[0]}
          editable={!busy}
        />

        <ErrorText>{error}</ErrorText>
        {!!info && <Muted>{info}</Muted>}
        <Button title={t('common.save')} onPress={save} loading={saving} disabled={loading} />

        {shopId !== null && !confirmingDelete && (
          <Button
            title={t('common.delete')}
            onPress={() => setConfirmingDelete(true)}
            disabled={busy}
          />
        )}
        {shopId !== null && confirmingDelete && (
          <View style={styles.confirm}>
            <Muted>{t('shops.confirmDelete')}</Muted>
            <Button title={t('common.confirm')} onPress={remove} loading={saving} variant="accent" />
            <Button
              title={t('common.cancel')}
              onPress={() => setConfirmingDelete(false)}
              disabled={saving}
            />
          </View>
        )}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  page: { padding: spacing.lg, gap: spacing.md },
  confirm: { gap: spacing.sm },
});
