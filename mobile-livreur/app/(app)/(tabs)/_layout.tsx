import { Tabs } from 'expo-router';
import { Text, type ColorValue } from 'react-native';

import { colors } from '../../../src/theme/colors';
import { fonts } from '../../../src/theme/typography';
import { t } from '../../../src/i18n';

/**
 * Icônes en glyphes Unicode : pas de bibliothèque d'icônes dans les
 * dépendances (celles de mobile/, reprises telles quelles). À remplacer par
 * un jeu d'icônes si la charte en impose un.
 */
function icon(glyph: string) {
  return function TabIcon({ color, size }: { color: ColorValue; size: number }) {
    return <Text style={{ color, fontSize: size, lineHeight: size + 4 }}>{glyph}</Text>;
  };
}

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: colors.textOnPrimary,
        headerTitleStyle: { fontFamily: fonts.heading },
        sceneStyle: { backgroundColor: colors.background },
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarLabelStyle: { fontFamily: fonts.bodyMedium },
        tabBarStyle: { backgroundColor: colors.surface, borderTopColor: colors.border },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{ title: t('parcels.title'), tabBarLabel: t('tabs.parcels'), tabBarIcon: icon('▣') }}
      />
      <Tabs.Screen
        name="earnings"
        options={{ title: t('earnings.title'), tabBarLabel: t('tabs.earnings'), tabBarIcon: icon('◆') }}
      />
      <Tabs.Screen
        name="profile"
        options={{ title: t('profile.title'), tabBarLabel: t('tabs.profile'), tabBarIcon: icon('●') }}
      />
    </Tabs>
  );
}
