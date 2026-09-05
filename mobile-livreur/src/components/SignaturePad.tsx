/**
 * Canevas de signature manuscrite : le destinataire signe au doigt, le tracé
 * est exporté en PNG (fichier temporaire) pour partir en multipart dans
 * `signatureImage`, le champ que le socle We Courier attend depuis toujours.
 *
 * Dessin en SVG natif (react-native-svg) + PanResponder : pas de WebView, pas
 * de dépendance supplémentaire à la charte. Fond blanc imposé : la signature
 * doit rester lisible une fois enregistrée côté serveur.
 */
import { forwardRef, useImperativeHandle, useMemo, useRef, useState } from 'react';
import { PanResponder, StyleSheet, View, type GestureResponderEvent } from 'react-native';
import Svg, { Path } from 'react-native-svg';
import { captureRef } from 'react-native-view-shot';

import { colors } from '../theme/colors';
import { radii } from '../theme/typography';

export type SignaturePadHandle = {
  /** URI locale d'un PNG de la signature, ou `null` si rien n'a été tracé. */
  capture: () => Promise<string | null>;
  clear: () => void;
};

type Props = {
  height?: number;
  /** Appelé quand le canevas passe de vide à signé et inversement. */
  onChange?: (hasSignature: boolean) => void;
  /** `true` pendant un tracé : le parent bloque son défilement pour ne pas voler le geste. */
  onDrawingChange?: (drawing: boolean) => void;
};

const STROKE_WIDTH = 2.5;

function point(e: GestureResponderEvent): string {
  const { locationX, locationY } = e.nativeEvent;
  return `${locationX.toFixed(1)} ${locationY.toFixed(1)}`;
}

export const SignaturePad = forwardRef<SignaturePadHandle, Props>(function SignaturePad(
  { height = 180, onChange, onDrawingChange },
  ref,
) {
  const canvasRef = useRef<View>(null);
  const [strokes, setStrokes] = useState<string[]>([]);
  const [current, setCurrent] = useState('');
  // Le tracé en cours vit aussi hors de l'état React : les gestes arrivent
  // plus vite que les rendus, et le PanResponder est créé une seule fois.
  const currentRef = useRef('');
  const strokesRef = useRef<string[]>([]);

  const responder = useMemo(
    () =>
      PanResponder.create({
        onStartShouldSetPanResponder: () => true,
        onStartShouldSetPanResponderCapture: () => true,
        onMoveShouldSetPanResponder: () => true,
        onMoveShouldSetPanResponderCapture: () => true,
        onPanResponderTerminationRequest: () => false,
        onPanResponderGrant: (e) => {
          const p = point(e);
          // Un simple appui laisse un point : le trait de longueur nulle prend le cap rond.
          currentRef.current = `M${p} L${p}`;
          setCurrent(currentRef.current);
          onDrawingChange?.(true);
        },
        onPanResponderMove: (e) => {
          currentRef.current += ` L${point(e)}`;
          setCurrent(currentRef.current);
        },
        onPanResponderRelease: () => finishStroke(),
        onPanResponderTerminate: () => finishStroke(),
      }),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [],
  );

  function finishStroke() {
    const d = currentRef.current;
    currentRef.current = '';
    setCurrent('');
    onDrawingChange?.(false);
    if (!d) return;
    const wasEmpty = strokesRef.current.length === 0;
    strokesRef.current = [...strokesRef.current, d];
    setStrokes(strokesRef.current);
    if (wasEmpty) onChange?.(true);
  }

  useImperativeHandle(ref, () => ({
    async capture() {
      if (strokesRef.current.length === 0 || !canvasRef.current) return null;
      return captureRef(canvasRef, { format: 'png', quality: 1, result: 'tmpfile' });
    },
    clear() {
      const hadSignature = strokesRef.current.length > 0;
      strokesRef.current = [];
      currentRef.current = '';
      setStrokes([]);
      setCurrent('');
      if (hadSignature) onChange?.(false);
    },
  }));

  return (
    <View
      ref={canvasRef}
      collapsable={false}
      style={[styles.canvas, { height }]}
      {...responder.panHandlers}
      accessibilityLabel="Zone de signature"
    >
      <Svg width="100%" height="100%">
        {strokes.map((d, index) => (
          <Path
            key={index}
            d={d}
            stroke={colors.text}
            strokeWidth={STROKE_WIDTH}
            fill="none"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        ))}
        {!!current && (
          <Path
            d={current}
            stroke={colors.text}
            strokeWidth={STROKE_WIDTH}
            fill="none"
            strokeLinecap="round"
            strokeLinejoin="round"
          />
        )}
      </Svg>
    </View>
  );
});

const styles = StyleSheet.create({
  canvas: {
    width: '100%',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.md,
    overflow: 'hidden',
  },
});
