/**
 * Intégration — liste des colis (`app/(app)/parcels.tsx`).
 *
 * Écran → `parcels.ts` → `client.ts` → `fetch` simulé. Garde : la liste est lue
 * sur `parcel/index` avec le Bearer ; les onglets En cours / Livrés / Retours
 * répartissent les colis selon le code backend (`domain/parcelStatus`) ; la carte
 * montre le montant en FCFA entiers et la pastille douane (S101) ; liste vide,
 * erreur serveur et jeton révoqué (401, S144) se disent à l'écran.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { fireEvent, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import ParcelsScreen from '../../app/(app)/parcels';
import { clearToken } from '../api/session';
import type { Parcel } from '../api/types';
import { formatAmount } from '../domain/money';
import { t } from '../i18n';
import { installFetch, jsonResponse, ok, singleCall } from '../testing/fetchMock';

jest.mock('../api/config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-api-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
const mockPush = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ push: mockPush }) }));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  jest.mocked(SecureStore.deleteItemAsync).mockClear();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue('jeton-marchand');
  fetchMock = installFetch();
});

function colis(id: number, status: number, extra: Partial<Parcel> = {}): Parcel {
  return {
    id,
    tracking_id: `BL-${id}`,
    customer_name: `Client ${id}`,
    customer_phone: null,
    customer_address: 'Cotonou',
    invoice_no: null,
    weight: '1 KG',
    total_delivery_amount: 1500,
    cod_amount: 0,
    vat_amount: 0,
    current_payable: 0,
    cash_collection: 12500,
    delivery_type_id: 1,
    deliveryType: 'Même jour',
    status,
    statusName: `Statut ${status}`,
    pickup_date: null,
    delivery_date: null,
    created_at: null,
    parcel_date: null,
    ...extra,
  } as Parcel;
}

it('charge la liste avec le Bearer et répartit les colis par onglet', async () => {
  fetchMock.mockResolvedValueOnce(
    ok({ parcels: [colis(1, 1, { customs_pending: 1 }), colis(2, 9), colis(3, 11)] }),
  );

  await render(<ParcelsScreen />);

  expect(await screen.findByText('BL-1')).toBeTruthy();
  const call = singleCall(fetchMock);
  expect(call.method).toBe('GET');
  expect(call.path).toBe('parcel/index');
  expect(call.headers.Authorization).toBe('Bearer jeton-marchand');

  // En cours : le colis en attente, avec sa pastille douane et son montant en FCFA entiers.
  expect(screen.getByTestId('customs-badge-1')).toBeTruthy();
  expect(screen.getByText(formatAmount(12500))).toBeTruthy();
  expect(screen.queryByText('BL-2')).toBeNull();
  expect(screen.queryByText('BL-3')).toBeNull();

  await fireEvent.press(screen.getByText(t('parcels.tabDelivered')));
  expect(screen.getByText('BL-2')).toBeTruthy();
  expect(screen.queryByText('BL-1')).toBeNull();

  await fireEvent.press(screen.getByText(t('parcels.tabReturns')));
  expect(screen.getByText('BL-3')).toBeTruthy();
  expect(screen.queryByText('BL-2')).toBeNull();
});

it('ouvre la fiche du colis touché et le formulaire de création', async () => {
  fetchMock.mockResolvedValueOnce(ok({ parcels: [colis(4, 1)] }));
  await render(<ParcelsScreen />);

  await fireEvent.press(await screen.findByTestId('parcel-card-4'));
  await fireEvent.press(screen.getByText(`+ ${t('parcels.newParcel')}`));

  expect(mockPush).toHaveBeenNthCalledWith(1, { pathname: '/(app)/parcel/[id]', params: { id: 4 } });
  expect(mockPush).toHaveBeenNthCalledWith(2, '/(app)/parcel/new');
});

it('dit quand il n\'y a aucun colis', async () => {
  fetchMock.mockResolvedValueOnce(ok({ parcels: [] }));
  await render(<ParcelsScreen />);
  expect(await screen.findByText(t('parcels.empty'))).toBeTruthy();
});

it('affiche le message du serveur quand la liste échoue', async () => {
  fetchMock.mockResolvedValueOnce(jsonResponse(500, { success: false, message: 'Erreur interne, réessayez.' }));
  await render(<ParcelsScreen />);
  expect(await screen.findByText('Erreur interne, réessayez.')).toBeTruthy();
  expect(SecureStore.deleteItemAsync).not.toHaveBeenCalled();
});

it('efface le jeton révoqué quand la liste répond 401 (S144)', async () => {
  fetchMock.mockResolvedValueOnce(jsonResponse(401, { message: 'Non authentifié.' }));
  await render(<ParcelsScreen />);
  expect(await screen.findByText('Non authentifié.')).toBeTruthy();
  expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('beninlink.merchant.token');
});
