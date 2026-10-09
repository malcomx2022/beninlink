/**
 * Partage de position du livreur : autorisation « pendant l'utilisation » seulement, une position
 * ponctuelle en précision équilibrée, envoyée par `updateLocation()` ; un refus ou une position
 * indisponible se disent sans rien envoyer — jamais une exception pour l'écran.
 */
import * as Location from 'expo-location';

import { updateLocation } from '../api/deliveryman';
import { shareCurrentPosition } from './location';

jest.mock('expo-location', () => ({
  requestForegroundPermissionsAsync: jest.fn(),
  requestBackgroundPermissionsAsync: jest.fn(),
  getCurrentPositionAsync: jest.fn(),
  Accuracy: { Balanced: 3 },
}));
jest.mock('../api/deliveryman', () => ({ updateLocation: jest.fn() }));

const permission = jest.mocked(Location.requestForegroundPermissionsAsync);
const position = jest.mocked(Location.getCurrentPositionAsync);

beforeEach(() => {
  jest.resetAllMocks();
  jest.mocked(updateLocation).mockResolvedValue(undefined);
});

describe('shareCurrentPosition', () => {
  it('envoie latitude et longitude et rend « sent »', async () => {
    permission.mockResolvedValue({ status: 'granted' } as never);
    position.mockResolvedValue({ coords: { latitude: 6.3654, longitude: 2.4183 } } as never);

    await expect(shareCurrentPosition()).resolves.toBe('sent');
    expect(position).toHaveBeenCalledWith({ accuracy: Location.Accuracy.Balanced });
    expect(updateLocation).toHaveBeenCalledWith(6.3654, 2.4183);
    // Jamais de suivi en arrière-plan.
    expect(Location.requestBackgroundPermissionsAsync).not.toHaveBeenCalled();
  });

  it("rend « denied » sans lire ni envoyer de position", async () => {
    permission.mockResolvedValue({ status: 'denied' } as never);

    await expect(shareCurrentPosition()).resolves.toBe('denied');
    expect(position).not.toHaveBeenCalled();
    expect(updateLocation).not.toHaveBeenCalled();
  });

  it("rend « unavailable » quand le GPS échoue, sans rien envoyer", async () => {
    permission.mockResolvedValue({ status: 'granted' } as never);
    position.mockRejectedValue(new Error('Location services disabled'));

    await expect(shareCurrentPosition()).resolves.toBe('unavailable');
    expect(updateLocation).not.toHaveBeenCalled();
  });

  it("laisse remonter l'échec de l'envoi (l'appelant décide)", async () => {
    permission.mockResolvedValue({ status: 'granted' } as never);
    position.mockResolvedValue({ coords: { latitude: 1, longitude: 2 } } as never);
    jest.mocked(updateLocation).mockRejectedValue(new Error('réseau'));

    await expect(shareCurrentPosition()).rejects.toThrow('réseau');
  });
});
