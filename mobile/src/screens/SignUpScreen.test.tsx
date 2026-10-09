import { fireEvent, render, screen } from '@testing-library/react-native';

import SignUpScreen from '../../app/(auth)/signup';
import { signUp } from '../api/auth';
import { ApiError } from '../api/client';
import { t } from '../i18n';

const mockReplace = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ replace: mockReplace }) }));
jest.mock('../api/auth', () => ({ signUp: jest.fn() }));
jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test' }));

beforeEach(() => {
  jest.mocked(signUp).mockReset();
  mockReplace.mockReset();
});

it('affiche les erreurs de saisie et reste sur le formulaire lorsque le serveur refuse', async () => {
  jest.mocked(signUp).mockRejectedValue(new ApiError('Veuillez corriger les champs indiqués.', 422,
    { ifu: ['L’IFU doit contenir 13 chiffres.'], password: ['8 caractères minimum.'] }));
  await render(<SignUpScreen />);
  await fireEvent.press(screen.getByText(t('auth.createAccount')));

  expect(await screen.findByText('L’IFU doit contenir 13 chiffres.')).toBeTruthy();
  expect(screen.getByText('8 caractères minimum.')).toBeTruthy();
  expect(screen.getByText('Veuillez corriger les champs indiqués.')).toBeTruthy();
  expect(mockReplace).not.toHaveBeenCalled();
});

it('transmet le numéro normalisé renvoyé par le serveur à la vérification SMS', async () => {
  jest.mocked(signUp).mockResolvedValue('2290197010006');
  await render(<SignUpScreen />);
  await fireEvent.press(screen.getByText(t('auth.createAccount')));

  expect(mockReplace).toHaveBeenCalledWith({ pathname: '/(auth)/verify-otp',
    params: { mobile: '2290197010006' } });
});
