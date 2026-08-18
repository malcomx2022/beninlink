import { Stack } from 'expo-router';

import { colors } from '../../src/theme/colors';
import { fonts } from '../../src/theme/typography';
import { t } from '../../src/i18n';

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
      <Stack.Screen name="index" options={{ title: t('dashboard.title') }} />
      <Stack.Screen name="parcels" options={{ title: t('parcels.title') }} />
      <Stack.Screen name="parcel/new" options={{ title: t('parcels.newParcel') }} />
      <Stack.Screen name="parcel/[id]" options={{ title: t('parcels.detail') }} />
      <Stack.Screen name="wallet" options={{ title: t('wallet.title') }} />
      <Stack.Screen name="shops" options={{ title: t('shops.title') }} />
      <Stack.Screen name="profile" options={{ title: t('profile.title') }} />
    </Stack>
  );
}
