import { useCallback, useEffect, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { fetchShops } from '../../src/api/parcels';
import type { Shop } from '../../src/api/types';
import { ErrorText, Muted } from '../../src/components/ui';
import { colors } from '../../src/theme/colors';
import { fonts, fontSizes, radii, spacing } from '../../src/theme/typography';
import { t } from '../../src/i18n';

export default function ShopsScreen() {
  const [shops, setShops] = useState<Shop[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      setShops(await fetchShops());
    } catch (e) {
      setError(e instanceof ApiError ? e.message : t('errors.unexpected'));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  return (
    <FlatList
      data={shops}
      keyExtractor={(s) => String(s.id)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      ListHeaderComponent={<ErrorText>{error}</ErrorText>}
      ListEmptyComponent={
        <View style={styles.empty}>
          <Muted>{t('shops.empty')}</Muted>
        </View>
      }
      renderItem={({ item }) => (
        <View style={styles.row}>
          <View style={styles.rowTop}>
            <Text style={styles.name}>{item.name}</Text>
            {/* `default_shop` arrive en « 0 »/« 1 » selon l'endpoint. */}
            {String(item.default_shop) === '1' && (
              <Text style={styles.badge}>{t('shops.default')}</Text>
            )}
          </View>
          <Muted>{item.address ?? ''}</Muted>
          <Muted>{item.contact_no ?? ''}</Muted>
        </View>
      )}
    />
  );
}

const styles = StyleSheet.create({
  list: { padding: spacing.md, gap: spacing.sm, backgroundColor: colors.background, flexGrow: 1 },
  empty: { padding: spacing.xl, alignItems: 'center' },
  row: {
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: spacing.xs,
  },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  name: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.md, color: colors.text },
  badge: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.accentDark },
});
