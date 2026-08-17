import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useFonts } from 'expo-font';
import { Sora_600SemiBold, Sora_700Bold } from '@expo-google-fonts/sora';
import { DMSans_400Regular, DMSans_500Medium } from '@expo-google-fonts/dm-sans';
import { ActivityIndicator, View } from 'react-native';

import { colors } from '@/theme/colors';
import { fonts } from '@/theme/typography';

export default function RootLayout() {
  // Les clés doivent correspondre à src/theme/typography.ts.
  const [fontsLoaded] = useFonts({
    [fonts.heading]: Sora_600SemiBold,
    [fonts.headingBold]: Sora_700Bold,
    [fonts.body]: DMSans_400Regular,
    [fonts.bodyMedium]: DMSans_500Medium,
  });

  if (!fontsLoaded) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background }}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  return (
    <>
      {/* Pas de backgroundColor : supprimé par expo-status-bar en mode edge-to-edge
          (SDK 57). La couleur de fond vient de l'en-tête du Stack ci-dessous. */}
      <StatusBar style="light" />
      <Stack
        screenOptions={{
          headerStyle: { backgroundColor: colors.primary },
          headerTintColor: colors.textOnPrimary,
          headerTitleStyle: { fontFamily: fonts.heading },
          contentStyle: { backgroundColor: colors.background },
        }}
      />
    </>
  );
}
