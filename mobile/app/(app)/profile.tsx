import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { useSession } from '../../src/session/SessionProvider';
import { Button, Card, ErrorText, Muted, Title } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { formatAmount, formatRate } from '../../src/domain/money';
import { t } from '../../src/i18n';

export default function ProfileScreen() {
  const { user, signOut } = useSession();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const merchant = user?.merchant ?? null;

  async function handleSignOut() {
    setError('');
    setLoading(true);
    try {
      await signOut();
    } catch {
      setError(t('errors.unexpected'));
    } finally {
      setLoading(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <Card>
        <Title>{merchant?.business_name ?? user?.name ?? '—'}</Title>
        <Line label={t('auth.merchantId')} value={merchant?.merchant_unique_id ?? '—'} />
        <Line label={t('auth.phone')} value={user?.phone ?? '—'} />
        <Line label={t('auth.email')} value={user?.email ?? '—'} />
        <Line label={t('profile.address')} value={user?.address ?? merchant?.address ?? '—'} />
        <Line label={t('profile.hub')} value={user?.hub?.name ?? '—'} />
      </Card>

      <Card>
        <Title>{t('profile.billingTerms')}</Title>
        {/* La TVA est un TAUX : jamais de symbole monétaire (bug corrigé côté web). */}
        <Line label={t('invoices.vat')} value={formatRate(merchant?.vat)} />
        <Line label={t('profile.returnCharges')} value={formatAmount(merchant?.return_charges)} />
        <Line label={t('wallet.balance')} value={formatAmount(merchant?.wallet_balance)} />
        <Muted>{t('wallet.prepaidNotice')}</Muted>
      </Card>

      <ErrorText>{error}</ErrorText>
      <Button title={t('profile.signOut')} onPress={handleSignOut} loading={loading} />
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
  line: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  lineLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  lineValue: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.text,
    flexShrink: 1,
    textAlign: 'right',
  },
});
