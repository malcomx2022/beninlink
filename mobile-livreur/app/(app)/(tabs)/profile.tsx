import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../../src/api/client';
import { fetchProfile } from '../../../src/api/deliveryman';
import type { ProfileData } from '../../../src/api/types';
import { useSession } from '../../../src/session/SessionProvider';
import { Button, Card, CountTile, ErrorText, StatTile, Title } from '../../../src/components/ui';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../../src/theme/typography';
import { formatAmount } from '../../../src/domain/money';
import { t } from '../../../src/i18n';

export default function ProfileScreen() {
  const { user, signOut } = useSession();
  const [profile, setProfile] = useState<ProfileData | null>(null);
  const [error, setError] = useState('');
  const [signingOut, setSigningOut] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      setProfile(await fetchProfile());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  async function handleSignOut() {
    setError('');
    setSigningOut(true);
    try {
      await signOut();
    } catch {
      setError(t('errors.unexpected'));
    } finally {
      setSigningOut(false);
    }
  }

  const current = profile?.user ?? user;
  const deliveryman = current?.deliveryman ?? null;

  return (
    <ScrollView
      contentContainerStyle={styles.page}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <Card>
        <Title>{current?.name ?? '—'}</Title>
        <Line label={t('auth.phone')} value={current?.phone ?? '—'} />
        <Line label={t('auth.email')} value={current?.email ?? '—'} />
        <Line label={t('profile.hub')} value={current?.hub?.name ?? '—'} />
      </Card>

      <View style={styles.tiles}>
        <CountTile label={t('profile.inProgress')} value={profile?.delivery_in_progress ?? 0} />
        <CountTile label={t('profile.delivered')} value={profile?.completed_delivered ?? 0} />
        <CountTile label={t('profile.returns')} value={profile?.canceled_delivered ?? 0} />
      </View>

      <View style={styles.tiles}>
        <StatTile label={t('earnings.balance')} value={profile?.current_balance ?? deliveryman?.current_balance} highlight />
        <StatTile label={t('earnings.earned')} value={profile?.deliveryman_earn} />
        <StatTile label={t('earnings.totalCod')} value={profile?.total_cod} />
      </View>

      <Card>
        <Title>{t('profile.rates')}</Title>
        <Line label={t('profile.deliveryCharge')} value={formatAmount(deliveryman?.delivery_charge)} />
        <Line label={t('profile.pickupCharge')} value={formatAmount(deliveryman?.pickup_charge)} />
        <Line label={t('profile.returnCharge')} value={formatAmount(deliveryman?.return_charge)} />
      </Card>

      <ErrorText>{error}</ErrorText>
      <Button title={t('profile.signOut')} onPress={handleSignOut} loading={signingOut} />
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
  tiles: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  line: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  lineLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  lineValue: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.text, flexShrink: 1, textAlign: 'right' },
});
