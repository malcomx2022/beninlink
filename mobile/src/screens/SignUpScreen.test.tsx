import { fireEvent, render, screen } from '@testing-library/react-native';

import SignUpScreen from '../../app/(auth)/signup';
import { signUp } from '../api/auth';
import { ApiError } from '../api/client';
import { t } from '../i18n';

const mockReplace = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ replace: mockReplace }) }));
jest.mock('../api/auth', () => ({ signUp: jest.fn() }));
jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test' }));
jest.mock('react-native-safe-area-context', () => ({ useSafeAreaInsets: () => ({ top: 0, bottom: 24, left: 0, right: 0 }) }));

async function fillForm(includeRccm = true) {
  await fireEvent.changeText(screen.getByLabelText(t('auth.companyName')), 'Boutique pilote');
  await fireEvent.changeText(screen.getByLabelText(t('auth.managerName')), 'Gérant pilote');
  await fireEvent.changeText(screen.getByLabelText(t('auth.phone')), '01 97 01 00 06');
  await fireEvent.changeText(screen.getByLabelText(t('auth.city')), 'Cotonou');
  await fireEvent.changeText(screen.getByLabelText(`${t('auth.ifu')} (${t('common.required')})`), '3202600010006');
  if (includeRccm) await fireEvent.changeText(screen.getByLabelText(`${t('auth.rccm')} (${t('common.required')})`), 'RB/COT/26 B 10006');
  await fireEvent.changeText(screen.getByLabelText(t('auth.password')), 'pilote2026');
}

beforeEach(() => {
  jest.mocked(signUp).mockReset();
  mockReplace.mockReset();
});

it('affiche les erreurs de saisie et reste sur le formulaire lorsque le serveur refuse', async () => {
  jest.mocked(signUp).mockRejectedValue(new ApiError('Veuillez corriger les champs indiqués.', 422,
    { ifu: ['L’IFU doit contenir 13 chiffres.'], password: ['8 caractères minimum.'] }));
  await render(<SignUpScreen />);
  await fillForm();
  await fireEvent.press(screen.getByText(t('auth.createAccount')));

  expect(await screen.findByText('L’IFU doit contenir 13 chiffres.')).toBeTruthy();
  expect(screen.getByText('8 caractères minimum.')).toBeTruthy();
  expect(screen.getByText('Veuillez corriger les champs indiqués.')).toBeTruthy();
  expect(mockReplace).not.toHaveBeenCalled();
});

it('transmet le numéro normalisé renvoyé par le serveur à la vérification SMS', async () => {
  jest.mocked(signUp).mockResolvedValue('2290197010006');
  await render(<SignUpScreen />);
  await fillForm();
  await fireEvent.press(screen.getByText(t('auth.createAccount')));

  expect(mockReplace).toHaveBeenCalledWith({ pathname: '/(auth)/verify-otp',
    params: { mobile: '2290197010006' } });
});

it('ne prend pas l’exemple RCCM pour une valeur et refuse le champ vide avant l’API', async () => {
  await render(<SignUpScreen />);
  await fillForm(false);
  await fireEvent.press(screen.getByText(t('auth.createAccount')));
  expect(signUp).not.toHaveBeenCalled();
  expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
  expect(screen.getByText(t('auth.signupCorrectionHint'))).toBeTruthy();
});
