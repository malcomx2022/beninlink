import { fireEvent, render, screen, waitFor } from '@testing-library/react-native';

import NewParcelScreen from '../../app/(app)/parcel/new';
import { fetchQuote, createParcel } from '../api/parcels';
import type { ParcelQuote } from '../api/types';
import { t } from '../i18n';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test' }));
jest.mock('expo-router', () => ({ useRouter: () => ({ push: jest.fn(), replace: jest.fn() }) }));
jest.mock('../api/customs', () => ({ fetchCustomsReference: jest.fn(async () => null) }));
jest.mock('../api/parcels', () => ({
  createParcel: jest.fn(), fetchQuote: jest.fn(), walletShortfall: jest.fn(),
  fetchParcelFormData: jest.fn(async () => ({
    shops: [{ id: 1, name: 'Boutique' }], deliveryCategories: { 1: { id: 1, title: 'Colis' } },
    deliveryTypes: [{ key: 'same_day', value: '1' }], zones: [{ id: 1, name: 'Cotonou' }], delays: [],
  })),
}));

it('retire le devis et interdit la confirmation dès que le montant change', async () => {
  const quote = { delivery_charge: 1000, cod_charge: 1, cod_amount: 120, vat: 18, vat_amount: 202, total_payable_charges: 1322, current_payable: 10678, customs: null } as ParcelQuote;
  jest.mocked(fetchQuote).mockResolvedValueOnce(quote).mockImplementation(() => new Promise(() => undefined));
  await render(<NewParcelScreen />);
  await fireEvent.press(await screen.findByText('Le jour même'));
  await fireEvent.press(screen.getByText('Cotonou'));
  await waitFor(() => expect(screen.getByRole('button', { name: t('parcels.create') })).not.toBeDisabled());
  await fireEvent.changeText(screen.getByPlaceholderText('12000'), '15000');
  expect(screen.getByRole('button', { name: t('parcels.create') })).toBeDisabled();
  await fireEvent.press(screen.getByRole('button', { name: t('parcels.create') }));
  expect(createParcel).not.toHaveBeenCalled();
});
