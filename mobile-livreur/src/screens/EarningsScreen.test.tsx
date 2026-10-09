/**
 * Onglet Gains, de bout en bout : écran → `fetchProfile()`, `fetchIncomeExpense()`,
 * `fetchParcelPaymentLogs()` → vrai client d'API → `fetch` simulé. Les trois chiffres viennent du serveur
 * (aucun calcul dans l'app) et s'affichent en FCFA entiers, même servis en chaîne décimale ; revenus et
 * dépenses restent dans deux listes séparées (lot 3) ; un échec se dit en français.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { render, screen, within } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import EarningsScreen from '../../app/(app)/(tabs)/earnings';
import { clearToken, setToken } from '../api/session';
import { t } from '../i18n';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test', REQUEST_TIMEOUT_MS: 20_000 }));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();
const BASE = 'https://example.test/api/v10/';
const fcfa = (texte: string) => `${texte.replace(/ /g, ' ')} FCFA`;

function ok(data: unknown) {
  return { ok: true, status: 200, text: async () => JSON.stringify({ success: true, message: '', data }) };
}

const profil = {
  user: { id: 4, name: 'Koffi Agbo' },
  current_balance: '15000.00',
  deliveryman_earn: 42750,
  total_cod: '1234567.40',
};
const revenus = [
  { id: 1, date: '01-10-2026', note: 'Livraison BL-0012', amount: '1500.00', type: 1 },
  { id: 2, date: '02-10-2026', note: null, amount: 2500, type: 1 },
];
const depenses = [{ id: 3, date: '03-10-2026', note: 'Carburant', amount: 800, type: 2 }];
const remises = [{ id: 9, type: 1, amount: 12500, date: '04-10-2026', note: 'Remise agence Cotonou', created_at: null }];

function serveur(reponses: Record<string, unknown>) {
  fetchMock.mockImplementation(async (url) => {
    const chemin = url.replace(BASE, '');
    if (!(chemin in reponses)) throw new Error(`route inattendue : ${chemin}`);
    const r = reponses[chemin];
    return r instanceof Error ? Promise.reject(r) : (r as object);
  });
}

beforeAll(() => {
  globalThis.fetch = fetchMock as unknown as typeof fetch;
});

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  await setToken('jeton-livreur');
});

describe('onglet Gains', () => {
  it('appelle les trois routes du livreur avec son jeton', async () => {
    serveur({
      'deliveryman/profile': ok(profil),
      'deliveryman/income-expense': ok({ income: revenus, expense: depenses }),
      'deliveryman/parcel-payment-logs': ok({ parcel_payment_logs: remises }),
    });
    await render(<EarningsScreen />);
    await screen.findByText(fcfa('15 000'));

    const urls = fetchMock.mock.calls.map(([url]) => url).sort();
    expect(urls).toEqual([
      BASE + 'deliveryman/income-expense',
      BASE + 'deliveryman/parcel-payment-logs',
      BASE + 'deliveryman/profile',
    ]);
    for (const [, init] of fetchMock.mock.calls) {
      expect(init.method).toBe('GET');
      expect(init.headers.Authorization).toBe('Bearer jeton-livreur');
      expect(init.headers.apiKey).toBe('test');
    }
  });

  it('affiche solde, gains et COD du serveur en FCFA entiers', async () => {
    serveur({
      'deliveryman/profile': ok(profil),
      'deliveryman/income-expense': ok({ income: [], expense: [] }),
      'deliveryman/parcel-payment-logs': ok({ parcel_payment_logs: [] }),
    });
    await render(<EarningsScreen />);

    expect(await screen.findByText(fcfa('15 000'))).toBeTruthy();
    expect(screen.getByText(fcfa('42 750'))).toBeTruthy();
    // 1234567.40 arrondi à l'entier : le franc CFA n'a pas de centimes.
    expect(screen.getByText(fcfa('1 234 567'))).toBeTruthy();
    expect(screen.queryByText(/\.00|,40|\.40/)).toBeNull();
    expect(screen.getByText(t('earnings.balance'))).toBeTruthy();
    expect(screen.getByText(t('earnings.totalCod'))).toBeTruthy();
    // Trois listes vides, trois mentions.
    expect(screen.getAllByText(t('earnings.empty'))).toHaveLength(3);
  });

  it('liste encaissements remis, revenus et dépenses séparément', async () => {
    serveur({
      'deliveryman/profile': ok(profil),
      'deliveryman/income-expense': ok({ income: revenus, expense: depenses }),
      'deliveryman/parcel-payment-logs': ok({ parcel_payment_logs: remises }),
    });
    await render(<EarningsScreen />);

    expect(await screen.findByText('Remise agence Cotonou')).toBeTruthy();
    expect(screen.getByText(fcfa('12 500'))).toBeTruthy();
    expect(screen.getByText('Livraison BL-0012')).toBeTruthy();
    expect(screen.getByText(fcfa('1 500'))).toBeTruthy();
    expect(screen.getByText(fcfa('2 500'))).toBeTruthy();
    expect(screen.getByText('Carburant')).toBeTruthy();
    expect(screen.getByText(fcfa('800'))).toBeTruthy();
    expect(screen.queryByText(t('earnings.empty'))).toBeNull();

    // Les dépenses ne sont jamais fondues dans les revenus (ni l'inverse).
    const titreDepenses = screen.getByText(t('earnings.expense'));
    const carteDepenses = titreDepenses.parent!; // la carte « Dépenses »
    expect(within(carteDepenses).getByText('Carburant')).toBeTruthy();
    expect(within(carteDepenses).queryByText('Livraison BL-0012')).toBeNull();
  });

  it("dit l'échec du serveur en français, sans planter", async () => {
    serveur({
      'deliveryman/profile': { ok: false, status: 500, text: async () => '{"message":"Service indisponible."}' },
      'deliveryman/income-expense': ok({ income: [], expense: [] }),
      'deliveryman/parcel-payment-logs': ok({ parcel_payment_logs: [] }),
    });
    await render(<EarningsScreen />);

    expect(await screen.findByText('Service indisponible.')).toBeTruthy();
    // Rien de chargé : les tuiles restent à zéro plutôt qu'à « NaN ».
    expect(screen.getAllByText(fcfa('0'))).toHaveLength(3);
  });

  it('signale une coupure réseau', async () => {
    serveur({
      'deliveryman/profile': new TypeError('Network request failed'),
      'deliveryman/income-expense': new TypeError('Network request failed'),
      'deliveryman/parcel-payment-logs': new TypeError('Network request failed'),
    });
    await render(<EarningsScreen />);

    expect(await screen.findByText(t('errors.network'))).toBeTruthy();
  });
});
