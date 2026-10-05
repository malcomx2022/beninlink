import { useCallback, useEffect, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';

import { ApiError } from '../../src/api/client';
import {
  fetchBalanceDetails,
  fetchInvoiceDetails,
  fetchInvoicePdfLink,
  fetchInvoices,
} from '../../src/api/merchant';
import type { BalanceDetails, Invoice, InvoiceDetails } from '../../src/api/types';
import { Button, Card, ErrorText, Muted, Title } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../src/theme/typography';
import { formatAmount } from '../../src/domain/money';
import { t } from '../../src/i18n';

/**
 * Factures = **relevés de règlement** (mobile/CLAUDE.md) : encaissé COD − frais
 * − TVA = net à reverser.
 *
 * Deux niveaux, deux sources :
 *   • en tête, le relevé **en cours** (`dashboard/balance-details`), calculé à la
 *     volée sur les colis livrés pas encore réglés ;
 *   • en dessous, les relevés **déjà émis** (`invoice-list/index`), dont la
 *     ventilation arrive à la demande (`invoice-details/{id}`).
 *
 * La ventilation se déplie sur place plutôt que dans un écran dédié : elle tient
 * en six lignes et l'aller-retour de navigation n'apporterait rien.
 *
 * Export PDF (chantier 4 de web/) : l'app demande un **lien signé** valable
 * 15 minutes puis l'ouvre dans le navigateur — elle ne peut pas joindre son
 * jeton Bearer à un navigateur, la signature en tient lieu. Le relevé porte
 * les mentions légales (IFU, RCCM) des deux parties.
 */
export default function InvoicesScreen() {
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [balance, setBalance] = useState<BalanceDetails | null>(null);
  /** Ventilations déjà chargées, par identifiant de facture. */
  const [details, setDetails] = useState<Record<number, InvoiceDetails>>({});
  const [openId, setOpenId] = useState<number | null>(null);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  /** Dernière page reçue, et s'il en reste. Le backend n'expose pas ses compteurs. */
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  /** Relevé dont le PDF est en cours d'ouverture. */
  const [pdfBusyId, setPdfBusyId] = useState<number | null>(null);

  const openPdf = useCallback(async (id: number) => {
    setError('');
    setPdfBusyId(id);
    try {
      const url = await fetchInvoicePdfLink(id);
      await WebBrowser.openBrowserAsync(url);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setPdfBusyId(null);
    }
  }, []);

  const load = useCallback(async () => {
    setError('');
    try {
      const [first, b] = await Promise.all([fetchInvoices(1), fetchBalanceDetails()]);
      setInvoices(first.items);
      setBalance(b);
      setPage(1);
      // S78 : le serveur dit où finit la liste ; repli sur « page pleine » sinon.
      setHasMore(first.hasMore);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  const loadMore = useCallback(async () => {
    if (!hasMore || loadingMore || refreshing) return;

    setLoadingMore(true);
    try {
      const next = await fetchInvoices(page + 1);
      setInvoices((current) => [...current, ...next.items]);
      setPage((p) => p + 1);
      setHasMore(next.hasMore);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
      setHasMore(false);
    } finally {
      setLoadingMore(false);
    }
  }, [hasMore, loadingMore, page, refreshing]);

  useEffect(() => {
    void load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    // Les ventilations dépliées sont invalidées : rechargées au besoin.
    setDetails({});
    await load();
    setRefreshing(false);
  }, [load]);

  const toggle = useCallback(
    async (id: number) => {
      if (openId === id) {
        setOpenId(null);
        return;
      }
      setOpenId(id);
      if (details[id]) return; // déjà chargée

      try {
        const detail = await fetchInvoiceDetails(id);
        setDetails((d) => ({ ...d, [id]: detail }));
      } catch (e) {
        setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
      }
    },
    [details, openId],
  );

  return (
    <FlatList
      data={invoices}
      keyExtractor={(invoice) => String(invoice.id)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      onEndReached={() => void loadMore()}
      onEndReachedThreshold={0.4}
      ListHeaderComponent={
        <View style={styles.header}>
          <ErrorText>{error}</ErrorText>

          {/* Relevé en cours : le chiffre qui intéresse le marchand aujourd'hui. */}
          <Card>
            <Title>{t('invoices.currentStatement')}</Title>
            <Text style={styles.net}>{formatAmount(balance?.available_balance)}</Text>
            <Muted>{t('invoices.netPayable')}</Muted>
            <Line label={t('invoices.codCollected')} value={balance?.amount_delivered} />
            <Line label={t('invoices.fees')} value={balance?.payable_delivery_charge} negative />
            <Line label={t('invoices.vat')} value={balance?.vat_amount} negative />
            <Muted>
              {t('invoices.totalParcels')} : {balance?.clearable_parcels ?? 0}
            </Muted>
          </Card>

          <Title>{t('invoices.issued')}</Title>
          {invoices.length > 0 && <Muted>{t('invoices.tapForDetail')}</Muted>}
        </View>
      }
      ListEmptyComponent={
        <View style={styles.empty}>
          <Muted>{t('invoices.empty')}</Muted>
        </View>
      }
      ListFooterComponent={
        <View style={styles.footer}>
          {loadingMore && <Muted>{t('common.loading')}</Muted>}
          <Muted>{t('invoices.exportNotice')}</Muted>
        </View>
      }
      renderItem={({ item }) => {
        const open = openId === item.id;
        const detail = details[item.id];
        return (
          <Pressable
            accessibilityRole="button"
            onPress={() => void toggle(item.id)}
            style={styles.row}
          >
            <View style={styles.rowTop}>
              <Text style={styles.invoiceId}>{item.invoice_id}</Text>
              <Text style={styles.amount}>{formatAmount(item.amount)}</Text>
            </View>
            <View style={styles.rowTop}>
              <Muted>{item.invoice_date ?? ''}</Muted>
              <Text style={styles.badge}>{item.status ?? ''}</Text>
            </View>

            {open &&
              (detail ? (
                <View style={styles.detail}>
                  <Line label={t('invoices.codCollected')} value={detail.total_deliverd_amount} />
                  <Line label={t('invoices.fees')} value={detail.delivery_charge} negative />
                  <Line label={t('parcels.codFee')} value={detail.cod_amount} negative />
                  <Line label={t('invoices.returnFees')} value={detail.total_return_fee} negative />
                  <Line label={t('invoices.netPayable')} value={detail.payable_amount} strong />
                  <Muted>
                    {t('invoices.totalParcels')} : {detail.total_parcels}
                  </Muted>
                  <Button
                    title={t('invoices.downloadPdf')}
                    onPress={() => void openPdf(item.id)}
                    loading={pdfBusyId === item.id}
                  />
                </View>
              ) : (
                <Muted>{t('common.loading')}</Muted>
              ))}
          </Pressable>
        );
      }}
    />
  );
}

function Line({
  label,
  value,
  negative,
  strong,
}: {
  label: string;
  value: unknown;
  negative?: boolean;
  strong?: boolean;
}) {
  return (
    <View style={styles.line}>
      <Text style={styles.lineLabel}>{label}</Text>
      <Text style={[styles.lineValue, strong && styles.lineValueStrong]}>
        {negative ? '− ' : ''}
        {formatAmount(value)}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  list: { padding: spacing.md, gap: spacing.sm, backgroundColor: colors.background, flexGrow: 1 },
  header: { gap: spacing.sm, marginBottom: spacing.xs },
  footer: { paddingTop: spacing.md },
  empty: { padding: spacing.xl, alignItems: 'center' },
  net: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
  row: {
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: spacing.xs,
  },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  invoiceId: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.text },
  amount: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
  badge: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.accentDark },
  detail: {
    gap: spacing.xs,
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  line: { flexDirection: 'row', justifyContent: 'space-between' },
  lineLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  lineValue: { fontFamily: fonts.numeric, fontSize: fontSizes.sm, color: colors.text },
  lineValueStrong: { color: colors.accent },
});
