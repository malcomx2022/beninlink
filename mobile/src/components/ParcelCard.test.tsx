/**
 * S103 — la carte d'un colis, RENDUE : la pastille « Douane » suit
 * `customs_pending` (S101), le montant est en FCFA entiers, le statut est
 * celui du backend.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { StyleSheet } from 'react-native';
import { fireEvent, render, screen } from '@testing-library/react-native';

import type { Parcel } from '../api/types';
import { formatAmount } from '../domain/money';
import { colors } from '../theme/colors';
import { ParcelCard } from './ParcelCard';

function colis(extra: Partial<Parcel> = {}): Parcel {
  return {
    id: 12,
    tracking_id: 'BL-12',
    customer_name: 'Awa Zinsou',
    customer_phone: '0196000002',
    customer_address: 'Fidjrossè, Cotonou',
    invoice_no: null,
    weight: '1 KG',
    total_delivery_amount: 1500,
    cod_amount: 12500,
    vat_amount: 0,
    current_payable: 11000,
    cash_collection: 12500,
    delivery_type_id: 1,
    deliveryType: 'Même jour',
    status: 1,
    statusName: 'En attente',
    pickup_date: null,
    delivery_date: null,
    created_at: '06 Oct 2026, 09:00 AM',
    parcel_date: '06 Oct 2026',
    ...extra,
  } as Parcel;
}

describe('ParcelCard (marchand)', () => {
  it('montre la pastille « Douane » seulement quand une alerte est en cours', async () => {
    const { rerender } = await render(<ParcelCard parcel={colis({ customs_pending: 2 })} onOpen={() => undefined} />);
    const pastille = screen.getByTestId('customs-badge-12');
    expect(pastille).toHaveTextContent('Douane');
    expect(StyleSheet.flatten(pastille.props.style).color).toBe(colors.warning);

    await rerender(<ParcelCard parcel={colis({ customs_pending: 0 })} onOpen={() => undefined} />);
    expect(screen.queryByTestId('customs-badge-12')).toBeNull();
  });

  it("n'affiche rien de douanier pour un serveur d'avant S101 (clé absente)", async () => {
    await render(<ParcelCard parcel={colis()} onOpen={() => undefined} />);
    expect(screen.queryByTestId('customs-badge-12')).toBeNull();
  });

  it('écrit le montant en FCFA entiers, garde le statut du backend et le type de livraison', async () => {
    await render(<ParcelCard parcel={colis({ cash_collection: '12500.00' })} onOpen={() => undefined} />);
    expect(screen.getByText(formatAmount(12500))).toBeTruthy();
    expect(screen.getByText('En attente')).toBeTruthy();
    expect(screen.getByText('Même jour')).toBeTruthy();
  });

  it('ouvre la fiche au toucher', async () => {
    const onOpen = jest.fn();
    await render(<ParcelCard parcel={colis()} onOpen={onOpen} />);
    await fireEvent.press(screen.getByTestId('parcel-card-12'));
    expect(onOpen).toHaveBeenCalledTimes(1);
  });
});
