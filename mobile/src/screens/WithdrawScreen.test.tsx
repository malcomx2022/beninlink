/** S146 : écran → vraie session / API / client ; fetch et stockage natif simulés. */
import { Text } from 'react-native';
import { act, fireEvent, render, screen, userEvent } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';
import WithdrawScreen from '../../app/(app)/wallet/withdraw';
import { clearToken, getToken, setToken } from '../api/session';
import { SessionProvider, useSession } from '../session/SessionProvider';
import { t } from '../i18n';
import { champ } from '../testing/fields';
import { fetchCalls, installFetch, jsonResponse, ok } from '../testing/fetchMock';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'cle-api-test', REQUEST_TIMEOUT_MS: 20_000 }));
jest.mock('expo-secure-store', () => ({ getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn() }));
jest.mock('../push', () => ({ desabonnerAppareil: jest.fn() }));
const account = { id: 11, mobile_company: 'MTN MoMo', mobile_no: '2290197000000' };
let fetchMock: jest.Mock;
let accounts: typeof account[];

function Compte() {
  const { loading, user } = useSession();
  if (loading) return <Text>session en cours</Text>;
  return user ? <WithdrawScreen /> : <Text>session fermée</Text>;
}

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  await setToken('jeton-pilote');
  accounts = [];
  fetchMock = installFetch();
  fetchMock.mockImplementation(async (url: string) => {
    if (url.endsWith('/profile')) return ok({ user: { id: 7, name: 'Compte pilote', merchant: { current_balance: 10000 } } });
    if (url.includes('payment-accounts/index')) return ok({ accounts });
    if (url.includes('payment-request/index')) return ok({ payments: [] });
    return ok([]);
  });
});

async function ouvrir() { await render(<SessionProvider><Compte /></SessionProvider>); }
function writes() { return fetchCalls(fetchMock).filter((c) => c.method === 'POST'); }
async function remplir() {
  await screen.findByText(t('wallet.noAccount'));
  await fireEvent.changeText(champ(t('wallet.holderName')), ' Titulaire pilote ');
  await fireEvent.press(screen.getByText('MTN MoMo'));
  await fireEvent.changeText(champ(t('wallet.mobileNo')), '229 0197000000');
}

it('ne prétend pas que la liste est vide avant la réponse et ne propose aucune écriture', async () => {
  const original = fetchMock.getMockImplementation()!;
  let resolve!: (value: Response) => void;
  fetchMock.mockImplementation((url: string) => url.includes('payment-accounts/index') ? new Promise<Response>((r) => { resolve = r; }) : original(url));
  await ouvrir();
  await screen.findByText(t('wallet.availableToWithdraw'));
  try {
    expect(screen.queryByText(t('wallet.noAccount'))).toBeNull();
    expect(screen.queryByText(t('wallet.addAccountAction'))).toBeNull();
    expect(screen.getByText(t('common.loading'))).toBeTruthy();
    expect(writes()).toEqual([]);
  } finally { await act(async () => resolve(ok({ accounts: [] }))); }
  expect(await screen.findByText(t('wallet.noAccount'))).toBeTruthy();
});

it.each(['payment-accounts/index', 'payment-request/index'])('affiche le refus de lecture (%s) et réessaie sans création', async (path) => {
  const original = fetchMock.getMockImplementation()!;
  let failed = false;
  fetchMock.mockImplementation((url: string) => {
    if (url.includes(path) && !failed) {
      failed = true;
      return Promise.resolve(jsonResponse(503, { success: false, message: 'Lecture indisponible.', data: [] }));
    }
    return original(url);
  });
  await ouvrir();
  expect(await screen.findByText('Lecture indisponible.')).toBeTruthy();
  expect(screen.queryByText(t('wallet.noAccount'))).toBeNull();
  await fireEvent.press(screen.getByRole('button', { name: t('common.retry') }));
  expect(await screen.findByText(t('wallet.noAccount'))).toBeTruthy();
  expect(writes()).toEqual([]);
});

