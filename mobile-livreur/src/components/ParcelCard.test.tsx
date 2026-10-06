/**
 * S103 — la carte d'une course, RENDUE : la pastille « Douane » suit
 * `customs_pending` (S101), le montant est en FCFA entiers, et les boutons
 * Appeler / Itinéraire ne s'activent qu'avec un numéro ou une adresse.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { StyleSheet } from 'react-native';
import { fireEvent, render, screen } from '@testing-library/react-native';

import type { ParcelSummary } from '../api/types';
import { formatAmount } from '../domain/money';
import { colors } from '../theme/colors';
import { ParcelCard } from './ParcelCard';

function course(extra: Partial<ParcelSummary> = {}): ParcelSummary {
  return {
    id: 12,
    tracking_id: 'BL-12',
    merchant_id: 3,
    merchant_name: 'Boutique Ganhi',
    merchant_mobile: '0197000001',
    merchant_address: 'Ganhi, Cotonou',
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
    status: 15,
    statusName: 'Livreur assigné',
    pickup_date: null,
    delivery_date: null,
    ...extra,
  } as ParcelSummary;
}

const rien = () => undefined;

describe('ParcelCard (livreur)', () => {
  it('montre la pastille « Douane » seulement quand un document reste à collecter', async () => {
    const { rerender } = await render(<ParcelCard parcel={course({ customs_pending: 1 })} onOpen={rien} onCall={rien} onRoute={rien} />);
    const pastille = screen.getByTestId('customs-badge-12');
    expect(pastille).toHaveTextContent('Douane');
    expect(StyleSheet.flatten(pastille.props.style).color).toBe(colors.warning);

    await rerender(<ParcelCard parcel={course({ customs_pending: 0 })} onOpen={rien} onCall={rien} onRoute={rien} />);
    expect(screen.queryByTestId('customs-badge-12')).toBeNull();
  });

  it("n'affiche rien de douanier pour un serveur d'avant S101 (clé absente)", async () => {
    await render(<ParcelCard parcel={course()} onOpen={rien} onCall={rien} onRoute={rien} />);
    expect(screen.queryByTestId('customs-badge-12')).toBeNull();
  });

  it('écrit le montant à encaisser en FCFA entiers et garde le statut du backend', async () => {
    await render(<ParcelCard parcel={course({ cash_collection: '12500.00' })} onOpen={rien} onCall={rien} onRoute={rien} />);
    expect(screen.getByText(formatAmount(12500))).toBeTruthy();
    expect(screen.getByText('Livreur assigné')).toBeTruthy();
  });

  it("ouvre la course au toucher, et n'offre Appeler / Itinéraire qu'avec un numéro et une adresse", async () => {
    const onOpen = jest.fn();
    const onCall = jest.fn();
    await render(
      <ParcelCard parcel={course({ customer_phone: null })} onOpen={onOpen} onCall={onCall} onRoute={rien} />,
    );
    await fireEvent.press(screen.getByTestId('parcel-card-12'));
    expect(onOpen).toHaveBeenCalledTimes(1);

    const appeler = screen.getByRole('button', { name: 'Appeler' });
    expect(appeler).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Itinéraire' })).toBeEnabled();
    await fireEvent.press(appeler);
    expect(onCall).not.toHaveBeenCalled();
  });
});
