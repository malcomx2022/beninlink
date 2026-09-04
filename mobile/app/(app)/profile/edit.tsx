import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';

import { ApiError } from '../../../src/api/client';
import { updateProfile } from '../../../src/api/merchant';
import { useSession } from '../../../src/session/SessionProvider';
import { Button, ErrorText, Field, Muted } from '../../../src/components/ui';
import { spacing } from '../../../src/theme/typography';
import { t } from '../../../src/i18n';

/**
 * Modification de l'identité du compte.
 *
 * Les cinq champs sont **toujours** envoyés : le repository du backend
 * réécrit `mobile`, `email` et `business_name` avec ce qu'il reçoit, même
 * absents (voir `ProfileUpdatePayload`). Le formulaire part donc des valeurs
 * actuelles, et l'avertissement à l'écran dit ce qu'un champ vidé produit.
 */
export default function ProfileEditScreen() {
  const router = useRouter();
  const { user, refresh } = useSession();
  const [values, setValues] = useState({
    name: user?.name ?? '',
    business_name: user?.merchant?.business_name ?? '',
    email: user?.email ?? '',
    mobile: user?.phone ?? '',
    address: user?.address ?? user?.merchant?.address ?? '',
  });
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  const set = (key: keyof typeof values) => (value: string) =>
    setValues((v) => ({ ...v, [key]: value }));

  async function save() {
    setError('');
    setFieldErrors({});
    const payload = {
      name: values.name.trim(),
      business_name: values.business_name.trim(),
      email: values.email.trim(),
      mobile: values.mobile.replace(/\s/g, ''),
      address: values.address.trim(),
    };
    if (!payload.name || !payload.business_name || !payload.address) {
      setError(t('errors.requiredField'));
      return;
    }
    setSaving(true);
    try {
      await updateProfile(payload);
      await refresh(); // relit /profile : la session reflète les nouvelles valeurs
      router.back();
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
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
        <Field
          label={t('auth.companyName')}
          value={values.business_name}
          onChangeText={set('business_name')}
          error={fieldErrors.business_name?.[0]}
          editable={!saving}
        />
        <Field
          label={t('auth.managerName')}
          value={values.name}
          onChangeText={set('name')}
          error={fieldErrors.name?.[0]}
          editable={!saving}
        />
        <Field
          label={t('auth.phone')}
          value={values.mobile}
          onChangeText={set('mobile')}
          keyboardType="phone-pad"
          placeholder="22997000000"
          error={fieldErrors.mobile?.[0]}
          editable={!saving}
        />
        <Field
          label={t('auth.email')}
          value={values.email}
          onChangeText={set('email')}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="email-address"
          error={fieldErrors.email?.[0]}
          editable={!saving}
        />
        <Field
          label={t('profile.address')}
          value={values.address}
          onChangeText={set('address')}
          error={fieldErrors.address?.[0]}
          editable={!saving}
        />
        <Muted>{t('profile.allFieldsNotice')}</Muted>
        <ErrorText>{error}</ErrorText>
        <Button title={t('common.save')} onPress={save} loading={saving} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  page: { padding: spacing.lg, gap: spacing.md },
});