it('conserve la confirmation d’ajout si la relecture échoue et réessaie uniquement les lectures', async () => {
  const original = fetchMock.getMockImplementation()!;
  let added = false;
  let failed = false;
  fetchMock.mockImplementation((url: string) => {
    if (url.endsWith('payment-account/store')) { added = true; accounts = [account]; return Promise.resolve(ok([])); }
    if (url.includes('payment-accounts/index') && added && !failed) {
      failed = true;
      return Promise.resolve(jsonResponse(503, { success: false, message: 'Lecture indisponible.', data: [] }));
    }
    return original(url);
  });
  await ouvrir();
  await remplir();
  await fireEvent.press(screen.getByRole('button', { name: t('wallet.addAccountAction') }));
  expect(await screen.findByText(t('wallet.accountAdded'))).toBeTruthy();
  expect(screen.getByText('Lecture indisponible.')).toBeTruthy();
  expect(screen.queryByText(t('wallet.addAccountAction'))).toBeNull();
  await fireEvent.press(screen.getByRole('button', { name: t('common.retry') }));
  expect(await screen.findByText(t('wallet.withdrawTitle'))).toBeTruthy();
  expect(writes()).toHaveLength(1);
  expect(writes()[0]).toMatchObject({ path: 'payment-account/store', body: { payment_method: 'mobile', mobile_holder_name: 'Titulaire pilote', mobile_company: 'MTN MoMo', mobile_no: '2290197000000', account_type: 'Personnel' } });
});

it('laisse corriger un compte refusé en 422 sans confirmer son ajout', async () => {
  const original = fetchMock.getMockImplementation()!;
  fetchMock.mockImplementation((url: string) => url.endsWith('payment-account/store') ? Promise.resolve(jsonResponse(422, { success: false, message: 'Corrigez le compte.', data: { message: { mobile_no: ['Numéro invalide.'] } } })) : original(url));
  await ouvrir();
  await remplir();
  await fireEvent.press(screen.getByRole('button', { name: t('wallet.addAccountAction') }));
  expect(await screen.findByText('Numéro invalide.')).toBeTruthy();
  expect(screen.queryByText(t('wallet.accountAdded'))).toBeNull();
  expect(champ(t('wallet.mobileNo'))).toHaveProp('editable', true);
});

it('déconnecte sur un 401 pendant la lecture', async () => {
  const original = fetchMock.getMockImplementation()!;
  fetchMock.mockImplementation((url: string) => url.includes('payment-accounts/index') ? Promise.resolve(jsonResponse(401, { success: false, message: 'Session expirée.', data: [] })) : original(url));
  await ouvrir();
  expect(await screen.findByText('session fermée')).toBeTruthy();
  expect(await getToken()).toBeNull();
  expect(writes()).toEqual([]);
});

it('fige l’opérateur pendant une création lente', async () => {
  const original = fetchMock.getMockImplementation()!;
  let resolve!: (value: Response) => void;
  fetchMock.mockImplementation((url: string) => url.endsWith('payment-account/store') ? new Promise<Response>((r) => { resolve = r; }) : original(url));
  await ouvrir();
  await remplir();
  await userEvent.setup().press(screen.getByRole('button', { name: t('wallet.addAccountAction') }));
  try { expect(screen.getByText('Moov Money')).toBeDisabled(); }
  finally { accounts = [account]; await act(async () => resolve(ok([]))); }
  expect(await screen.findByText(t('wallet.withdrawTitle'))).toBeTruthy();
});

it('permet une demande entière sur un compte chargé du marchand', async () => {
  accounts = [account];
  await ouvrir();
  await screen.findByText(t('wallet.withdrawTitle'));
  await fireEvent.changeText(champ(t('wallet.withdrawAmount')), '1000');
  await fireEvent.press(screen.getByRole('button', { name: t('wallet.withdrawAction') }));
  expect(await screen.findByText(t('wallet.requestSent'))).toBeTruthy();
  expect(writes()).toHaveLength(1);
  expect(writes()[0]).toMatchObject({ path: 'payment-request/store', body: { amount: 1000, merchant_account: 11 } });
});
