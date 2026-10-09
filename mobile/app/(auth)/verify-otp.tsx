import { useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import { useLocalSearchParams } from 'expo-router';

import { resendOtp } from '../../src/api/auth';
import { ApiError } from '../../src/api/client';
import { useSession } from '../../src/session/SessionProvider';
import { Button, ErrorText, Field, Muted } from '../../src/components/ui';
import { spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/**
 * Vérification du code SMS. Ouvre la session : le garde de app/_layout.tsx
 * bascule ensuite de lui-même vers l'espace connecté.
 *
 * ⚠️ L'envoi du SMS dépend d'une passerelle configurée côté web/ (REVE, Twilio
 * ou Vonage — voir bloc K de la cartographie). Sans passerelle active, aucun
 * code ne part et l'inscription reste bloquée ici : c'est un préalable
 * d'exploitation, pas un défaut de l'app.
 */
export default function VerifyOtpScreen() {
  const { mobile } = useLocalSearchParams<{ mobile?: string }>();
  const { verifyOtp } = useSession();
  const [otp, setOtp] = useState('');
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [loading, setLoading] = useState(false);

  async function submit() {
    setError('');
    if (!mobile?.trim() || !otp.trim()) {
      setError(t('errors.requiredField'));
      return;
    }
    setLoading(true);
    try {
      await verifyOtp(mobile.trim(), otp.trim());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setLoading(false);
    }
  }

  async function resend() {
    setError('');
    setInfo('');
    if (!mobile) {
      setError(t('errors.unexpected'));
      return;
    }
    try {
      await resendOtp(mobile);
      setInfo(t('auth.otpResent'));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Muted>
        {t('auth.otpSent')} {mobile ?? ''}
      </Muted>
      <Field
        label={t('auth.otpTitle')}
        value={otp}
        onChangeText={setOtp}
        keyboardType="number-pad"
        maxLength={5}
        placeholder="•••••"
        editable={!loading}
      />
      <ErrorText>{error}</ErrorText>
      {!!info && <Muted>{info}</Muted>}
      <Button title={t('auth.verify')} onPress={submit} loading={loading} />
      <Button title={t('auth.resendOtp')} onPress={resend} variant="accent" disabled={loading} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.lg, gap: spacing.md },
});
