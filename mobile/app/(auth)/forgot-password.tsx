import { useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';

import { requestPasswordReset } from '../../src/api/auth';
import { ApiError } from '../../src/api/client';
import { Button, ErrorText, Field, Muted } from '../../src/components/ui';
import { spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [sent, setSent] = useState('');
  const [loading, setLoading] = useState(false);

  async function submit() {
    setError('');
    setSent('');
    if (!email.trim()) {
      setError(t('errors.requiredField'));
      return;
    }
    setLoading(true);
    try {
      const message = await requestPasswordReset(email);
      setSent(message || t('auth.resetSent'));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    } finally {
      setLoading(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Muted>{t('auth.resetIntro')}</Muted>
      <Field
        label={t('auth.email')}
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        autoCorrect={false}
        keyboardType="email-address"
        placeholder="nom@entreprise.bj"
        editable={!loading}
      />
      <ErrorText>{error}</ErrorText>
      {!!sent && <Muted>{sent}</Muted>}
      <Button title={t('auth.sendResetLink')} onPress={submit} loading={loading} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.lg, gap: spacing.md },
});
