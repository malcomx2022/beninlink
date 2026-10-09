/**
 * Issue d'une course, de bout en bout : écran `parcel/[id]/status` → `reportOutcome()` / `reportDelivered()`
 * → vrai client d'API → `fetch` simulé. Garde les trois issues permises (livré, partiel, retour) et
 * seulement elles, le montant attendu en FCFA entiers, le montant encaissé d'un partiel validé avant envoi
 * (vide refusé, zéro permis — lot 3), le `status_action` du backend, et les preuves (photo, signature)
 * jointes en multipart. Le canevas de signature est simulé : il dessine en SVG natif.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`, `await fireEvent`).
 */
import { act, fireEvent, render, screen, userEvent, waitFor } from '@testing-library/react-native';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import * as SecureStore from 'expo-secure-store';

import ParcelStatusScreen from '../../app/(app)/parcel/[id]/status';
import { clearToken, setToken } from '../api/session';
import { t } from '../i18n';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test', REQUEST_TIMEOUT_MS: 20_000 }));
const mockDismissTo = jest.fn();
jest.mock('expo-router', () => ({
  useLocalSearchParams: () => ({ id: '12' }),
  useRouter: () => ({ dismissTo: mockDismissTo }),
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));
jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(),
  launchCameraAsync: jest.fn(),
}));
jest.mock('expo-location', () => ({
  requestForegroundPermissionsAsync: jest.fn(),
  getCurrentPositionAsync: jest.fn(),
  Accuracy: { Balanced: 3 },
}));
const mockCapture = jest.fn<Promise<string | null>, []>();
jest.mock('../components/SignaturePad', () => {
  const { forwardRef, useImperativeHandle } = jest.requireActual<typeof import('react')>('react');
  const { Pressable, Text } = jest.requireActual<typeof import('react-native')>('react-native');
  const SignaturePad = forwardRef(function SignaturePad(
    { onChange }: { onChange?: (signe: boolean) => void },
    ref: React.Ref<{ capture: () => Promise<string | null>; clear: () => void }>,
  ) {
    useImperativeHandle(ref, () => ({
      capture: mockCapture,
      clear: () => onChange?.(false),
    }));
    return (
      <Pressable onPress={() => onChange?.(true)}>
        <Text>signer (test)</Text>
      </Pressable>
    );
  });
  return { SignaturePad };
});

type RNForm = FormData & { getAll: (cle: string) => unknown[] };
const RNFormData = jest.requireActual('react-native/Libraries/Network/FormData').default as typeof FormData;
const NodeFormData = globalThis.FormData;

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();

/** Le n-ième appel à `fetch` ; échoue lisiblement s'il n'a pas eu lieu. */
function appel(i: number, appels: Appel[] = fetchMock.mock.calls): Appel {
  const trouve = appels[i];
  if (!trouve) throw new Error(`pas d'appel n° ${i} à fetch`);
  return trouve;
}
const BASE = 'https://example.test/api/v10/';

function ok(data: unknown) {
  return { ok: true, status: 200, text: async () => JSON.stringify({ success: true, message: '', data }) };
}

const course = {
  id: 12,
  tracking_id: 'BL-0012',
  customer_name: 'Awa Zinsou',
  // Chaîne décimale tolérée par toAmount() : l'écran doit afficher un entier.
  cash_collection: '12500.00',
};

/** Les appels d'écriture (tout sauf le détail de la course). */
function envois() {
  return fetchMock.mock.calls.filter(([url]) => !url.includes('parcel/details'));
}

async function afficher() {
  await render(<ParcelStatusScreen />);
  await screen.findByText('BL-0012');
}

async function choisir(libelle: string) {
  await fireEvent.press(screen.getByText(libelle));
}

async function enregistrer() {
  await fireEvent.press(screen.getByText(t('status.confirm')));
}

beforeAll(() => {
  globalThis.fetch = fetchMock as unknown as typeof fetch;
  globalThis.FormData = RNFormData;
});

afterAll(() => {
  globalThis.FormData = NodeFormData;
});

