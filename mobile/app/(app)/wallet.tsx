import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import * as WebBrowser from 'expo-web-browser';

import { ApiError } from '../../src/api/client';
import { fetchRechargeStatus, initiateRecharge } from '../../src/api/fedapay';
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
  const [amount, setAmount] = useState('');
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [busy, setBusy] = useState(false);

  const balance = user?.merchant?.wallet_balance ?? 0;

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
      const { reference, payment_url } = await initiateRecharge(value);

      // Ouvre la page FedaPay et attend sa fermeture. Le résultat renvoyé ne
      // dit RIEN du paiement : il indique seulement que l'utilisateur est revenu.
      await WebBrowser.openBrowserAsync(payment_url);

      // Seule autorité : le serveur, qui n'a crédité que sur webhook signé.
      const status = await fetchRechargeStatus(reference);
      if (status.status === 'approved') {
        setInfo(t('wallet.rechargeApproved'));
        await refresh(); // recharge le profil pour afficher le nouveau solde
      } else {
        setInfo(t('wallet.rechargePending'));
      }
      setAmount('');
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
  }, [amount, refresh]);

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Card>
        <Title>{t('wallet.balance')}</Title>
        <Text style={styles.balance}>{formatAmount(balance)}</Text>
        <Muted>{t('wallet.prepaidExplanation')}</Muted>
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
          variant="accent"
        />
        {/* Rappel honnête : le crédit dépend de la confirmation de l'opérateur. */}
        <Muted>{t('wallet.confirmationNotice')}</Muted>
      </Card>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.md, gap: spacing.md },
  balance: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
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
