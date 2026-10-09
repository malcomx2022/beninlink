import { fireEvent, render, screen } from '@testing-library/react-native';

import VerifyOtpScreen from '../../app/(auth)/verify-otp';
import { t } from '../i18n';

const mockVerifyOtp = jest.fn();
let mockMobile: string | undefined;
jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test' }));
jest.mock('expo-router', () => ({ useLocalSearchParams: () => ({ mobile: mockMobile }) }));
jest.mock('../session/SessionProvider', () => ({ useSession: () => ({ verifyOtp: mockVerifyOtp }) }));
jest.mock('../api/auth', () => ({ resendOtp: jest.fn() }));

beforeEach(() => {
  mockVerifyOtp.mockReset().mockResolvedValue(undefined);
  mockMobile = '2290197010006';
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
