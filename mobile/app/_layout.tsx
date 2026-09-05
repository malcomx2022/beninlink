import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useFonts } from 'expo-font';
import { Sora_600SemiBold, Sora_700Bold } from '@expo-google-fonts/sora';
import { DMSans_400Regular, DMSans_500Medium } from '@expo-google-fonts/dm-sans';
import { ActivityIndicator, View } from 'react-native';

import { usePushNotifications } from '../src/push';
import { SessionProvider, useSession } from '../src/session/SessionProvider';
import { colors } from '../src/theme/colors';
import { fonts } from '../src/theme/typography';

function Splash() {
  return (
    <View
      style={{
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: colors.background,
      }}
    >
      <ActivityIndicator color={colors.primary} />
    </View>
  );
}

/**
 * Deux groupes de routes exclusifs : `(auth)` et `(app)`.
 * `expo-router` monte celui qui correspond à l'état de session ; on n'utilise pas
 * de redirection impérative, qui provoque un aller-retour visible à l'écran.
 */
function RootNavigator() {
  const { loading, user } = useSession();

  // Abonnement de l'appareil et ouverture de l'écran touché : uniquement
  // session ouverte (voir src/push).
  usePushNotifications(!!user);

  if (loading) return <Splash />;

  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: colors.textOnPrimary,
        headerTitleStyle: { fontFamily: fonts.heading },
        contentStyle: { backgroundColor: colors.background },
      }}
    >
      <Stack.Protected guard={!user}>
        <Stack.Screen name="(auth)" options={{ headerShown: false }} />
      </Stack.Protected>
      <Stack.Protected guard={!!user}>
        <Stack.Screen name="(app)" options={{ headerShown: false }} />
      </Stack.Protected>
    </Stack>
  );
}

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    [fonts.heading]: Sora_600SemiBold,
    [fonts.headingBold]: Sora_700Bold,
    [fonts.body]: DMSans_400Regular,
    [fonts.bodyMedium]: DMSans_500Medium,
  });

  if (!fontsLoaded) return <Splash />;

  return (
    <SessionProvider>
      {/* Pas de backgroundColor : retiré en SDK 57 (edge-to-edge). */}
      <StatusBar style="light" />
      <RootNavigator />
    </SessionProvider>
  );
}
