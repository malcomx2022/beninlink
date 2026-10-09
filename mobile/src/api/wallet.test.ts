/**
 * Argent du marchand (`wallet.ts`, `fedapay.ts`) — ce qui part au backend, en entiers XOF.
 *
 * Garde : l'historique du porte-monnaie se lit page par page et dit s'il en reste
 * (bloc `page` S78, sinon repli sur la page pleine) ; le compte Mobile Money part
 * avec les champs préfixés que le backend attend ; un retrait et une recharge
 * envoient des montants ENTIERS ; la recharge ne fait qu'obtenir `payment_url`, et
 * son état se demande au serveur (le retour du navigateur ne prouve rien).
 */
import * as SecureStore from 'expo-secure-store';

import { fetchRechargeStatus, initiateRecharge } from './fedapay';
import { setToken } from './session';
import {
  WALLET_HISTORY_PER_PAGE,
  createMobileMoneyAccount,
  createPaymentRequest,
  fetchPaymentAccounts,
  fetchPaymentRequests,
  fetchWalletHistory,
} from './wallet';
import { installFetch, ok, singleCall } from '../testing/fetchMock';

jest.mock('./config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-api-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  await setToken('jeton-marchand');
  fetchMock = installFetch();
});

function entree(id: number) {
  return { id, amount: 1000, type: 'credit', source: 'fedapay', created_at: '09 Oct 2026' };
}

describe('historique du porte-monnaie', () => {
  it('demande la page voulue, avec le jeton, et lit `page` pour savoir s\'il en reste', async () => {
    fetchMock.mockResolvedValueOnce(
      ok({ entries: [entree(1), entree(2)] }, { page: { current: 2, per_page: 10, last: 3, total: 22 } }),
    );

    const page = await fetchWalletHistory(2);

    const call = singleCall(fetchMock);
    expect(call.method).toBe('GET');
    expect(call.path).toBe('wallet/history?page=2');
    expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
    expect(page.items.map((e) => e.id)).toEqual([1, 2]);
    expect(page.hasMore).toBe(true); // 2 lignes seulement, mais le serveur dit page 2 sur 3
  });

  it('sans `page` (serveur d\'avant S78), une page pleine laisse supposer une suite', async () => {
    const pleine = Array.from({ length: WALLET_HISTORY_PER_PAGE }, (_, i) => entree(i));
    fetchMock.mockResolvedValueOnce(ok({ entries: pleine })).mockResolvedValueOnce(ok({ entries: [entree(1)] }));

    expect((await fetchWalletHistory()).hasMore).toBe(true);
    expect((await fetchWalletHistory()).hasMore).toBe(false);
  });

  it('rend une liste vide quand `entries` manque', async () => {
    fetchMock.mockResolvedValueOnce(ok({}));
    await expect(fetchWalletHistory()).resolves.toEqual({ items: [], hasMore: false });
  });
});

describe('comptes et demandes de retrait', () => {
  it('lit les comptes de règlement, et une liste vide si la clé manque', async () => {
    fetchMock.mockResolvedValueOnce(ok({ accounts: [{ id: 4 }] })).mockResolvedValueOnce(ok({}));

    await expect(fetchPaymentAccounts()).resolves.toEqual([{ id: 4 }]);
    await expect(fetchPaymentAccounts()).resolves.toEqual([]);
    expect(fetchMock.mock.calls[0]![0]).toBe('https://example.test/api/v10/payment-accounts/index');
  });

  it('envoie un compte Mobile Money avec les champs `mobile_*` du backend', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await createMobileMoneyAccount({ holder_name: 'Awa Zinsou', operator: 'Moov Money', mobile_no: '22995000000' });

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('payment-account/store');
    expect(call.body).toEqual({
      payment_method: 'mobile',
      mobile_holder_name: 'Awa Zinsou',
      mobile_company: 'Moov Money',
      mobile_no: '22995000000',
      account_type: 'Personnel',
    });
  });

  it('lit les demandes de retrait sous `payments`', async () => {
    fetchMock.mockResolvedValueOnce(ok({ payments: [{ id: 9, amount: 5000 }] }));
    await expect(fetchPaymentRequests()).resolves.toEqual([{ id: 9, amount: 5000 }]);
    expect(singleCall(fetchMock).path).toBe('payment-request/index');
  });

  it('demande un retrait en FCFA entiers et omet une description vide', async () => {
    fetchMock.mockResolvedValueOnce(ok([])).mockResolvedValueOnce(ok([]));

    await createPaymentRequest({ amount: 15000.6, merchant_account: 4 });
    await createPaymentRequest({ amount: 2000, merchant_account: 4, description: 'Fin de semaine' });

    const [sans, avec] = fetchMock.mock.calls.map(([, init]) => JSON.parse(init.body));
    expect(fetchMock.mock.calls[0]![0]).toBe('https://example.test/api/v10/payment-request/store');
    expect(sans).toEqual({ amount: 15001, merchant_account: 4 });
    expect(avec).toEqual({ amount: 2000, merchant_account: 4, description: 'Fin de semaine' });
  });
});

describe('recharge FedaPay', () => {
  it('initie la recharge avec un montant entier et rend la référence et payment_url', async () => {
    fetchMock.mockResolvedValueOnce(ok({ reference: 'REF-7', payment_url: 'https://pay.example.test/REF-7' }));

    await expect(initiateRecharge(5000.4)).resolves.toEqual({
      reference: 'REF-7',
      payment_url: 'https://pay.example.test/REF-7',
    });

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('fedapay/initiate');
    expect(call.body).toEqual({ amount: 5000 });
    expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
  });

  it("demande l'état au serveur par la référence et rend le solde qu'il annonce", async () => {
    const etat = { reference: 'REF-7', status: 'approved', amount: 5000, wallet_balance: 12500 };
    fetchMock.mockResolvedValueOnce(ok(etat));

    await expect(fetchRechargeStatus('REF-7')).resolves.toEqual(etat);

    const call = singleCall(fetchMock);
    expect(call.method).toBe('GET');
    expect(call.path).toBe('fedapay/status/REF-7');
  });
});
