/**
 * Preuve de livraison par photo : l'appareil photo n'est ouvert qu'avec l'autorisation, en qualité 0,5,
 * sans recadrage ni EXIF ; une annulation rend `null` (la livraison reste déclarable sans photo) et un
 * refus lève `CameraDeniedError`, que l'écran traduit en message.
 */
import * as ImagePicker from 'expo-image-picker';

import { CameraDeniedError, takeDeliveryPhoto } from './photo';

jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(),
  launchCameraAsync: jest.fn(),
}));

const permission = jest.mocked(ImagePicker.requestCameraPermissionsAsync);
const camera = jest.mocked(ImagePicker.launchCameraAsync);

beforeEach(() => {
  jest.resetAllMocks();
});

describe('takeDeliveryPhoto', () => {
  it("rend l'URI de la photo prise, réduite pour le réseau mobile", async () => {
    permission.mockResolvedValue({ status: 'granted' } as never);
    camera.mockResolvedValue({ canceled: false, assets: [{ uri: 'file:///colis.jpg' }] } as never);

    await expect(takeDeliveryPhoto()).resolves.toBe('file:///colis.jpg');
    expect(camera).toHaveBeenCalledWith({ mediaTypes: ['images'], quality: 0.5, allowsEditing: false, exif: false });
  });

  it("rend null quand le livreur annule", async () => {
    permission.mockResolvedValue({ status: 'granted' } as never);
    camera.mockResolvedValue({ canceled: true, assets: null } as never);

    await expect(takeDeliveryPhoto()).resolves.toBeNull();
  });

  it('rend null si la caméra ne renvoie aucune image', async () => {
    permission.mockResolvedValue({ status: 'granted' } as never);
    camera.mockResolvedValue({ canceled: false, assets: [] } as never);

    await expect(takeDeliveryPhoto()).resolves.toBeNull();
  });

  it.each(['denied', 'undetermined'])("lève CameraDeniedError sans ouvrir l'appareil (%s)", async (status) => {
    permission.mockResolvedValue({ status } as never);

    await expect(takeDeliveryPhoto()).rejects.toBeInstanceOf(CameraDeniedError);
    expect(camera).not.toHaveBeenCalled();
  });
});
