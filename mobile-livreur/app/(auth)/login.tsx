import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Link } from 'expo-router';

import { ApiError } from '../../src/api/client';
import { API_BASE_URL } from '../../src/api/config';
import { useSession } from '../../src/session/SessionProvider';
import { Button, ErrorText, Field, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

export default function LoginScreen() {
  const { signIn } = useSession();
  const [driverId, setDriverId] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(false);

  async function submit() {
    setError('');
    setFieldErrors({});
    if (!driverId.trim() || !password) {
      setError(t('errors.requiredField'));
      return;
    }
    setLoading(true);
    try {
      await signIn(driverId, password);
      // La redirection est portée par le garde de app/_layout.tsx.
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

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <View style={styles.brand}>
          <Text style={styles.brandName}>{t('common.appName')}</Text>
          <Text style={styles.brandBaseline}>{t('auth.baseline')}</Text>
        </View>

        <Field
          label={t('auth.driverId')}
          testID="login-identifiant"
          value={driverId}
          onChangeText={setDriverId}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="default"
          textContentType="username"
          error={fieldErrors.driver_id?.[0]}
        />
        <Muted>{t('auth.driverIdHint')}</Muted>
        <Field
          label={t('auth.password')}
          testID="login-mot-de-passe"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          textContentType="password"
          onSubmitEditing={submit}
          error={fieldErrors.password?.[0]}
        />

        {/* Repère E2E (.maestro/flows/01) : le message vient du serveur, seul l'identifiant est stable. */}
        {!!error && (
          <View testID="login-erreur" collapsable={false}>
            <ErrorText>{error}</ErrorText>
          </View>
        )}
        <Button title={t('auth.signIn')} onPress={submit} loading={loading} variant="accent" />
        {/* S98 — mot de passe oublié : les mêmes routes que l'app marchand. */}
        <Link href="/(auth)/forgot-password" style={styles.link}>
          {t('auth.forgotPassword')}
        </Link>

        {__DEV__ && <Text style={styles.devHint}>{API_BASE_URL}</Text>}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  page: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg, gap: spacing.md },
  brand: { alignItems: 'center', marginBottom: spacing.lg, gap: spacing.xs },
  brandName: { fontFamily: fonts.headingBold, fontSize: fontSizes.xxl, color: colors.primary },
  brandBaseline: { fontFamily: fonts.body, fontSize: fontSizes.md, color: colors.textMuted },
  link: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    textAlign: 'center',
    paddingVertical: spacing.sm,
  },
  devHint: {
    fontFamily: fonts.body,
    fontSize: fontSizes.xs,
    color: colors.disabled,
    textAlign: 'center',
    marginTop: spacing.lg,
  },
});
