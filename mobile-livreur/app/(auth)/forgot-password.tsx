import { useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import { Link } from 'expo-router';

import { requestPasswordReset } from '../../src/api/auth';
import { ApiError } from '../../src/api/client';
import { Button, ErrorText, Field, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/**
 * S98 — première étape du mot de passe oublié : demander le lien (repris de l'app marchand).
 *
 * Le backend envoie un e-mail dont le lien mène à sa page web de
 * réinitialisation. La seconde étape existe aussi dans l'app
 * (`reset-password`), avec le même jeton : on la propose une fois le message
 * parti, sans laisser croire que l'app reçoit quoi que ce soit d'elle-même.
 */
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
        placeholder="prenom.nom@exemple.bj"
        editable={!loading}
      />
      <ErrorText>{error}</ErrorText>
      {!!sent && (
        <>
          <Muted>{sent}</Muted>
          <Muted>{t('auth.resetNextStep')}</Muted>
        </>
      )}
      <Button title={t('auth.sendResetLink')} onPress={submit} loading={loading} />
      <Link
        href={{ pathname: '/(auth)/reset-password', params: { email: email.trim() } }}
        style={styles.link}
      >
        {t('auth.haveToken')}
      </Link>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.lg, gap: spacing.md },
  link: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    textAlign: 'center',
    paddingVertical: spacing.sm,
  },
});
