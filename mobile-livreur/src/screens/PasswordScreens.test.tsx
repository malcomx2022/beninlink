/**
 * Mots de passe du livreur, de bout en bout (écran → `auth.ts` → vrai client d'API → `fetch` simulé) :
 * - oublié (S98) : `password/email` sans jeton, message du serveur affiché ;
 * - réinitialisé : `password/reset`, 8 caractères au moins (S134) et confirmation vérifiés AVANT l'appel,
 *   erreurs du serveur sous leur champ ;
 * - changé depuis le profil : `update-password` avec le Bearer, même minimum de 8 caractères.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`, `await fireEvent`).
 */
import { act, fireEvent, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import ForgotPasswordScreen from '../../app/(auth)/forgot-password';
import ResetPasswordScreen from '../../app/(auth)/reset-password';
import PasswordScreen from '../../app/(app)/profile/password';
import { clearToken, setToken } from '../api/session';
import { t } from '../i18n';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test', REQUEST_TIMEOUT_MS: 20_000 }));
let mockParams: Record<string, string> = {};
const mockBack = jest.fn();
jest.mock('expo-router', () => ({
  Link: 'Text',
  useLocalSearchParams: () => mockParams,
  useRouter: () => ({ back: mockBack }),
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();
type Champ = ReturnType<typeof screen.getByText>;

/** Le n-ième appel à `fetch` ; échoue lisiblement s'il n'a pas eu lieu. */
function appel(i: number, appels: Appel[] = fetchMock.mock.calls): Appel {
  const trouve = appels[i];
  if (!trouve) throw new Error(`pas d'appel n° ${i} à fetch`);
  return trouve;
}
const BASE = 'https://example.test/api/v10/';
const trop_court = t('errors.passwordTooShort').replace('{n}', '8');

function reponse(status: number, corps: unknown) {
  return { ok: status >= 200 && status < 300, status, text: async () => JSON.stringify(corps) };
}

/** Rend l'écran et rend ses champs de saisie, dans l'ordre (1, 3 ou 4 selon l'écran). */
async function afficher(element: React.ReactElement) {
  const rendu = await render(element);
  const champs = rendu.container.queryAll((n) => n.type === 'TextInput');
  if (champs.length === 0) throw new Error('aucun champ de saisie rendu');
  return champs as [Champ, Champ, Champ, Champ];
}

function corps(i = 0) {
  return JSON.parse(appel(i)[1].body as string);
}

beforeAll(() => {
  globalThis.fetch = fetchMock as unknown as typeof fetch;
});

beforeEach(async () => {
  jest.resetAllMocks();
  mockParams = {};
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
});

afterEach(() => {
  jest.clearAllTimers();
  jest.useRealTimers();
});

describe('mot de passe oublié', () => {
  it("exige l'adresse avant d'appeler l'API", async () => {
    await afficher(<ForgotPasswordScreen />);
    await fireEvent.press(screen.getByText(t('auth.sendResetLink')));

    expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('demande le lien sur password/email, sans jeton, et affiche le message du serveur', async () => {
    await setToken('jeton-traine');
    fetchMock.mockResolvedValueOnce(
      reponse(200, { success: true, message: 'Lien envoyé', data: { message: 'Nous vous avons envoyé le lien par e-mail.' } }),
    );
    await afficher(<ForgotPasswordScreen />);

    await fireEvent.changeText(screen.getByPlaceholderText('prenom.nom@exemple.bj'), '  awa@exemple.bj ');
    await fireEvent.press(screen.getByText(t('auth.sendResetLink')));

    expect(await screen.findByText('Nous vous avons envoyé le lien par e-mail.')).toBeTruthy();
    expect(screen.getByText(t('auth.resetNextStep'))).toBeTruthy();
    const [url, init] = appel(0);
    expect(url).toBe(BASE + 'password/email');
    expect(init.method).toBe('POST');
    expect(init.headers.Authorization).toBeUndefined();
    expect(corps()).toEqual({ email: 'awa@exemple.bj' });
  });

  it('se replie sur le texte de l’app quand le serveur ne dit rien', async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, data: [] }));
    await afficher(<ForgotPasswordScreen />);

    await fireEvent.changeText(screen.getByPlaceholderText('prenom.nom@exemple.bj'), 'awa@exemple.bj');
    await fireEvent.press(screen.getByText(t('auth.sendResetLink')));

    expect(await screen.findByText(t('auth.resetSent'))).toBeTruthy();
  });

  it('affiche la limite de cadence du serveur (S96)', async () => {
    fetchMock.mockResolvedValueOnce(reponse(429, { message: 'Trop de tentatives. Réessayez dans une minute.' }));
    await afficher(<ForgotPasswordScreen />);

    await fireEvent.changeText(screen.getByPlaceholderText('prenom.nom@exemple.bj'), 'awa@exemple.bj');
    await fireEvent.press(screen.getByText(t('auth.sendResetLink')));

    expect(await screen.findByText('Trop de tentatives. Réessayez dans une minute.')).toBeTruthy();
  });
});

describe('réinitialisation', () => {
  it('reprend jeton et adresse du lien profond', async () => {
    mockParams = { token: 'jeton-du-lien', email: 'awa@exemple.bj' };
    await afficher(<ResetPasswordScreen />);

    expect(screen.getByDisplayValue('jeton-du-lien')).toBeTruthy();
    expect(screen.getByDisplayValue('awa@exemple.bj')).toBeTruthy();
  });

  it('refuse 7 caractères sans appeler l’API (S134 : 8 au moins)', async () => {
    mockParams = { token: 'abc', email: 'awa@exemple.bj' };
    const [, , motDePasse, confirmation] = await afficher(<ResetPasswordScreen />);

    await fireEvent.changeText(motDePasse, '1234567');
    await fireEvent.changeText(confirmation, '1234567');
    await fireEvent.press(screen.getByText(t('auth.resetPassword')));

    expect(screen.getByText(trop_court)).toBeTruthy();
    expect(trop_court).toContain('8');
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('refuse une confirmation différente sans appeler l’API', async () => {
    mockParams = { token: 'abc', email: 'awa@exemple.bj' };
    const [, , motDePasse, confirmation] = await afficher(<ResetPasswordScreen />);

    await fireEvent.changeText(motDePasse, '12345678');
    await fireEvent.changeText(confirmation, '12345679');
    await fireEvent.press(screen.getByText(t('auth.resetPassword')));

    expect(screen.getByText(t('errors.passwordMismatch'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('envoie 8 caractères confirmés à password/reset et propose de se reconnecter', async () => {
    fetchMock.mockResolvedValueOnce(
      reponse(200, { success: true, data: { message: 'Votre mot de passe a été réinitialisé.' } }),
    );
    const [email, jeton, motDePasse, confirmation] = await afficher(<ResetPasswordScreen />);

    await fireEvent.changeText(email, ' awa@exemple.bj ');
    await fireEvent.changeText(jeton, '  abc123  ');
    await fireEvent.changeText(motDePasse, '12345678');
    await fireEvent.changeText(confirmation, '12345678');
    await fireEvent.press(screen.getByText(t('auth.resetPassword')));

    expect(await screen.findByText('Votre mot de passe a été réinitialisé.')).toBeTruthy();
    expect(screen.getByText(t('auth.backToSignIn'))).toBeTruthy();
    const [url, init] = appel(0);
    expect(url).toBe(BASE + 'password/reset');
    expect(init.headers.Authorization).toBeUndefined();
    expect(corps()).toEqual({
      token: 'abc123',
      email: 'awa@exemple.bj',
      password: '12345678',
      password_confirmation: '12345678',
    });
  });

  it('place les erreurs du serveur sous leur champ', async () => {
    mockParams = { token: 'perime', email: 'awa@exemple.bj' };
    fetchMock.mockResolvedValueOnce(
      reponse(422, { message: 'Ce jeton de réinitialisation est invalide.', errors: { token: ['Jeton expiré.'] } }),
    );
    const [, , motDePasse, confirmation] = await afficher(<ResetPasswordScreen />);

    await fireEvent.changeText(motDePasse, '12345678');
    await fireEvent.changeText(confirmation, '12345678');
    await fireEvent.press(screen.getByText(t('auth.resetPassword')));

    expect(await screen.findByText('Jeton expiré.')).toBeTruthy();
    expect(screen.getByText('Ce jeton de réinitialisation est invalide.')).toBeTruthy();
    expect(screen.queryByText(t('auth.backToSignIn'))).toBeNull();
  });
});

describe('changement depuis le profil', () => {
  const tropCourtProfil = t('profile.passwordTooShort').replace('{n}', '8');

  it('annonce et applique le minimum de 8 caractères', async () => {
    await setToken('jeton-livreur');
    const [ancien, nouveau, confirmation] = await afficher(<PasswordScreen />);
    // La consigne est affichée d'emblée.
    expect(screen.getAllByText(tropCourtProfil)).toHaveLength(1);

    await fireEvent.changeText(ancien, 'ancien-mdp');
    await fireEvent.changeText(nouveau, '1234567');
    await fireEvent.changeText(confirmation, '1234567');
    await fireEvent.press(screen.getByText(t('common.confirm')));

    expect(screen.getAllByText(tropCourtProfil)).toHaveLength(2); // consigne + erreur
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('envoie update-password avec le Bearer puis revient au profil', async () => {
    jest.useFakeTimers();
    await setToken('jeton-livreur');
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, message: 'Mot de passe modifié.', data: [] }));
    const [ancien, nouveau, confirmation] = await afficher(<PasswordScreen />);

    await fireEvent.changeText(ancien, 'ancien-mdp');
    await fireEvent.changeText(nouveau, 'nouveau-8');
    await fireEvent.changeText(confirmation, 'nouveau-8');
    await fireEvent.press(screen.getByText(t('common.confirm')));

    expect(await screen.findByText(t('profile.passwordChanged'))).toBeTruthy();
    const [url, init] = appel(0);
    expect(url).toBe(BASE + 'update-password');
    expect(init.method).toBe('PUT');
    expect(init.headers.Authorization).toBe('Bearer jeton-livreur');
    expect(corps()).toEqual({ old_password: 'ancien-mdp', new_password: 'nouveau-8', confirm_password: 'nouveau-8' });

    await act(async () => {
      jest.advanceTimersByTime(800);
    });
    expect(mockBack).toHaveBeenCalledTimes(1);
  });

  it("affiche l'erreur du serveur sous l'ancien mot de passe", async () => {
    await setToken('jeton-livreur');
    fetchMock.mockResolvedValueOnce(
      reponse(422, { message: 'Le mot de passe actuel est incorrect.', errors: { old_password: ['Incorrect.'] } }),
    );
    const [ancien, nouveau, confirmation] = await afficher(<PasswordScreen />);

    await fireEvent.changeText(ancien, 'faux-mdp');
    await fireEvent.changeText(nouveau, 'nouveau-8');
    await fireEvent.changeText(confirmation, 'nouveau-8');
    await fireEvent.press(screen.getByText(t('common.confirm')));

    expect(await screen.findByText('Incorrect.')).toBeTruthy();
    expect(screen.getByText('Le mot de passe actuel est incorrect.')).toBeTruthy();
    expect(mockBack).not.toHaveBeenCalled();
  });
});
