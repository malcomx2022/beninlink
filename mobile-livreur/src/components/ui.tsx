/**
 * Briques d'interface communes. Regroupées en un fichier tant qu'elles tiennent :
 * un fichier par composant se justifiera quand elles auront des variantes.
 * Aucun littéral de couleur ici non plus — tout vient du thème.
 */
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type TextInputProps,
  type ViewProps,
} from 'react-native';

import { colors } from '../theme/colors';
import { fonts, fontSizes, radii, spacing } from '../theme/typography';
import { formatAmount } from '../domain/money';
import type { Amount } from '../api/types';

export function Card({ style, ...props }: ViewProps) {
  return <View {...props} style={[styles.card, style]} />;
}

export function Title({ children }: { children: React.ReactNode }) {
  return <Text style={styles.title}>{children}</Text>;
}

export function Label({ children }: { children: React.ReactNode }) {
  return <Text style={styles.label}>{children}</Text>;
}

export function Muted({ children }: { children: React.ReactNode }) {
  return <Text style={styles.muted}>{children}</Text>;
}

/** Message d'erreur. Rouge = incident, conformément à la charte. */
export function ErrorText({ children }: { children: React.ReactNode }) {
  if (!children) return null;
  return <Text style={styles.error}>{children}</Text>;
}

type FieldProps = TextInputProps & { label: string; error?: string };

export function Field({ label, error, style, ...props }: FieldProps) {
  return (
    <View style={styles.field}>
      <Label>{label}</Label>
      <TextInput
        placeholderTextColor={colors.disabled}
        {...props}
        style={[styles.input, !!error && styles.inputError, style]}
      />
      <ErrorText>{error}</ErrorText>
    </View>
  );
}

type ButtonProps = {
  title: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  /** `accent` (ocre) réservé à l'action clé de l'écran. */
  variant?: 'primary' | 'accent';
};

export function Button({ title, onPress, loading, disabled, variant = 'primary' }: ButtonProps) {
  const inactive = disabled || loading;
  return (
    <Pressable
      accessibilityRole="button"
      onPress={onPress}
      disabled={inactive}
      style={({ pressed }) => [
        styles.button,
        variant === 'accent' ? styles.buttonAccent : styles.buttonPrimary,
        inactive && styles.buttonDisabled,
        pressed && !inactive && styles.buttonPressed,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={colors.textOnPrimary} />
      ) : (
        <Text style={styles.buttonText}>{title}</Text>
      )}
    </Pressable>
  );
}

/** Chiffre clé : libellé + montant FCFA. Sora pour les chiffres (charte). */
export function StatTile({
  label,
  value,
  highlight,
}: {
  label: string;
  /** `undefined` accepté : les données peuvent ne pas être encore chargées. */
  value: Amount | undefined;
  highlight?: boolean;
}) {
  return (
    <View style={styles.tile}>
      <Text style={styles.tileLabel}>{label}</Text>
      <Text style={[styles.tileValue, highlight && styles.tileValueHighlight]}>
        {formatAmount(value)}
      </Text>
    </View>
  );
}

/**
 * Choix parmi quelques options, en pastilles.
 * Préféré à un `Picker` natif : pas de dépendance supplémentaire, et les listes
 * du formulaire de colis (boutiques, catégories, types) restent courtes.
 */
export function ChoiceGroup<T extends string | number>({
  label,
  options,
  value,
  onChange,
  error,
  disabled = false,
}: {
  label: string;
  options: { value: T; label: string }[];
  value: T | null;
  onChange: (value: T) => void;
  error?: string;
  disabled?: boolean;
}) {
  return (
    <View style={styles.field}>
      <Label>{label}</Label>
      <View style={styles.choices}>
        {options.map((option) => {
          const active = option.value === value;
          return (
            <Pressable
              key={String(option.value)}
              disabled={disabled}
              accessibilityState={{ disabled }}
              onPress={() => onChange(option.value)}
              style={[styles.choice, active && styles.choiceActive]}
            >
              <Text style={[styles.choiceLabel, active && styles.choiceLabelActive]}>
                {option.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
      <ErrorText>{error}</ErrorText>
    </View>
  );
}

/** Compteur sans unité monétaire (nombre de colis, de boutiques…). */
export function CountTile({ label, value }: { label: string; value: number }) {
  return (
    <View style={styles.tile}>
      <Text style={styles.tileLabel}>{label}</Text>
      <Text style={styles.tileValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderRadius: radii.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: spacing.sm,
  },
  title: { fontFamily: fonts.heading, fontSize: fontSizes.lg, color: colors.text },
  label: { fontFamily: fonts.bodyMedium, fontSize: fontSizes.sm, color: colors.textMuted },
  muted: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.textMuted },
  error: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.danger },
  field: { gap: spacing.xs },
  input: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
    fontFamily: fonts.body,
    fontSize: fontSizes.md,
    color: colors.text,
    backgroundColor: colors.surface,
  },
  inputError: { borderColor: colors.danger },
  button: {
    borderRadius: radii.md,
    paddingVertical: spacing.md,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
  buttonPrimary: { backgroundColor: colors.primary },
  buttonAccent: { backgroundColor: colors.accent },
  buttonDisabled: { backgroundColor: colors.disabled },
  buttonPressed: { opacity: 0.85 },
  buttonText: {
    fontFamily: fonts.bodyMedium,
    fontSize: fontSizes.md,
    color: colors.textOnPrimary,
  },
  tile: {
    flexGrow: 1,
    flexBasis: '45%',
    backgroundColor: colors.surface,
    borderRadius: radii.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.md,
    gap: spacing.xs,
  },
  choices: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  choice: {
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.md,
    borderRadius: radii.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  choiceActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  choiceLabel: { fontFamily: fonts.body, fontSize: fontSizes.sm, color: colors.text },
  choiceLabelActive: { fontFamily: fonts.bodyMedium, color: colors.textOnPrimary },
  tileLabel: { fontFamily: fonts.body, fontSize: fontSizes.xs, color: colors.textMuted },
  tileValue: { fontFamily: fonts.numeric, fontSize: fontSizes.lg, color: colors.text },
  tileValueHighlight: { color: colors.accent, fontSize: fontSizes.xl },
});
