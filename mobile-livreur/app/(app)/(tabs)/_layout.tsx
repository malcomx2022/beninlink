import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import type { ColorValue } from 'react-native';

import { colors } from '../../../src/theme/colors';
import { fonts } from '../../../src/theme/typography';
import { t } from '../../../src/i18n';

type IconName = keyof typeof Ionicons.glyphMap;

function icon(name: IconName) {
  return function TabIcon({ color, size }: { color: ColorValue; size: number }) {
    return <Ionicons name={name} color={color} size={size} />;
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
        options={{ title: t('parcels.title'), tabBarLabel: t('tabs.parcels'), tabBarButtonTestID: 'onglet-courses', tabBarIcon: icon('bicycle-outline') }}
      />
      <Tabs.Screen
        name="earnings"
        options={{ title: t('earnings.title'), tabBarLabel: t('tabs.earnings'), tabBarButtonTestID: 'onglet-gains', tabBarIcon: icon('wallet-outline') }}
      />
      <Tabs.Screen
        name="profile"
        options={{ title: t('profile.title'), tabBarLabel: t('tabs.profile'), tabBarButtonTestID: 'onglet-profil', tabBarIcon: icon('person-outline') }}
      />
    </Tabs>
  );
}
