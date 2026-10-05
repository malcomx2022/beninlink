/**
 * S84 (M2) — la carte des alertes douanières, RENDUE.
 *
 * C'est la propriété que S68 et S82 n'avaient pu que lire dans la source : la
 * couleur de gravité est celle de la charte, et « marquer traitée » n'apparaît
 * que sur une alerte en cours.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`,
 * `await fireEvent`) : sans l'attente, `screen` dit « render n'a pas été appelé ».
 */
import { StyleSheet } from 'react-native';
import { fireEvent, render, screen } from '@testing-library/react-native';

import type { CustomsAlert } from '../api/types';
import { CustomsAlertStatus, CustomsLevel } from '../domain/customsLevel';
import { colors } from '../theme/colors';
import { CustomsAlertCard } from './CustomsAlertCard';

function alerte(extra: Partial<CustomsAlert> = {}): CustomsAlert {
  return {
    id: 7,
    parcel_id: 12,
    tracking_id: 'BL-12',
    country_code: 'NG',
    country_name: 'Nigeria',
    goods_category: 'alimentaire',
    category_name: 'Produits alimentaires',
    level: CustomsLevel.BLOCKING,
    level_name: 'Bloquant',
    required_document: 'Certificat NAFDAC',
    message: 'Livraison interdite sans ce document.',
    status: CustomsAlertStatus.PENDING,
    status_name: 'En cours',
    created_at: '05 Oct 2026',
    ...extra,
  };
}

const couleurDe = (texte: string) => StyleSheet.flatten(screen.getByText(texte).props.style).color;

describe('CustomsAlertCard', () => {
  it('ne rend rien pour un colis sans alerte (colis domestique)', async () => {
    const { toJSON } = await render(<CustomsAlertCard alerts={[]} busyId={null} onResolve={jest.fn()} />);
    expect(toJSON()).toBeNull();
  });

  it('peint un niveau BLOQUANT en `danger`, un AVERTISSEMENT en `warning`, une INFO en `info`', async () => {
    await render(
      <CustomsAlertCard
        alerts={[
          alerte({ id: 1, level: CustomsLevel.BLOCKING, level_name: 'Bloquant' }),
          alerte({ id: 2, level: CustomsLevel.WARNING, level_name: 'Avertissement' }),
          alerte({ id: 3, level: CustomsLevel.INFO, level_name: 'Info' }),
        ]}
        busyId={null}
        onResolve={jest.fn()}
      />,
    );
    expect(couleurDe('Bloquant')).toBe(colors.danger);
    expect(couleurDe('Avertissement')).toBe(colors.warning);
    expect(couleurDe('Info')).toBe(colors.info);
    // Et jamais l'ocre des actions clés ni le vert « tout va bien » (S68).
    expect([couleurDe('Avertissement'), couleurDe('Info')]).not.toContain(colors.accent);
    expect([couleurDe('Avertissement'), couleurDe('Info')]).not.toContain(colors.primary);
  });

  it('montre le message et le document requis', async () => {
    await render(<CustomsAlertCard alerts={[alerte()]} busyId={null} onResolve={jest.fn()} />);
    expect(screen.getByText('Livraison interdite sans ce document.')).toBeTruthy();
    expect(screen.getByText(/Certificat NAFDAC/)).toBeTruthy();
    expect(screen.getByText('Nigeria — Produits alimentaires')).toBeTruthy();
  });

  it('offre « Marquer traitée » sur une alerte EN COURS et remonte son identifiant', async () => {
    const onResolve = jest.fn();
    await render(<CustomsAlertCard alerts={[alerte({ id: 41 })]} busyId={null} onResolve={onResolve} />);

    await fireEvent.press(screen.getByText('Marquer traitée'));

    expect(onResolve).toHaveBeenCalledWith(41);
  });

  it('ne l\'offre plus sur une alerte TRAITÉE et montre son statut à la place', async () => {
    await render(
      <CustomsAlertCard
        alerts={[alerte({ status: CustomsAlertStatus.RESOLVED, status_name: 'Traitée' })]}
        busyId={null}
        onResolve={jest.fn()}
      />,
    );
    expect(screen.queryByText('Marquer traitée')).toBeNull();
    expect(screen.getByText('Traitée')).toBeTruthy();
  });
});
