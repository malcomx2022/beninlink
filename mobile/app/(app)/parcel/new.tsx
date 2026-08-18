import { useCallback, useEffect, useMemo, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { createParcel, fetchParcelFormData } from '../../../src/api/parcels';
import type { ParcelFormData } from '../../../src/api/types';
import { Button, ChoiceGroup, ErrorText, Field, Muted } from '../../../src/components/ui';
import { spacing } from '../../../src/theme/typography';
import { deliveryTypeId, deliveryTypeLabel } from '../../../src/domain/deliveryType';
import { t } from '../../../src/i18n';

/**
 * Création d'un colis.
 *
 * **Aucun montant n'est calculé ici.** Depuis la correction de S2, le serveur
 * établit frais de livraison, frais COD, TVA, total et net à reverser à partir
 * du barème et des taux du marchand. L'app ne transmet que des choix et le
 * montant à encaisser ; les montants s'affichent ensuite sur le détail du colis.
 *
 * Reproduire le calcul ici — comme le faisait l'app Flutter dépréciée —
 * dupliquerait le barème et rouvrirait la faille.
 */
export default function NewParcelScreen() {
  const router = useRouter();
  const [form, setForm] = useState<ParcelFormData | null>(null);
  const [shopId, setShopId] = useState<number | null>(null);
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [typeId, setTypeId] = useState<number | null>(null);
  const [values, setValues] = useState({
    customer_name: '',
    customer_phone: '',
    customer_address: '',
    cash_collection: '',
    selling_price: '',
    weight: '',
    invoice_no: '',
  });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  const set = (key: keyof typeof values) => (value: string) =>
    setValues((v) => ({ ...v, [key]: value }));

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchParcelFormData();
      setForm(data);
      // Présélection quand il n'y a qu'un choix possible : autant d'étapes en moins.
      const shops = data?.shops ?? [];
      if (shops.length === 1 && shops[0]) setShopId(shops[0].id);
      const categories = Object.values(data?.deliveryCategories ?? {});
      if (categories.length === 1 && categories[0]) setCategoryId(categories[0].id);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const typeOptions = useMemo(() => {
    // `deliveryTypes` liste des interrupteurs de configuration : on ne garde que
    // ceux activés, et on traduit la clé en identifiant de l'énumération backend.
    return (form?.deliveryTypes ?? [])
      .filter((type) => String(type.value) === '1')
      .map((type) => ({ value: deliveryTypeId(type.key), label: deliveryTypeLabel(type.key) }))
      .filter((option): option is { value: number; label: string } => option.value !== null);
  }, [form]);

  async function submit() {
    setError('');
    setFieldErrors({});
    if (!shopId || !categoryId || !typeId) {
      setError(t('parcels.chooseAllOptions'));
      return;
    }
    const cash = Number(values.cash_collection.replace(/\s/g, ''));
    if (!Number.isFinite(cash) || cash < 0) {
      setFieldErrors({ cash_collection: [t('parcels.invalidAmount')] });
      return;
    }

    setSaving(true);
    try {
      await createParcel({
        shop_id: shopId,
        category_id: categoryId,
        delivery_type_id: typeId,
        customer_name: values.customer_name.trim(),
        customer_phone: values.customer_phone.trim(),
        customer_address: values.customer_address.trim(),
        cash_collection: cash,
        ...(values.selling_price ? { selling_price: Number(values.selling_price) } : {}),
        ...(values.weight ? { weight: values.weight } : {}),
        ...(values.invoice_no ? { invoice_no: values.invoice_no.trim() } : {}),
      });
      router.replace('/(app)/parcels');
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

  if (loading) {
    return (
      <ScrollView contentContainerStyle={styles.page}>
        <Muted>{t('common.loading')}</Muted>
      </ScrollView>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <ChoiceGroup
          label={t('parcels.shop')}
          options={(form?.shops ?? []).map((shop) => ({ value: shop.id, label: shop.name }))}
          value={shopId}
          onChange={setShopId}
          error={fieldErrors.shop_id?.[0]}
        />

        <ChoiceGroup
          label={t('parcels.category')}
          options={Object.values(form?.deliveryCategories ?? {}).map((category) => ({
            value: category.id,
            label: category.title,
          }))}
          value={categoryId}
          onChange={setCategoryId}
          error={fieldErrors.category_id?.[0]}
        />

        <ChoiceGroup
          label={t('parcels.deliveryType')}
          options={typeOptions}
          value={typeId}
          onChange={setTypeId}
          error={fieldErrors.delivery_type_id?.[0]}
        />

        <Field
          label={t('parcels.name')}
          value={values.customer_name}
          onChangeText={set('customer_name')}
          placeholder="Ex. Aïcha Kora"
          error={fieldErrors.customer_name?.[0]}
          editable={!saving}
        />
        <Field
          label={t('auth.phone')}
          value={values.customer_phone}
          onChangeText={set('customer_phone')}
          keyboardType="phone-pad"
          placeholder="+229 …"
          error={fieldErrors.customer_phone?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.address')}
          value={values.customer_address}
          onChangeText={set('customer_address')}
          placeholder="Ville, quartier"
          error={fieldErrors.customer_address?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.weight')}
          value={values.weight}
          onChangeText={set('weight')}
          keyboardType="numeric"
          placeholder="1"
          error={fieldErrors.weight?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.cashCollection')}
          value={values.cash_collection}
          onChangeText={set('cash_collection')}
          keyboardType="numeric"
          placeholder="12000"
          error={fieldErrors.cash_collection?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.sellingPrice')}
          value={values.selling_price}
          onChangeText={set('selling_price')}
          keyboardType="numeric"
          placeholder="10000"
          error={fieldErrors.selling_price?.[0]}
          editable={!saving}
        />
        <Field
          label={`${t('parcels.invoiceNo')} (${t('common.optional')})`}
          value={values.invoice_no}
          onChangeText={set('invoice_no')}
          placeholder="Réf. interne"
          error={fieldErrors.invoice_no?.[0]}
          editable={!saving}
        />

        {/* Les montants ne sont pas affichés avant l'envoi : ils appartiennent au
            serveur. Les annoncer ici supposerait de les recalculer. */}
        <Muted>{t('parcels.amountsComputedByServer')}</Muted>

        <ErrorText>{error}</ErrorText>
        <Button title={t('parcels.create')} onPress={submit} loading={saving} variant="accent" />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  page: { padding: spacing.md, gap: spacing.md },
});