beforeEach(async () => {
  jest.resetAllMocks();
  jest.useFakeTimers();
  mockCapture.mockResolvedValue('file:///signature.png');
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  await setToken('jeton-livreur');
  // Position refusée par défaut : aucun envoi silencieux, sauf scénario qui l'autorise.
  jest.mocked(Location.requestForegroundPermissionsAsync).mockResolvedValue({ status: 'denied' } as never);
  fetchMock.mockImplementation(async (url) =>
    url === BASE + 'deliveryman/parcel/details/12' ? ok({ parcel: course, parcelEvents: [] }) : ok([]),
  );
});

afterEach(() => {
  jest.clearAllTimers();
  jest.useRealTimers();
});

describe('issue de la course', () => {
  it('efface la signature lors du changement d’issue, même après un passage par le partiel', async () => {
    await afficher();
    await choisir(t('status.delivered'));
    await fireEvent.press(screen.getByText('signer (test)'));
    await choisir(t('status.partial'));
    await choisir(t('status.returned'));

    expect(screen.queryByText(t('common.clearSignature'))).toBeNull();
    await fireEvent.press(screen.getByText('signer (test)'));
    await enregistrer();
    expect((appel(0, envois())[1].body as RNForm).getAll('signatureImage')).toHaveLength(1);
  });

  it('ne réutilise pas une signature de livraison pour un retour', async () => {
    await afficher();
    await choisir(t('status.delivered'));
    await fireEvent.press(screen.getByText('signer (test)'));
    await choisir(t('status.returned'));
    expect(screen.queryByText(t('common.clearSignature'))).toBeNull();
    await enregistrer();
    expect(mockCapture).not.toHaveBeenCalled();
    expect(JSON.parse(appel(0, envois())[1].body as string)).toEqual({ parcel_id: 12, status_action: 24 });
  });

  it.each(['delivered', 'returned'] as const)('ne perd pas une signature dont la capture est vide (%s), et permet de réessayer', async (issue) => {
    mockCapture.mockResolvedValueOnce(null);
    await afficher();
    await choisir(t(`status.${issue}`));
    await fireEvent.press(screen.getByText('signer (test)'));
    await enregistrer();

    expect(envois()).toEqual([]);
    expect(screen.getByText(t('status.signatureCaptureFailed'))).toBeTruthy();
    await enregistrer();
    expect(await screen.findByText(t(issue === 'delivered' ? 'status.successDelivered' : 'status.successReturned'))).toBeTruthy();
    expect((appel(0, envois())[1].body as RNForm).getAll('signatureImage')).toHaveLength(1);
  });

  it('n’envoie rien si la capture de signature échoue', async () => {
    mockCapture.mockRejectedValueOnce(new Error('capture failed'));
    await afficher();
    await choisir(t('status.returned'));
    await fireEvent.press(screen.getByText('signer (test)'));
    await enregistrer();
    expect(envois()).toEqual([]);
    expect(screen.getByText(t('errors.unexpected'))).toBeTruthy();
    expect(mockDismissTo).not.toHaveBeenCalled();
    await fireEvent.press(screen.getByText(t('common.clearSignature')));
    await enregistrer();
    expect(await screen.findByText(t('status.successReturned'))).toBeTruthy();
    expect(mockCapture).toHaveBeenCalledTimes(1);
  });

  it('fige l’issue et les preuves pendant une capture lente', async () => {
    let resolve!: (uri: string) => void;
    mockCapture.mockImplementationOnce(() => new Promise((r) => { resolve = r; }));
    await afficher();
    await choisir(t('status.delivered'));
    await fireEvent.press(screen.getByText('signer (test)'));
    const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
    await user.press(screen.getByText(t('status.confirm')));
    await waitFor(() => expect(mockCapture).toHaveBeenCalledTimes(1));

    await choisir(t('status.partial'));
    expect(screen.queryByText(t('status.collected'))).toBeNull();
    expect(screen.getByText(t('common.clearSignature'))).toBeDisabled();
    expect(screen.getByText(t('common.photo'))).toBeDisabled();
    expect(screen.getByPlaceholderText(t('status.notePlaceholder'))).toHaveProp('editable', false);
    await act(async () => resolve('file:///signature.png'));
    expect(await screen.findByText(t('status.successDelivered'))).toBeTruthy();
    expect(envois()).toHaveLength(1);
  });

  it('charge la course, affiche le montant attendu en FCFA entiers et les trois issues permises', async () => {
    await afficher();

    expect(appel(0)[0]).toBe(BASE + 'deliveryman/parcel/details/12');
    expect(appel(0)[1].headers.Authorization).toBe('Bearer jeton-livreur');
    expect(screen.getByText('Awa Zinsou')).toBeTruthy();
    // Espaces insécables : « 12 500 FCFA », jamais « 12500.00 ».
    expect(screen.getByText('12\u00a0500\u00a0FCFA')).toBeTruthy();
    expect(screen.queryByText(/12500\.00|,00/)).toBeNull();

    for (const issue of [t('status.delivered'), t('status.partial'), t('status.returned')]) {
      expect(screen.getByText(issue)).toBeTruthy();
    }
    // Aucune autre étape (ramassage, entrepôt…) n'est proposée au livreur.
    expect(screen.queryByText(t('parcelStage.warehouse'))).toBeNull();
    expect(screen.queryByText(t('parcelStage.pickup_assigned'))).toBeNull();
  });

  it("exige une issue avant d'envoyer", async () => {
    await afficher();
    await enregistrer();

    expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
    expect(envois()).toEqual([]);
  });

  it.each(['', '12,5', '-5', 'abc'])('refuse le montant encaissé « %s » sans appeler l’API', async (saisie) => {
    await afficher();
    await choisir(t('status.partial'));
    await fireEvent.changeText(screen.getByPlaceholderText('12500'), saisie);
    await enregistrer();

    expect(screen.getByText(t('errors.invalidAmount'))).toBeTruthy();
    expect(envois()).toEqual([]);
  });

  it('déclare un partiel avec le seul montant encaissé, puis revient à la liste', async () => {
    await afficher();
    await choisir(t('status.partial'));
    await fireEvent.changeText(screen.getByPlaceholderText('12500'), '7 500');
    await enregistrer();

    expect(await screen.findByText(t('status.successPartial'))).toBeTruthy();
    expect(envois()).toHaveLength(1);
    const [url, init] = appel(0, envois());
    expect(url).toBe(BASE + 'deliveryman/parcel-status-update');
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({ parcel_id: 12, status_action: 32, cash_collection: 7500 });

    expect(mockDismissTo).not.toHaveBeenCalled();
    await act(async () => {
      jest.advanceTimersByTime(600);
    });
    expect(mockDismissTo).toHaveBeenCalledWith('/(app)/(tabs)');
  });

  it('accepte un encaissement nul explicite (lot 3)', async () => {
    await afficher();
    await choisir(t('status.partial'));
    await fireEvent.changeText(screen.getByPlaceholderText('12500'), '0');
    await enregistrer();

    expect(await screen.findByText(t('status.successPartial'))).toBeTruthy();
    expect(JSON.parse(appel(0, envois())[1].body as string)).toMatchObject({ status_action: 32, cash_collection: 0 });
  });

  it('déclare un retour avec sa remarque, sans montant', async () => {
    await afficher();
    await choisir(t('status.returned'));
    await fireEvent.changeText(screen.getByPlaceholderText(t('status.notePlaceholder')), '  Destinataire absent ');
    await enregistrer();

    expect(await screen.findByText(t('status.successReturned'))).toBeTruthy();
    const [url, init] = appel(0, envois());
    expect(url).toBe(BASE + 'deliveryman/parcel-status-update');
    expect(JSON.parse(init.body as string)).toEqual({ parcel_id: 12, status_action: 24, note: 'Destinataire absent' });
  });

  it('joint la signature à un retour, en multipart (S106)', async () => {
    await afficher();
    await choisir(t('status.returned'));
    expect(screen.getByText(t('status.signatureReturnHint'))).toBeTruthy();
    await fireEvent.press(screen.getByText('signer (test)'));
    await enregistrer();

    expect(await screen.findByText(t('status.successReturned'))).toBeTruthy();
    const form = appel(0, envois())[1].body as RNForm;
    expect(form.getAll('status_action')).toEqual(['24']);
    expect(form.getAll('signatureImage')).toEqual([
      { uri: 'file:///signature.png', name: 'signature-retour-12.png', type: 'image/png' },
    ]);
  });

  it('déclare une livraison avec photo et signature, puis partage la position', async () => {
    jest.mocked(ImagePicker.requestCameraPermissionsAsync).mockResolvedValue({ status: 'granted' } as never);
    jest.mocked(ImagePicker.launchCameraAsync).mockResolvedValue({
      canceled: false,
      assets: [{ uri: 'file:///colis.jpg' }],
    } as never);
    jest.mocked(Location.requestForegroundPermissionsAsync).mockResolvedValue({ status: 'granted' } as never);
    jest.mocked(Location.getCurrentPositionAsync).mockResolvedValue({ coords: { latitude: 6.36, longitude: 2.42 } } as never);
    await afficher();

    await choisir(t('status.delivered'));
    await fireEvent.press(screen.getByText(t('common.photo')));
    expect(await screen.findByText(t('common.retakePhoto'))).toBeTruthy();
    await fireEvent.press(screen.getByText('signer (test)'));
    expect(screen.getByText(t('common.clearSignature'))).toBeTruthy();
    await enregistrer();

    expect(await screen.findByText(t('status.successDelivered'))).toBeTruthy();
    const [url, init] = appel(0, envois());
    expect(url).toBe(BASE + 'deliveryman/parcel/delivered/12');
    expect(init.headers['Content-Type']).toBeUndefined();
    const form = init.body as RNForm;
    expect(form.getAll('image')).toEqual([{ uri: 'file:///colis.jpg', name: 'livraison-12.jpg', type: 'image/jpeg' }]);
    expect(form.getAll('signatureImage')).toEqual([{ uri: 'file:///signature.png', name: 'signature-12.png', type: 'image/png' }]);
    // Aucun montant n'est transmis pour une livraison : le backend recalcule.
    expect(form.getAll('cash_collection')).toEqual([]);

    await waitFor(() => expect(envois()).toHaveLength(2));
    expect(appel(1, envois())[0]).toBe(BASE + 'deliveryman/parcel-location-update');
    expect(JSON.parse(appel(1, envois())[1].body as string)).toEqual({ lat: 6.36, long: 2.42 });
  });

  it("explique le refus de l'appareil photo sans bloquer la livraison", async () => {
    jest.mocked(ImagePicker.requestCameraPermissionsAsync).mockResolvedValue({ status: 'denied' } as never);
    await afficher();

    await choisir(t('status.delivered'));
    await fireEvent.press(screen.getByText(t('common.photo')));
    expect(await screen.findByText(t('errors.cameraDenied'))).toBeTruthy();

    await enregistrer();
    expect(await screen.findByText(t('status.successDelivered'))).toBeTruthy();
    expect((appel(0, envois())[1].body as RNForm).getAll('image')).toEqual([]);
  });

  it('affiche le refus du serveur et laisse réessayer', async () => {
    fetchMock.mockImplementation(async (url) =>
      url.endsWith('details/12')
        ? ok({ parcel: course, parcelEvents: [] })
        : {
            ok: false,
            status: 422,
            text: async () => JSON.stringify({ success: false, message: 'Cette course est déjà clôturée.', data: [] }),
          },
    );
    await afficher();
    await choisir(t('status.returned'));
    await enregistrer();

    expect(await screen.findByText('Cette course est déjà clôturée.')).toBeTruthy();
    expect(screen.queryByText(t('status.successReturned'))).toBeNull();
    await act(async () => {
      jest.advanceTimersByTime(1000);
    });
    expect(mockDismissTo).not.toHaveBeenCalled();
  });
});
