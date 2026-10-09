import { fireEvent, render, screen, waitFor } from '@testing-library/react-native';

import WalletScreen from '../../app/(app)/wallet';
import { fetchRechargeStatus, initiateRecharge } from '../api/fedapay';
import { storage } from '../api/session';
import { openBrowserAsync } from 'expo-web-browser';
import { t } from '../i18n';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test' }));
jest.mock('expo-router', () => ({
  Link: 'Text', useLocalSearchParams: () => ({}), useFocusEffect: jest.fn(),
}));
jest.mock('expo-web-browser', () => ({ openBrowserAsync: jest.fn(async () => ({})) }));
jest.mock('../api/fedapay', () => ({ initiateRecharge: jest.fn(), fetchRechargeStatus: jest.fn() }));
jest.mock('../api/wallet', () => ({ fetchWalletHistory: jest.fn(async () => ({ items: [], hasMore: false })) }));
jest.mock('../api/session', () => ({ storage: { get: jest.fn(), set: jest.fn(), remove: jest.fn() } }));
jest.mock('../session/SessionProvider', () => ({
  useSession: () => ({ user: { id: 7, merchant: { wallet_balance: 0 } }, refresh: jest.fn(async () => undefined) }),
}));

beforeEach(() => {
  jest.clearAllMocks();
  // Une réponse mockResolvedValueOnce non consommée ne doit pas passer
  // au scénario suivant, même si un test précédent échoue.
  jest.mocked(fetchRechargeStatus).mockReset();
  jest.mocked(storage.get).mockResolvedValue(null);
  jest.mocked(storage.set).mockResolvedValue(undefined);
  jest.mocked(storage.remove).mockResolvedValue(undefined);
  jest.mocked(initiateRecharge).mockResolvedValue({ reference: 'REF-7', payment_url: 'https://example.test/pay' });
});

it('garde une recharge pending et permet de vérifier le webhook tardif', async () => {
  jest.mocked(fetchRechargeStatus).mockResolvedValueOnce({ reference: 'REF-7', status: 'pending', amount: 5000, wallet_balance: 0 })
    .mockResolvedValueOnce({ reference: 'REF-7', status: 'approved', amount: 5000, wallet_balance: 5000 });
  await render(<WalletScreen />);
  await fireEvent.changeText(screen.getByPlaceholderText('5000'), '5000');
  await waitFor(() => expect(screen.getByText(t('wallet.rechargeAction'))).toBeEnabled());
  await fireEvent.press(screen.getByText(t('wallet.rechargeAction')));
  expect(await screen.findByText(t('wallet.rechargePending'))).toBeTruthy();
  expect(storage.set).toHaveBeenCalledWith('beninlink.merchant.7.recharge', JSON.stringify({ reference: 'REF-7', payment_url: 'https://example.test/pay' }));
  expect(storage.remove).not.toHaveBeenCalled();
  await waitFor(() => expect(screen.getByText(t('wallet.checkRecharge'))).toBeEnabled());
  await fireEvent.press(screen.getByText(t('wallet.checkRecharge')));
  expect(await screen.findByText(t('wallet.rechargeApproved'))).toBeTruthy();
  expect(storage.remove).toHaveBeenCalledWith('beninlink.merchant.7.recharge');
});

it('rouvre le même paiement après redémarrage sans créer une seconde transaction', async () => {
  jest.mocked(storage.get).mockResolvedValue(JSON.stringify({ reference: 'REF-7', payment_url: 'https://example.test/pay' }));
  jest.mocked(fetchRechargeStatus).mockResolvedValue({ reference: 'REF-7', status: 'pending', amount: 5000, wallet_balance: 0 });
  await render(<WalletScreen />);
  await screen.findByText(t('wallet.resumePayment'));
  await waitFor(() => expect(screen.getByText(t('wallet.resumePayment'))).toBeEnabled());
  await fireEvent.press(screen.getByText(t('wallet.resumePayment')));
  expect(await screen.findByText(t('wallet.rechargePending'))).toBeTruthy();
  expect(openBrowserAsync).toHaveBeenCalledWith('https://example.test/pay');
  expect(initiateRecharge).not.toHaveBeenCalled();
  expect(storage.remove).not.toHaveBeenCalled();
});

it.each(['declined', 'canceled'] as const)('reprend une référence persistée et distingue %s de pending', async (status) => {
  jest.mocked(storage.get).mockResolvedValue('REF-7');
  jest.mocked(fetchRechargeStatus).mockResolvedValue({ reference: 'REF-7', status, amount: 5000, wallet_balance: 0 });
  await render(<WalletScreen />);
  await waitFor(() => expect(screen.getByText(t('wallet.checkRecharge'))).toBeEnabled());
  await fireEvent.press(screen.getByText(t('wallet.checkRecharge')));
  expect(await screen.findByText(t(status === 'declined' ? 'wallet.rechargeDeclined' : 'wallet.rechargeCanceled'))).toBeTruthy();
  expect(screen.queryByText(t('wallet.rechargePending'))).toBeNull();
  expect(initiateRecharge).not.toHaveBeenCalled();
});
