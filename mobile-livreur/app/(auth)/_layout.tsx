import { Stack } from 'expo-router';

import { colors } from '../../src/theme/colors';
import { fonts } from '../../src/theme/typography';
import { t } from '../../src/i18n';

export default function AuthLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: colors.textOnPrimary,
        headerTitleStyle: { fontFamily: fonts.heading },
        contentStyle: { backgroundColor: colors.background },
      }}
    >
      <Stack.Screen name="login" options={{ headerShown: false }} />
      {/* S98 — mot de passe oublié, en deux étapes, comme dans l'app marchand. */}
      <Stack.Screen name="forgot-password" options={{ title: t('auth.forgotTitle') }} />
      <Stack.Screen name="reset-password" options={{ title: t('auth.resetTitle') }} />
    </Stack>
  );
}
