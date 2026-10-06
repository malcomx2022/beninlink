/**
 * S95 — la douane sur la course, RENDUE : la gravité porte la couleur de la
 * charte, le document est nommé, et rien ne permet de « traiter » (le livreur lit).
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { StyleSheet } from 'react-native';
import { render, screen } from '@testing-library/react-native';

import type { CustomsAlert } from '../api/types';
import { CustomsAlertStatus, CustomsLevel } from '../domain/customsLevel';
import { colors } from '../theme/colors';
import { CustomsNotice } from './CustomsNotice';

function alerte(extra: Partial<CustomsAlert> = {}): CustomsAlert {
  return {
    id: 7,
    parcel_id: 12,
    tracking_id: 'BL-12',
    country_code: 'TG',
    country_name: 'Togo',
    goods_category: 'textile',
    category_name: 'Textile',
    level: CustomsLevel.WARNING,
    level_name: 'Avertissement',
    required_document: 'Certificat de circulation UEMOA',
    message: 'Document obligatoire.',
    status: CustomsAlertStatus.PENDING,
    status_name: 'En cours',
    created_at: '06 Oct 2026',
    ...extra,
  };
}

const couleurDe = (texte: string) => StyleSheet.flatten(screen.getByText(texte).props.style).color;

describe('CustomsNotice', () => {
  it("ne rend rien pour une course sans alerte (un colis domestique)", async () => {
    await render(<CustomsNotice alerts={[]} />);
    expect(screen.queryByTestId('customs-notice')).toBeNull();
  });

  it('nomme le document à collecter et colore la gravité comme la charte', async () => {
    await render(<CustomsNotice alerts={[alerte(), alerte({ id: 8, level: CustomsLevel.BLOCKING, level_name: 'Bloquant', required_document: 'Certificat NAFDAC' })]} />);
    expect(screen.getByText(/Certificat de circulation UEMOA/)).toBeTruthy();
    expect(couleurDe('Avertissement')).toBe(colors.warning);
    expect(couleurDe('Bloquant')).toBe(colors.danger);
  });

  it('ne propose aucune action : le livreur lit, le transporteur traite', async () => {
    await render(<CustomsNotice alerts={[alerte()]} />);
    expect(screen.queryByRole('button')).toBeNull();
    expect(screen.getByText('En cours')).toBeTruthy();
  });
});
