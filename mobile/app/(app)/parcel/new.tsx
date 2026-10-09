import { useCallback, useEffect, useMemo, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useRouter } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { fetchCustomsReference } from '../../../src/api/customs';
import {
  createParcel,
  fetchParcelFormData,
  fetchQuote,
  walletShortfall,
  type WalletShortfall,
} from '../../../src/api/parcels';
import type { CustomsReference, ParcelFormData, ParcelQuote } from '../../../src/api/types';
import { Button, Card, ChoiceGroup, ErrorText, Field, Muted, Title } from '../../../src/components/ui';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../../src/theme/typography';
import { deliveryTypeId, deliveryTypeLabel } from '../../../src/domain/deliveryType';
import { formatAmount, formatRate } from '../../../src/domain/money';
import { zoneChoices } from '../../../src/domain/zoneChoices';
import { customsLevelColorName } from '../../../src/domain/customsLevel';
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
 *
 * Le prix reste néanmoins visible **avant** la création : `POST parcel/quote`
 * renvoie les montants qu'appliquera le serveur, sans rien enregistrer. L'app
 * les affiche tels quels.
 */
export default function NewParcelScreen() {
  const router = useRouter();
  const [form, setForm] = useState<ParcelFormData | null>(null);
  const [shopId, setShopId] = useState<number | null>(null);
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [typeId, setTypeId] = useState<number | null>(null);
  /**
   * Route du colis (**D4**) : zone obligatoire et délai facultatif (S91).
   */
  const [zoneId, setZoneId] = useState<number | null>(null);
  const [delayId, setDelayId] = useState<number | null>(null);
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
  /**
   * Refus pour solde insuffisant : le porte-monnaie prépayé ne couvre pas les
   * frais. Le backend l'oppose désormais à tous les chemins de création — le
   * colis n'est pas créé, et le solde ne descend plus sous zéro. On garde ce
   * qui manque pour proposer la recharge du bon montant.
   */
  const [shortfall, setShortfall] = useState<WalletShortfall | null>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [quoteResult, setQuote] = useState<ParcelQuote | null>(null);
  const [quotedKey, setQuotedKey] = useState('');
  const [quoting, setQuoting] = useState(false);
  /** Douane : référentiel des pays et catégories, et choix du marchand. */
  const [customs, setCustoms] = useState<CustomsReference | null>(null);
  const [country, setCountry] = useState<string | null>(null);
  const [goods, setGoods] = useState<string | null>(null);

  // La signature invalide le devis dès le rendu, avant même le prochain effet.
  const quoteKey = JSON.stringify([categoryId, typeId, values.cash_collection, values.weight, country, goods, zoneId, delayId]);
  const quote = quotedKey === quoteKey ? quoteResult : null;

  const set = (key: keyof typeof values) => (value: string) =>
    setValues((v) => ({ ...v, [key]: value }));

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [data, reference] = await Promise.all([
        fetchParcelFormData(),
        // Un référentiel vide (aucune règle) ne doit pas empêcher de créer un
        // colis : on l'ignore alors, l'écran reste purement domestique.
        fetchCustomsReference().catch(() => null),
      ]);
      setForm(data);
      setCustoms(reference);
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

  /**
   * Devis à chaque changement d'option ou de montant.
   *
   * Temporisé : on laisse la frappe retomber avant d'interroger le serveur. Le
   * `AbortController` annule le devis devenu obsolète — sans lui, une réponse
   * lente pourrait écraser une réponse plus récente.
   */
  useEffect(() => {
    setQuote(null);
    setQuoting(false);
    if (!categoryId || !typeId || !zoneId) {
      setQuote(null);
      return;
    }

    const controller = new AbortController();
    const timer = setTimeout(async () => {
      setQuoting(true);
      try {
        const result = await fetchQuote(
          {
            category_id: categoryId,
            delivery_type_id: typeId,
            cash_collection: Number(values.cash_collection.replace(/\s/g, '')) || 0,
            ...(values.weight ? { weight: values.weight } : {}),
            ...(country ? { destination_country: country } : {}),
            ...(goods ? { customs_category: goods } : {}),
            ...(zoneId ? { zone_id: zoneId } : {}),
            ...(delayId ? { delay_id: delayId } : {}),
          },
          controller.signal,
        );
        if (!controller.signal.aborted) {
          setQuote(result);
          setQuotedKey(quoteKey);
        }
      } catch {
        // Devis abandonné ou serveur indisponible : on n'affiche aucun montant
        // plutôt qu'un montant faux. Un devis courant est requis avant confirmation.
        if (!controller.signal.aborted) setQuote(null);
      } finally {
        if (!controller.signal.aborted) setQuoting(false);
      }
    }, 400);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [categoryId, typeId, values.cash_collection, values.weight, country, goods, zoneId, delayId, quoteKey]);

  const typeOptions = useMemo(() => {
    // `deliveryTypes` liste des interrupteurs de configuration : on ne garde que
    // ceux activés, et on traduit la clé en identifiant de l'énumération backend.
    return (form?.deliveryTypes ?? [])
      .filter((type) => String(type.value) === '1')
      .map((type) => ({ value: deliveryTypeId(type.key), label: deliveryTypeLabel(type.key) }))
      .filter((option): option is { value: number; label: string } => option.value !== null);
  }, [form]);

  /**
   * Zones proposées (**D4**) — une entrée par zone, rien d'autre.
   *
   * Depuis l'étape 6 (2026-09-07) la route est le seul axe de tarification :
   * `zone_id` est obligatoire côté serveur, et l'ancienne entrée « barème
   * hérité » (valeur 0) ne menait plus qu'à un refus (**S91 / M4**). Vide tant
   * que le transporteur n'a pas de zones — ce que `tarification-prete` interdit
   * désormais à tout déploiement (S86).
   */
  const zoneOptions = useMemo(() => zoneChoices(form?.zones ?? []), [form]);

  /** Délais et leur supplément — global, donc annoncé une fois par délai. */
  const delayOptions = useMemo(
    () =>
      (form?.delays ?? []).map((delay) => ({
        value: delay.id,
        label:
          Number(delay.surcharge) > 0
            ? `${delay.name} (+ ${formatAmount(delay.surcharge, false)})`
            : delay.name,
      })),
    [form],
  );

  /**
   * Zone d'export choisie sans pays de destination : le serveur refusera, on
   * le dit avant plutôt que de laisser le marchand buter sur l'envoi.
   */
  const exportSansPays = useMemo(() => {
    const zone = (form?.zones ?? []).find((z) => z.id === zoneId);

    return !!zone?.export && !country;
  }, [form, zoneId, country]);

  async function submit() {
    setError('');
    setFieldErrors({});
    setShortfall(null);
    if (!shopId || !categoryId || !typeId) {
      setError(t('parcels.chooseAllOptions'));
      return;
    }
    const cash = Number(values.cash_collection.replace(/\s/g, ''));
    if (!Number.isInteger(cash) || cash < 0) {
      setFieldErrors({ cash_collection: [t('parcels.invalidAmount')] });
      return;
    }

    if (!quote || quoting || quote.customs?.blocking) {
      setError(t('parcels.quoteHint'));
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
        ...(country ? { destination_country: country } : {}),
        ...(goods ? { customs_category: goods } : {}),
        ...(zoneId ? { zone_id: zoneId } : {}),
        ...(delayId ? { delay_id: delayId } : {}),
      });
      router.replace('/(app)/parcels');
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
        setFieldErrors(e.errors);
        setShortfall(walletShortfall(e));
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
          testID="colis-boutique"
          options={(form?.shops ?? []).map((shop) => ({ value: shop.id, label: shop.name }))}
          value={shopId}
          onChange={setShopId}
          error={fieldErrors.shop_id?.[0]}
        />

        <ChoiceGroup
          label={t('parcels.category')}
          testID="colis-categorie"
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
          testID="colis-type"
          options={typeOptions}
          value={typeId}
          onChange={setTypeId}
          error={fieldErrors.delivery_type_id?.[0]}
        />

        {/* Barème par zones (D4) : la zone est obligatoire, le délai la suit.
            Sans zone servie, les sélecteurs n'apparaissent pas et le serveur
            refusera la création — un transporteur sans zones ne facture rien. */}
        {!!zoneOptions.length && (
          <>
            <ChoiceGroup
              label={t('parcels.zone')}
              testID="colis-zone"
              options={zoneOptions}
              value={zoneId}
              onChange={setZoneId}
              error={fieldErrors.zone_id?.[0]}
            />

            {!!zoneId && !!delayOptions.length && (
              <ChoiceGroup
                label={t('parcels.delay')}
                options={delayOptions}
                value={delayId}
                onChange={setDelayId}
                error={fieldErrors.delay_id?.[0]}
              />
            )}

            {/* La zone d'export se facture au pays : sans destination choisie,
                le serveur refusera la création plutôt que d'inventer un prix. */}
            {exportSansPays && <Muted>{t('parcels.zoneNeedsCountry')}</Muted>}
          </>
        )}

        {/* Douane : listes servies par le backend (customs/reference). Le pays
            vide reste le cas normal — un colis domestique n'a rien à déclarer. */}
        {!!customs?.countries?.length && (
          <>
            <ChoiceGroup
              label={t('customs.destination')}
              options={[
                { value: '', label: t('customs.domestic') },
                ...customs.countries.map((c) => ({ value: c.code, label: c.name })),
              ]}
              value={country ?? ''}
              onChange={(value) => {
                setCountry(value === '' ? null : value);
                if (value === '') setGoods(null);
              }}
              error={fieldErrors.destination_country?.[0]}
            />

            {!!country && (
              <ChoiceGroup
                label={t('customs.goodsCategory')}
                options={customs.categories.map((c) => ({ value: c.slug, label: c.name }))}
                value={goods}
                onChange={setGoods}
                error={fieldErrors.customs_category?.[0]}
              />
            )}
          </>
        )}

        <Field
          label={t('parcels.name')}
          testID="colis-nom"
          value={values.customer_name}
          onChangeText={set('customer_name')}
          placeholder="Ex. Aïcha Kora"
          error={fieldErrors.customer_name?.[0]}
          editable={!saving}
        />
        <Field
          label={t('auth.phone')}
          testID="colis-telephone"
          value={values.customer_phone}
          onChangeText={set('customer_phone')}
          keyboardType="phone-pad"
          placeholder="+229 …"
          error={fieldErrors.customer_phone?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.address')}
          testID="colis-adresse"
          value={values.customer_address}
          onChangeText={set('customer_address')}
          placeholder="Ville, quartier"
          error={fieldErrors.customer_address?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.weight')}
          testID="colis-poids"
          value={values.weight}
          onChangeText={set('weight')}
          keyboardType="numeric"
          placeholder="1"
          error={fieldErrors.weight?.[0]}
          editable={!saving}
        />
        <Field
          label={t('parcels.cashCollection')}
          testID="colis-montant-cod"
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

        {/* Devis : ces montants viennent du serveur, ce sont ceux qui seront
            enregistrés. Rien n'est calculé ici. */}
        <Card>
          <Title>{t('parcels.quoteTitle')}</Title>
          {quote ? (
            <>
              <QuoteLine label={t('invoices.fees')} value={quote.delivery_charge} />
              <QuoteLine
                label={`${t('parcels.codFee')} (${formatRate(quote.cod_charge)})`}
                value={quote.cod_amount}
              />
              <QuoteLine
                label={`${t('invoices.vat')} (${formatRate(quote.vat)})`}
                value={quote.vat_amount}
              />
              <QuoteLine label={t('parcels.totalCharges')} value={quote.total_payable_charges} />
              <QuoteLine
                label={t('parcels.currentPayable')}
                value={quote.current_payable}
                highlight
              />
            </>
          ) : (
            <Muted>{quoting ? t('parcels.quotePending') : t('parcels.quoteHint')}</Muted>
          )}
          <Muted>{t('parcels.amountsComputedByServer')}</Muted>

          {/* Règle douanière renvoyée par le même devis. Le serveur refusera la
              création tant qu'un blocage s'applique : l'annoncer ici évite au
              marchand de remplir le reste pour rien. */}
          {!!quote?.customs && (
            <View
              style={[
                styles.customs,
                { borderColor: colors[customsLevelColorName(quote.customs.level)] },
              ]}
            >
              <Text
                style={[
                  styles.customsLevel,
                  { color: colors[customsLevelColorName(quote.customs.level)] },
                ]}
              >
                {quote.customs.blocking ? t('customs.blockingTitle') : quote.customs.level_name}
              </Text>
              <Text style={styles.customsMessage}>{quote.customs.message}</Text>
              {!!quote.customs.required_document && (
                <Text style={styles.customsDocument}>
                  {t('customs.requiredDocument')} : {quote.customs.required_document}
                </Text>
              )}
            </View>
          )}
        </Card>

        <ErrorText>{error}</ErrorText>
        {!!shortfall && (
          <Button
            title={`${t('parcels.rechargeToContinue')} ${formatAmount(shortfall.missing)}`}
            onPress={() =>
              router.push({
                pathname: '/(app)/wallet',
                params: { amount: String(shortfall.missing) },
              })
            }
          />
        )}
        <Button
          title={t('parcels.create')}
          onPress={submit}
          loading={saving}
          disabled={!quote || quoting || quote.customs?.blocking === true}
          variant="accent"
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function QuoteLine({
  label,
  value,
  highlight,
}: {
  label: string;
  value: unknown;
  highlight?: boolean;
}) {
  return (
    <View style={styles.quoteLine}>
      <Text style={styles.quoteLabel}>{label}</Text>
      <Text style={[styles.quoteValue, highlight && styles.quoteValueHighlight]}>
        {formatAmount(value)}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  page: { padding: spacing.md, gap: spacing.md },
  quoteLine: { flexDirection: 'row', justifyContent: 'space-between' },
  quoteLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  quoteValue: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.text },
  quoteValueHighlight: { color: colors.accent, fontSize: fontSizes.md },
  customs: { borderWidth: 1, borderRadius: radii.md, padding: spacing.sm, gap: spacing.xs },
  customsLevel: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm },
  customsMessage: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  customsDocument: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.textMuted },
});
