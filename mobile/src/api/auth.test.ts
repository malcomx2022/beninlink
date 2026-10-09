import { verifyOtp } from './auth';
import { api } from './client';
import { endpoints } from './endpoints';
import { setToken } from './session';

jest.mock('./client', () => ({ api: { post: jest.fn() } }));
jest.mock('./session', () => ({ setToken: jest.fn() }));

beforeEach(() => jest.resetAllMocks());

it('transmet le numéro avec le code SMS avant de conserver le jeton', async () => {
  const user = { id: 7 };
  jest.mocked(api.post).mockResolvedValue({ token: 'test-token', user });

  await expect(verifyOtp(' 2290197010006 ', ' 12345 ')).resolves.toEqual(user);

  expect(api.post).toHaveBeenCalledWith(endpoints.otpVerification,
    { mobile: '2290197010006', otp: '12345' }, { authenticated: false });
  expect(setToken).toHaveBeenCalledWith('test-token');
});

it('ne conserve aucun jeton lorsque la vérification du code échoue', async () => {
  jest.mocked(api.post).mockRejectedValue(new Error('Code refusé'));

  await expect(verifyOtp('2290197010006', '12345')).rejects.toThrow('Code refusé');
  expect(setToken).not.toHaveBeenCalled();
});
