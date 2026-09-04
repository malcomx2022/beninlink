import { useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { updatePassword } from '../../../src/api/merchant';
import { Button, ErrorText, Field, Muted } from '../../../src/components/ui';
import { spacing } from '../../../src/theme/typography';
import { t } from '../../../src/i18n';

/** Minimum imposé par `PUT /update-password` (`UpdatePasswordRequest`). */
const MIN_PASSWORD_LENGTH = 6;

/**
 * Changement de mot de passe, session ouverte. Le backend vérifie l'ancien
 * mot de passe et répond 422 avec un message explicite s'il ne correspond pas.
 */
export default function ChangePasswordScreen() {
  const router = useRouter();
  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [done, setDone] = useState('');
  const [saving, setSaving] = useState(false);

  async function submit() {
    setError('');
    setFieldErrors({});
    if (!current || !next) {
      setError(t('errors.requiredField'));
      return;
    }
    if (next.length < MIN_PASSWORD_LENGTH) {
      setError(t('errors.passwordTooShort').replace('{n}', String(MIN_PASSWORD_LENGTH)));
      return;
    }
    if (next !== confirmation) {
      setError(t('errors.passwordMismatch'));
      return;
    }
    setSaving(true);
    try {
      await updatePassword(current, next);
      setDone(t('profile.passwordChanged'));
      setCurrent('');
      setNext('');
      setConfirmation('');
    } catch (e) {
      if (e instanceof ApiError) {
        setError(e.message);
        setFieldErrors(e.errors);
      } else {
        setError(t('errors.unexpected'));
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Field
        label={t('profile.currentPassword')}
        value={current}
        onChangeText={setCurrent}
        secureTextEntry
        autoCapitalize="none"
        error={fieldErrors.old_password?.[0]}
        editable={!saving}
      />
      <Field
        label={t('auth.newPassword')}
        value={next}
        onChangeText={setNext}
        secureTextEntry
        autoCapitalize="none"
        error={fieldErrors.new_password?.[0]}
        editable={!saving}
      />
      <Field
        label={t('auth.confirmPassword')}
        value={confirmation}
        onChangeText={setConfirmation}
        secureTextEntry
        autoCapitalize="none"
        error={fieldErrors.confirm_password?.[0]}
        editable={!saving}
      />
      <ErrorText>{error}</ErrorText>
      {!!done && <Muted>{done}</Muted>}
      {done ? (
        <Button title={t('common.back')} onPress={() => router.back()} />
      ) : (
        <Button title={t('profile.changePassword')} onPress={submit} loading={saving} />
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: spacing.lg, gap: spacing.md },
});
