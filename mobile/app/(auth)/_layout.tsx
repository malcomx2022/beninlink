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
      <Stack.Screen name="signup" options={{ title: t('auth.signUpTitle') }} />
      <Stack.Screen name="verify-otp" options={{ title: t('auth.otpTitle') }} />
      <Stack.Screen name="forgot-password" options={{ title: t('auth.forgotTitle') }} />
      <Stack.Screen name="reset-password" options={{ title: t('auth.resetTitle') }} />
    </Stack>
  );
}
