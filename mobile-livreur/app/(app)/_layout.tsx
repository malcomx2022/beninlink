import { Stack } from 'expo-router';

import { colors } from '../../src/theme/colors';
import { fonts } from '../../src/theme/typography';
import { t } from '../../src/i18n';

/**
 * Les trois onglets (courses, gains, profil) vivent dans `(tabs)` ; le détail
 * d'une course et le changement de statut s'empilent par-dessus.
 */
export default function AppLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: colors.textOnPrimary,
        headerTitleStyle: { fontFamily: fonts.heading },
        contentStyle: { backgroundColor: colors.background },
      }}
    >
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      <Stack.Screen name="parcel/[id]/index" options={{ title: t('parcels.detail') }} />
      <Stack.Screen name="parcel/[id]/status" options={{ title: t('status.title') }} />
    </Stack>
  );
}
