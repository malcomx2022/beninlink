import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { signUp } from '../../src/api/auth';
import { ApiError } from '../../src/api/client';
import { Button, ErrorText, Field, Muted } from '../../src/components/ui';
import { spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/**
 * Inscription d'une PME.
 *
 * IFU et RCCM sont **exigés par le backend** depuis le chantier 2 ; la CNSS ne
 * concerne que les entreprises ayant des salariés, elle reste facultative.
 *
 * Le backend ne connecte pas à l'issue de l'inscription : il envoie un code par
 * SMS. On enchaîne donc sur l'écran de vérification, pas sur le tableau de bord.
 */
export default function SignUpScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const [values, setValues] = useState({
    business_name: '',
    full_name: '',
    mobile: '',
    address: '',
    ifu: '',
    rccm: '',
    cnss: '',
    password: '',
  });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const set = (key: keyof typeof values) => (value: string) =>
    setValues((v) => ({ ...v, [key]: value }));

  async function submit() {
    setError('');
    setFieldErrors({});
    const requiredFields = ['business_name', 'full_name', 'mobile', 'address', 'ifu', 'rccm', 'password'] as const;
    const missing: Record<string, string[]> = {};
    for (const field of requiredFields) {
      if (!values[field].trim()) missing[field] = [t('errors.requiredField')];
    }
    if (Object.keys(missing).length) {
      setFieldErrors(missing);
      setError(t('auth.signupCorrectionHint'));
      return;
    }
    setLoading(true);
    try {
      const mobile = await signUp({
        business_name: values.business_name.trim(),
        full_name: values.full_name.trim(),
        address: values.address.trim(),
        mobile: values.mobile.trim(),
        password: values.password,
        ifu: values.ifu.trim(),
        rccm: values.rccm.trim(),
        // Champ vide non transmis : le backend le valide en `nullable`, mais une
        // chaîne vide déclencherait quand même le contrôle de format.
        ...(values.cnss.trim() ? { cnss: values.cnss.trim() } : {}),
      });
      router.replace({ pathname: '/(auth)/verify-otp', params: { mobile } });
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
      <ScrollView contentContainerStyle={[styles.page, { paddingBottom: spacing.lg + insets.bottom }]} keyboardShouldPersistTaps="handled">
        <Muted>{t('auth.signupRequiredHint')}</Muted>
        <Field
          label={t('auth.companyName')}
          value={values.business_name}
          onChangeText={set('business_name')}
          placeholder="Ex. Kola Distribution"
          error={fieldErrors.business_name?.[0]}
          editable={!loading}
        />
        <Field
          label={t('auth.managerName')}
          value={values.full_name}
          onChangeText={set('full_name')}
          placeholder="Ex. Fatou A."
          error={fieldErrors.full_name?.[0]}
          editable={!loading}
        />
        <Field
          label={t('auth.phone')}
          value={values.mobile}
          onChangeText={set('mobile')}
          keyboardType="phone-pad"
          placeholder="01 97 00 00 00"
          error={fieldErrors.mobile?.[0]}
          editable={!loading}
        />
        <Muted>{t('auth.signupPhoneHint')}</Muted>
        <Field
          label={t('auth.city')}
          value={values.address}
          onChangeText={set('address')}
          placeholder="Cotonou"
          error={fieldErrors.address?.[0]}
          editable={!loading}
        />

        <Muted>{t('auth.legalSection')}</Muted>
        <Field
          label={`${t('auth.ifu')} (${t('common.required')})`}
          value={values.ifu}
          onChangeText={set('ifu')}
          keyboardType="number-pad"
          maxLength={13}
          placeholder="13 chiffres"
          error={fieldErrors.ifu?.[0]}
          editable={!loading}
        />
        <Field
          label={`${t('auth.rccm')} (${t('common.required')})`}
          value={values.rccm}
          onChangeText={set('rccm')}
          autoCapitalize="characters"
          placeholder="Ex. RB/COT/24 B 1234"
          error={fieldErrors.rccm?.[0]}
          editable={!loading}
        />
        <Muted>{t('auth.signupRccmHint')}</Muted>
        <Field
          label={`${t('auth.cnss')} (${t('common.optional')})`}
          value={values.cnss}
          onChangeText={set('cnss')}
          placeholder="N° employeur"
          error={fieldErrors.cnss?.[0]}
          editable={!loading}
        />

        <Field
          label={t('auth.password')}
          value={values.password}
          onChangeText={set('password')}
          secureTextEntry
          autoCapitalize="none"
          error={fieldErrors.password?.[0]}
          editable={!loading}
        />
        <Muted>{t('auth.signupPasswordHint')}</Muted>
        <ErrorText>{error}</ErrorText>
        <Button title={t('auth.createAccount')} onPress={submit} loading={loading} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  page: { padding: spacing.lg, gap: spacing.md },
});
