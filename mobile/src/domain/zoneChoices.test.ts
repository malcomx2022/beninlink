import type { DeliveryZone } from '../api/types';
import { zoneChoices } from './zoneChoices';

const zone = (id: number, name: string): DeliveryZone =>
  ({ id, code: name.toLowerCase(), name, export: false, countries: [], rates: [] }) as unknown as DeliveryZone;

describe('zoneChoices', () => {
  it('propose une entrée par zone, dans l’ordre du serveur', () => {
    expect(zoneChoices([zone(3, 'Cotonou'), zone(5, 'Intérieur')])).toEqual([
      { value: 3, label: 'Cotonou' },
      { value: 5, label: 'Intérieur' },
    ]);
  });

  it('n’offre plus de « barème hérité » (valeur 0) : la zone est obligatoire', () => {
    expect(zoneChoices([zone(3, 'Cotonou')]).some((c) => c.value === 0)).toBe(false);
    expect(zoneChoices([])).toEqual([]);
  });
});
