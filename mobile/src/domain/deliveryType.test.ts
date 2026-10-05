/** S84 (M2) — la correspondance clé → identifiant des types de livraison. */
import { DELIVERY_TYPE_IDS, deliveryTypeId, deliveryTypeLabel } from './deliveryType';

describe('deliveryTypeId', () => {
  it('garde les identifiants de `DeliveryType.php`, faute de frappe du socle comprise', () => {
    expect(DELIVERY_TYPE_IDS).toEqual({ same_day: 1, next_day: 2, sub_city: 3, outside_City: 4 });
    expect(deliveryTypeId('outside_City')).toBe(4);
  });

  it('rend null sur une clé que le backend ne connaît pas', () => {
    expect(deliveryTypeId('outside_city')).toBeNull();
    expect(deliveryTypeId('')).toBeNull();
  });
});

describe('deliveryTypeLabel', () => {
  it('traduit les quatre clés et rend la clé telle quelle sinon', () => {
    expect(deliveryTypeLabel('same_day')).toBe('Le jour même');
    expect(deliveryTypeLabel('inconnue')).toBe('inconnue');
  });
});
