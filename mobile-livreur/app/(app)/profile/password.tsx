import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text } from 'react-native';
import { useRouter } from 'expo-router';

import { updatePassword } from '../../../src/api/auth';
import { ApiError } from '../../../src/api/client';
import { Button, Card, ErrorText, Field, Muted } from '../../../src/components/ui';
import { colors } from '../../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../../src/theme/typography';
import { t } from '../../../src/i18n';

/** Le backend exige 6 caractères et la confirmation (`UpdatePasswordRequest`). */
const MIN_LENGTH = 6;

export default function PasswordScreen() {
  const router = useRouter();
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [done, setDone] = useState(false);

  async function submit() {
    setError('');
    setFieldErrors({});
    if (!oldPassword || !newPassword || !confirm) {
      setError(t('errors.requiredField'));
      return;
    }
    if (newPassword.length < MIN_LENGTH) {
      setError(t('profile.passwordTooShort').replace('{n}', String(MIN_LENGTH)));
      return;
    }
    if (newPassword !== confirm) {
      setError(t('profile.passwordMismatch'));
      return;
    }
    setSaving(true);
    try {
      await updatePassword(oldPassword, newPassword);
      setDone(true);
      setTimeout(() => router.back(), 800);
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
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <Card>
          <Field
            label={t('profile.oldPassword')}
            value={oldPassword}
            onChangeText={setOldPassword}
            secureTextEntry
            textContentType="password"
            error={fieldErrors.old_password?.[0]}
          />
          <Field
            label={t('profile.newPassword')}
            value={newPassword}
            onChangeText={setNewPassword}
            secureTextEntry
            textContentType="newPassword"
            error={fieldErrors.new_password?.[0]}
          />
          <Field
            label={t('profile.confirmPassword')}
            value={confirm}
            onChangeText={setConfirm}
            secureTextEntry
            textContentType="newPassword"
            onSubmitEditing={submit}
            error={fieldErrors.confirm_password?.[0]}
          />
          <Muted>{t('profile.passwordTooShort').replace('{n}', String(MIN_LENGTH))}</Muted>
          <ErrorText>{error}</ErrorText>
          {done && <Text style={styles.done}>{t('profile.passwordChanged')}</Text>}
          <Button title={t('common.confirm')} onPress={submit} loading={saving} disabled={done} variant="accent" />
        </Card>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  page: { padding: spacing.md, gap: spacing.md },
  done: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.success },
});
