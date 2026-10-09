import { act, fireEvent, render, screen, userEvent } from '@testing-library/react-native';

import VerifyOtpScreen from '../../app/(auth)/verify-otp';
import { t } from '../i18n';
import { resendOtp } from '../api/auth';
import { ApiError } from '../api/client';

const mockVerifyOtp = jest.fn();
let mockMobile: string | undefined;
jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test' }));
jest.mock('expo-router', () => ({ useLocalSearchParams: () => ({ mobile: mockMobile }) }));
jest.mock('../session/SessionProvider', () => ({ useSession: () => ({ verifyOtp: mockVerifyOtp }) }));
jest.mock('../api/auth', () => ({ resendOtp: jest.fn() }));

beforeEach(() => {
  mockVerifyOtp.mockReset().mockResolvedValue(undefined);
  jest.mocked(resendOtp).mockReset().mockResolvedValue(undefined);
  mockMobile = '2290197010006';
});

it('bloque les actions pendant le renvoi et efface l’ancien code seulement après réussite', async () => {
  let resolve!: () => void;
  jest.mocked(resendOtp).mockReturnValue(new Promise<void>((done) => { resolve = done; }));
  await render(<VerifyOtpScreen />);
  await fireEvent.changeText(screen.getByPlaceholderText('•••••'), '12345');
  const resendButton = screen.getByRole('button', { name: t('auth.resendOtp') });
  await userEvent.setup().press(resendButton);
  expect(screen.getByText(t('auth.verify'))).toBeDisabled();
  expect(screen.getByPlaceholderText('•••••').props.editable).toBe(false);
  expect(resendButton).toBeDisabled();
  await userEvent.setup().press(resendButton);
  await fireEvent.press(screen.getByText(t('auth.verify')));
  expect(resendOtp).toHaveBeenCalledTimes(1);
  expect(mockVerifyOtp).not.toHaveBeenCalled();
  await act(async () => resolve());
  expect(screen.getByPlaceholderText('•••••').props.value).toBe('');
  expect(screen.getByText(t('auth.otpResent'))).toBeTruthy();
  expect(screen.getByText(t('auth.resendOtp'))).toBeEnabled();
});

it('conserve le code et permet de réessayer après un échec de renvoi', async () => {
  jest.mocked(resendOtp).mockRejectedValueOnce(new ApiError(t('errors.network'), 0));
  await render(<VerifyOtpScreen />);
  await fireEvent.changeText(screen.getByPlaceholderText('•••••'), '12345');
  await fireEvent.press(screen.getByText(t('auth.resendOtp')));
  expect(screen.getByText(t('errors.network'))).toBeTruthy();
  expect(screen.getByPlaceholderText('•••••').props.value).toBe('12345');
  await fireEvent.press(screen.getByText(t('auth.resendOtp')));
  expect(resendOtp).toHaveBeenCalledTimes(2);
  expect(screen.getByText(t('auth.otpResent'))).toBeTruthy();
});

it('vérifie le code à cinq chiffres avec le numéro reçu de l’inscription', async () => {
  await render(<VerifyOtpScreen />);
  const input = screen.getByPlaceholderText('•••••');
  expect(input.props.maxLength).toBe(5);
  await fireEvent.changeText(input, '12345');
  await fireEvent.press(screen.getByText(t('auth.verify')));
  expect(mockVerifyOtp).toHaveBeenCalledWith('2290197010006', '12345');
});

it('ne tente pas de connexion lorsque le numéro est absent de la route', async () => {
  mockMobile = undefined;
  await render(<VerifyOtpScreen />);
  await fireEvent.changeText(screen.getByPlaceholderText('•••••'), '12345');
  await fireEvent.press(screen.getByText(t('auth.verify')));
  expect(mockVerifyOtp).not.toHaveBeenCalled();
  expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
});
