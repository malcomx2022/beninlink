import { useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import { Link, useLocalSearchParams } from 'expo-router';

import { resetPassword } from '../../src/api/auth';
import { ApiError } from '../../src/api/client';
import { Button, ErrorText, Field, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/** Minimum imposé par `POST password/reset` côté backend. */
const MIN_PASSWORD_LENGTH = 8;

/**
 * Seconde étape du mot de passe oublié : définir le nouveau mot de passe.
 *
 * Le jeton vient du lien envoyé par e-mail. Il arrive ici de deux façons :
 * par lien profond (`beninlink://reset-password?token=…&email=…`, que le
 * backend pourra émettre sans rien changer à l'app) ou par saisie, à partir
 * du lien web. L'écran ne fait aucune hypothèse sur la forme du jeton : le
 * backend est seul juge de sa validité.
 */
export default function ResetPasswordScreen() {
  const params = useLocalSearchParams<{ token?: string; email?: string }>();
  const [token, setToken] = useState(params.token ?? '');
  const [email, setEmail] = useState(params.email ?? '');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [done, setDone] = useState('');
  const [loading, setLoading] = useState(false);

  async function submit() {
    setError('');
    setFieldErrors({});
    if (!token.trim() || !email.trim() || !password) {
      setError(t('errors.requiredField'));
      return;
    }
    if (password.length < MIN_PASSWORD_LENGTH) {
      setError(t('errors.passwordTooShort').replace('{n}', String(MIN_PASSWORD_LENGTH)));
      return;
    }
    if (password !== confirmation) {
      setError(t('errors.passwordMismatch'));
      return;
    }
    setLoading(true);
    try {
      const message = await resetPassword({
        token: token.trim(),
        email,
        password,
        password_confirmation: confirmation,
      });
      setDone(message || t('auth.resetDone'));
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
        setFieldErrors(e.errors);
      } else {
        setError(t('errors.unexpected'));
      }
    } finally {
      setLoading(false);
    }
  }

  if (done) {
    return (
      <ScrollView contentContainerStyle={styles.page}>
        <Muted>{done}</Muted>
        <Link href="/(auth)/login" style={styles.link}>
          {t('auth.backToSignIn')}
        </Link>
      </ScrollView>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Field
        label={t('auth.email')}
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        autoCorrect={false}
        keyboardType="email-address"
        placeholder="nom@entreprise.bj"
        error={fieldErrors.email?.[0]}
        editable={!loading}
      />
      <Field
        label={t('auth.resetToken')}
        value={token}
        onChangeText={setToken}
        autoCapitalize="none"
        autoCorrect={false}
        error={fieldErrors.token?.[0]}
        editable={!loading}
      />
      <Muted>{t('auth.resetTokenHint')}</Muted>
      <Field
        label={t('auth.newPassword')}
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        autoCapitalize="none"
        error={fieldErrors.password?.[0]}
        editable={!loading}
      />
      <Field
        label={t('auth.confirmPassword')}
        value={confirmation}
        onChangeText={setConfirmation}
        secureTextEntry
        autoCapitalize="none"
        editable={!loading}
      />
      <ErrorText>{error}</ErrorText>
      <Button title={t('auth.resetPassword')} onPress={submit} loading={loading} />
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
