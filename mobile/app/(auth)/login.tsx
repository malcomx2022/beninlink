import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Link } from 'expo-router';

import { ApiError } from '../../src/api/client';
import { API_BASE_URL } from '../../src/api/config';
import { useSession } from '../../src/session/SessionProvider';
import { Button, ErrorText, Field } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

export default function LoginScreen() {
  const { signIn } = useSession();
  const [merchantId, setMerchantId] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(false);

  async function submit() {
    setError('');
    setFieldErrors({});
    if (!merchantId.trim() || !password) {
      setError(t('errors.requiredField'));
      return;
    }
    setLoading(true);
    try {
      await signIn(merchantId, password);
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
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <View style={styles.brand}>
          <Text style={styles.brandName}>{t('common.appName')}</Text>
          <Text style={styles.brandBaseline}>Espace marchand</Text>
        </View>

        <Field
          label={t('auth.merchantId')}
          testID="login-identifiant"
          value={merchantId}
          onChangeText={setMerchantId}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="default"
          placeholder="Ex. 2024"
          error={fieldErrors.merchant_id?.[0]}
          editable={!loading}
        />
        {/* Le backend identifie par l'identifiant marchand (users.unique_id),
            pas par téléphone : l'indication évite une impasse à la saisie. */}
        <Text style={styles.hint}>{t('auth.merchantIdHint')}</Text>

        <Field
          label={t('auth.password')}
          testID="login-mot-de-passe"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoCapitalize="none"
          error={fieldErrors.password?.[0]}
          editable={!loading}
          onSubmitEditing={submit}
          returnKeyType="go"
        />

        <ErrorText testID="login-erreur">{error}</ErrorText>

        <Button title={t('auth.signIn')} onPress={submit} loading={loading} />

        <Link href="/(auth)/forgot-password" style={styles.link}>
          {t('auth.forgotPassword')}
        </Link>

        <Link href="/(auth)/signup" style={styles.link}>
          {t('auth.noAccount')}
        </Link>

        {/* Repère de développement : affiche l'API réellement visée par le bundle
            chargé. Évite de confondre un backend injoignable avec un bundle
            périmé encore servi par le cache du navigateur. Retiré en production. */}
        {__DEV__ && <Text style={styles.debug}>API : {API_BASE_URL}</Text>}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  page: { padding: spacing.lg, gap: spacing.md, flexGrow: 1, justifyContent: 'center' },
  brand: { alignItems: 'center', marginBottom: spacing.lg },
  brandName: { fontFamily: fonts.headingBold, fontSize: fontSizes.xxl, color: colors.primary },
  brandBaseline: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  hint: {
    fontFamily: fonts.body,
    fontSize: fontSizes.xs,
    color: colors.textMuted,
    marginTop: -spacing.xs,
  },
  link: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.sm,
    color: colors.primary,
    textAlign: 'center',
    paddingVertical: spacing.sm,
  },
  debug: {
    fontFamily: fonts.body,
    fontSize: fontSizes.xs,
    color: colors.disabled,
    textAlign: 'center',
  },
});
