import { useCallback, useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Link, useFocusEffect, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';

import { storage } from '../../src/api/session';
import { ApiError } from '../../src/api/client';
import { fetchRechargeStatus, initiateRecharge } from '../../src/api/fedapay';
import { fetchWalletHistory } from '../../src/api/wallet';
import type { WalletEntry } from '../../src/api/types';
import { useSession } from '../../src/session/SessionProvider';
import { Button, Card, ErrorText, Field, Muted, Title } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { formatAmount } from '../../src/domain/money';
import { t } from '../../src/i18n';

/**
 * Portefeuille prépayé et recharge Mobile Money.
 *
 * Le parcours suit strictement la règle du projet : l'app **n'accède jamais aux
 * clés FedaPay**. Elle demande au backend d'initier la transaction, ouvre la
 * page hébergée, puis **interroge le serveur** — elle ne déduit rien de la
 * fermeture du navigateur, qu'un utilisateur peut provoquer sans avoir payé.
 *
 * Choix d'implémentation : `expo-web-browser` plutôt qu'une WebView brute. Il
 * ouvre un navigateur intégré (Custom Tabs / SFSafariViewController) qui affiche
 * l'URL réelle — préférable pour un paiement — et fonctionne aussi sur la cible
 * web. L'intention du CLAUDE.md est respectée : ouvrir `payment_url`, rien de plus.
 */
export default function WalletScreen() {
  const { user, refresh } = useSession();
  /**
   * Montant pré-rempli quand on arrive ici depuis un refus pour solde
   * insuffisant : l'écran de création passe exactement ce qui manquait. Le
   * marchand reste libre de le modifier.
   */
  const { amount: montantDemande } = useLocalSearchParams<{ amount?: string }>();
  const [amount, setAmount] = useState(
    typeof montantDemande === 'string' && /^\d+$/.test(montantDemande) ? montantDemande : '',
  );
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [busy, setBusy] = useState(false);
  const [reference, setReference] = useState<string | null>(null);
  const [paymentUrl, setPaymentUrl] = useState<string | null>(null);
  const [trackingReady, setTrackingReady] = useState(false);
  const referenceKey = `beninlink.merchant.${user?.id}.recharge`;

  const balance = user?.merchant?.wallet_balance ?? 0;

  // Historique paginé par 10 ; depuis S78 le serveur dit s'il en reste (`page`).
  const [entries, setEntries] = useState<WalletEntry[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [historyError, setHistoryError] = useState('');

  const loadHistory = useCallback(async (pageToLoad: number) => {
    setHistoryError('');
    try {
      const result = await fetchWalletHistory(pageToLoad);
      setEntries((current) => (pageToLoad === 1 ? result.items : [...current, ...result.items]));
      setPage(pageToLoad);
      setHasMore(result.hasMore);
    } catch (e) {
      setHistoryError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useFocusEffect(useCallback(() => {
    void loadHistory(1);
    void refresh().catch(() => setError(t('errors.unexpected')));
  }, [loadHistory, refresh]));

  const verifyRecharge = useCallback(async (pendingReference: string) => {
    const result = await fetchRechargeStatus(pendingReference);
    if (result.status === 'approved') {
      await refresh();
      setInfo(t('wallet.rechargeApproved'));
    } else if (result.status === 'declined') {
      setInfo(t('wallet.rechargeDeclined'));
    } else if (result.status === 'canceled') {
      setInfo(t('wallet.rechargeCanceled'));
    } else {
      setInfo(t('wallet.rechargePending'));
    }
    if (['approved', 'declined', 'canceled'].includes(result.status)) {
      await storage.remove(referenceKey);
      setReference(null);
      setPaymentUrl(null);
    }
    await loadHistory(1);
  }, [loadHistory, refresh, referenceKey]);

  useEffect(() => {
    let active = true;
    setTrackingReady(false);
    void storage.get(referenceKey).then((saved) => {
      if (active) {
        // Tolère aussi la première forme, qui ne conservait que la référence.
        const payment = saved?.startsWith('{') ? JSON.parse(saved) : { reference: saved };
        if (payment.reference !== null && typeof payment.reference !== 'string') throw new Error('Invalid reference');
        setReference(payment.reference);
        setPaymentUrl(typeof payment.payment_url === 'string' ? payment.payment_url : null);
        setTrackingReady(true);
      }
    }).catch(() => {
      if (active) setError(t('errors.unexpected'));
    });
    return () => { active = false; };
  }, [referenceKey]);

  const checkRecharge = useCallback(async () => {
    if (!reference) return;
    setBusy(true);
    setError('');
    try {
      await verifyRecharge(reference);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setBusy(false);
    }
  }, [reference, verifyRecharge]);

  const resumePayment = useCallback(async () => {
    if (!reference || !paymentUrl) return;
    setBusy(true);
    setError('');
    try {
      await WebBrowser.openBrowserAsync(paymentUrl);
      await verifyRecharge(reference);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setBusy(false);
    }
  }, [reference, paymentUrl, verifyRecharge]);

  const recharge = useCallback(async () => {
    setError('');
    setInfo('');

    const value = Number(amount.replace(/\s/g, ''));
    if (!Number.isInteger(value) || value <= 0) {
      setError(t('wallet.invalidAmount'));
      return;
    }

    setBusy(true);
    try {
      const { reference: pendingReference, payment_url } = await initiateRecharge(value);
      setReference(pendingReference);
      setPaymentUrl(payment_url);
      await storage.set(referenceKey, JSON.stringify({ reference: pendingReference, payment_url }));

      // Ouvre la page FedaPay et attend sa fermeture. Le résultat renvoyé ne
      // dit RIEN du paiement : il indique seulement que l'utilisateur est revenu.
      await WebBrowser.openBrowserAsync(payment_url);

      // Seule autorité : le serveur, qui n'a crédité que sur webhook signé.
      await verifyRecharge(pendingReference);
      setAmount('');
      await loadHistory(1); // la recharge apparaît dans l'historique, quel que soit son statut
    } catch (e) {
      if (e instanceof ApiError) {
        // 503 = passerelle non configurée côté serveur : message explicite.
        setError(e.message);
      } else {
        setError(t('errors.unexpected'));
      }
    } finally {
      setBusy(false);
    }
  }, [amount, verifyRecharge, referenceKey, loadHistory]);

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Card>
        <Title>{t('wallet.balance')}</Title>
        <Text style={styles.balance}>{formatAmount(balance)}</Text>
        <Muted>{t('wallet.prepaidExplanation')}</Muted>
        <Link href="/(app)/wallet/withdraw" style={styles.link}>
          {t('wallet.withdrawLink')}
        </Link>
      </Card>

      <Card>
        <Title>{t('wallet.recharge')}</Title>
        <Field
          label={t('wallet.amountToAdd')}
          value={amount}
          onChangeText={setAmount}
          keyboardType="number-pad"
          placeholder="5000"
          editable={!busy}
        />
        <View style={styles.quick}>
          {[1000, 5000, 10000, 25000].map((preset) => (
            <Text
              key={preset}
              style={styles.preset}
              onPress={() => !busy && setAmount(String(preset))}
            >
              {formatAmount(preset, false)}
            </Text>
          ))}
        </View>

        <Muted>{t('wallet.operators')}</Muted>
        <ErrorText>{error}</ErrorText>
        {!!info && <Muted>{info}</Muted>}

        <Button
          title={t('wallet.rechargeAction')}
          onPress={recharge}
          loading={busy}
          disabled={!trackingReady || !!reference}
          variant="accent"
        />
        {!!reference && (
          <Button title={t('wallet.checkRecharge')} onPress={checkRecharge} loading={busy} />
        )}
        {!!reference && !!paymentUrl && (
          <Button title={t('wallet.resumePayment')} onPress={resumePayment} loading={busy} />
        )}
        {/* Rappel honnête : le crédit dépend de la confirmation de l'opérateur. */}
        <Muted>{t('wallet.confirmationNotice')}</Muted>
      </Card>

      <Card>
        <Title>{t('wallet.history')}</Title>
        <ErrorText>{historyError}</ErrorText>
        {entries.length === 0 && !historyError && <Muted>{t('wallet.historyEmpty')}</Muted>}
        {entries.map((entry) => (
          <View key={entry.id} style={styles.entry}>
            <View style={styles.entryTop}>
              <Text style={styles.entryAmount}>
                {entry.type === 2 ? '− ' : '+ '}
                {formatAmount(entry.amount)}
              </Text>
              <Text style={styles.entryStatus}>{entry.statusName}</Text>
            </View>
            <Muted>
              {[entry.typeName, entry.paymentMethodName, entry.transaction_id]
                .filter(Boolean)
                .join(' · ')}
            </Muted>
            <Muted>{entry.created_at ?? ''}</Muted>
          </View>
        ))}
        {hasMore && (
          <Text style={styles.link} onPress={() => void loadHistory(page + 1)}>
            {t('common.loadMore')}
          </Text>
        )}
      </Card>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  balance: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
  link: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    paddingVertical: spacing.xs,
  },
  entry: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.sm,
    gap: spacing.xs,
  },
  entryTop: { flexDirection: 'row', justifyContent: 'space-between' },
  entryAmount: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
  entryStatus: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.accentDark },
  quick: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  preset: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 999,
    paddingVertical: spacing.xs,
    paddingHorizontal: spacing.md,
    overflow: 'hidden',
  },
});
