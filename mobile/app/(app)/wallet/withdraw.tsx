import { useCallback, useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../../src/api/client';
import {
  MOBILE_MONEY_OPERATORS,
  createMobileMoneyAccount,
  createPaymentRequest,
  fetchPaymentAccounts,
  fetchPaymentRequests,
  type MobileMoneyOperator,
} from '../../../src/api/wallet';
import type { PaymentAccount, PaymentRequest } from '../../../src/api/types';
import { useSession } from '../../../src/session/SessionProvider';
import { Button, Card, ChoiceGroup, ErrorText, Field, Muted, Title } from '../../../src/components/ui';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../../src/theme/typography';
import { formatAmount, toAmount } from '../../../src/domain/money';
import { t } from '../../../src/i18n';

/** Libellé d'un compte de règlement : opérateur ou banque, puis numéro. */
function accountLabel(account: PaymentAccount): string {
  const provider = account.mobile_company ?? account.bank_name ?? account.paymentMethodName ?? '';
  const number = account.mobile_no ?? account.account_no ?? '';
  return [provider, number].filter(Boolean).join(' · ');
}

/**
 * Demande de retrait du **net à reverser**.
 *
 * Trois blocs : le montant disponible (`merchant.current_balance`, que le
 * backend compare lui aussi), le compte de règlement (avec ajout d'un compte
 * Mobile Money si le marchand n'en a aucun), et la liste des demandes déjà
 * faites avec leur statut. Le backend refuse un compte qui n'appartient pas
 * au marchand et un montant supérieur au disponible ; l'écran anticipe le
 * second cas pour éviter un aller-retour, sans en faire la règle.
 */
export default function WithdrawScreen() {
  const { user, refresh } = useSession();
  const available = toAmount(user?.merchant?.current_balance);

  const [accounts, setAccounts] = useState<PaymentAccount[]>([]);
  const [requests, setRequests] = useState<PaymentRequest[]>([]);
  // Compte choisi ; à défaut, le premier de la liste (dérivé, pas écrit dans `load()`).
  const [accountId, setAccountId] = useState<number | null>(null);
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [busy, setBusy] = useState(false);

  // Ajout d'un compte Mobile Money : imposé tant qu'aucun compte n'existe,
  // sinon à la demande. Dérivé plutôt que stocké, pour ne pas le recalculer
  // dans `load()`.
  const [addingAccount, setAddingAccount] = useState(false);
  const [holder, setHolder] = useState('');
  const [operator, setOperator] = useState<MobileMoneyOperator | null>(null);
  const [mobileNo, setMobileNo] = useState('');
  const [accountErrors, setAccountErrors] = useState<Record<string, string[]>>({});
  const [accountError, setAccountError] = useState('');

  const showAccountForm = addingAccount || accounts.length === 0;
  const selectedAccountId = accountId ?? accounts[0]?.id ?? null;

  const load = useCallback(async () => {
    setError('');
    try {
      const [a, r] = await Promise.all([fetchPaymentAccounts(), fetchPaymentRequests()]);
      setAccounts(a);
      setRequests(r);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  async function addAccount() {
    setAccountError('');
    setAccountErrors({});
    const number = mobileNo.replace(/\s/g, '');
    if (!holder.trim() || !operator || !number) {
      setAccountError(t('errors.requiredField'));
      return;
    }
    setBusy(true);
    try {
      await createMobileMoneyAccount({ holder_name: holder.trim(), operator, mobile_no: number });
      setInfo(t('wallet.accountAdded'));
      setHolder('');
      setOperator(null);
      setMobileNo('');
      setAddingAccount(false);
      await load();
    } catch (e) {
      if (e instanceof ApiError) {
        setAccountError(e.message);
        setAccountErrors(e.errors);
      } else {
        setAccountError(t('errors.unexpected'));
      }
    } finally {
      setBusy(false);
    }
  }

  async function submit() {
    setError('');
    setInfo('');
    const value = Number(amount.replace(/\s/g, ''));
    if (!Number.isInteger(value) || value <= 0) {
      setError(t('wallet.invalidAmount'));
      return;
    }
    if (value > available) {
      setError(t('wallet.withdrawTooMuch'));
      return;
    }
    if (selectedAccountId === null) {
      setError(t('wallet.chooseAccount'));
      return;
    }
    setBusy(true);
    try {
      await createPaymentRequest({
        amount: value,
        merchant_account: selectedAccountId,
        description: description.trim() || undefined,
      });
      setInfo(t('wallet.requestSent'));
      setAmount('');
      setDescription('');
      await Promise.all([load(), refresh()]);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <Card>
          <Title>{t('wallet.availableToWithdraw')}</Title>
          <Text style={styles.available}>{formatAmount(available)}</Text>
          <Muted>{t('wallet.withdrawIntro')}</Muted>
        </Card>

        {showAccountForm ? (
          <Card>
            <Title>{t('wallet.addAccount')}</Title>
            {accounts.length === 0 && <Muted>{t('wallet.noAccount')}</Muted>}
            <Field
              label={t('wallet.holderName')}
              value={holder}
              onChangeText={setHolder}
              error={accountErrors.mobile_holder_name?.[0]}
              editable={!busy}
            />
            <ChoiceGroup
              label={t('wallet.operator')}
              options={MOBILE_MONEY_OPERATORS.map((o) => ({ value: o, label: o }))}
              value={operator}
              onChange={setOperator}
              error={accountErrors.mobile_company?.[0]}
            />
            <Field
              label={t('wallet.mobileNo')}
              value={mobileNo}
              onChangeText={setMobileNo}
              keyboardType="phone-pad"
              placeholder="22997000000"
              error={accountErrors.mobile_no?.[0]}
              editable={!busy}
            />
            <Muted>{t('shops.phoneHint')}</Muted>
            <ErrorText>{accountError}</ErrorText>
            <Button title={t('wallet.addAccountAction')} onPress={addAccount} loading={busy} />
            {accounts.length > 0 && (
              <Button
                title={t('common.cancel')}
                onPress={() => setAddingAccount(false)}
                disabled={busy}
              />
            )}
          </Card>
        ) : (
          <Card>
            <Title>{t('wallet.withdrawTitle')}</Title>
            <ChoiceGroup
              label={t('wallet.withdrawAccount')}
              options={accounts.map((a) => ({ value: a.id, label: accountLabel(a) }))}
              value={selectedAccountId}
              onChange={setAccountId}
            />
            <Text style={styles.addLink} onPress={() => !busy && setAddingAccount(true)}>
              {t('wallet.addAccount')}
            </Text>
            <Field
              label={t('wallet.withdrawAmount')}
              value={amount}
              onChangeText={setAmount}
              keyboardType="number-pad"
              placeholder="10000"
              editable={!busy}
            />
            <Field
              label={t('wallet.withdrawDescription')}
              value={description}
              onChangeText={setDescription}
              editable={!busy}
            />
            <ErrorText>{error}</ErrorText>
            {!!info && <Muted>{info}</Muted>}
            <Button
              title={t('wallet.withdrawAction')}
              onPress={submit}
              loading={busy}
              variant="accent"
            />
          </Card>
        )}

        <Card>
          <Title>{t('wallet.requests')}</Title>
          {requests.length === 0 && <Muted>{t('wallet.requestsEmpty')}</Muted>}
          {requests.map((r) => (
            <View key={r.id} style={styles.request}>
              <View style={styles.requestTop}>
                <Text style={styles.requestAmount}>{formatAmount(r.amount)}</Text>
                <Text style={styles.requestStatus}>{r.statusName ?? ''}</Text>
              </View>
              <Muted>
                {[r.mobile_company ?? r.bank_name, r.mobile_no ?? r.account_no]
                  .filter(Boolean)
                  .join(' · ')}
              </Muted>
              <Muted>{r.request_date ?? ''}</Muted>
            </View>
          ))}
        </Card>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  page: { padding: spacing.md, gap: spacing.md },
  available: { fontFamily: fonts.numeric, fontSize: fontSizes.display, color: colors.accent },
  addLink: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    paddingVertical: spacing.xs,
  },
  request: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.sm,
    gap: spacing.xs,
  },
  requestTop: { flexDirection: 'row', justifyContent: 'space-between' },
  requestAmount: { fontFamily: fonts.numeric, fontSize: fontSizes.md, color: colors.text },
  requestStatus: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.accentDark },
});
