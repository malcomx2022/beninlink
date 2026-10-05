/**
 * S84 (M2) — 33 statuts du backend → 7 étapes marchand, exécutés.
 */
import {
  BackendParcelStatus,
  TAB_STAGES,
  TIMELINE_ORDER,
  isIncident,
  toMerchantStage,
  type MerchantStage,
} from './parcelStatus';

const B = BackendParcelStatus;
const STAGES: readonly MerchantStage[] = ['pending', 'pickup_assigned', 'warehouse', 'courier_assigned', 'delivered', 'partial', 'returned'];

describe('toMerchantStage', () => {
  it('rabat chacun des 33 statuts du backend sur une étape marchand', () => {
    const codes = Object.values(B);
    expect(codes).toHaveLength(33);
    for (const code of codes) {
      expect(STAGES).toContain(toMerchantStage(code));
    }
  });

  it('rattache une annulation à l\'étape AMONT, jamais à une étape « annulé »', () => {
    expect(toMerchantStage(B.PICKUP_ASSIGN_CANCEL)).toBe('pending');
    expect(toMerchantStage(B.RECEIVED_WAREHOUSE_CANCEL)).toBe('warehouse');
    expect(toMerchantStage(B.DELIVERED_CANCEL)).toBe('courier_assigned');
    expect(toMerchantStage(B.PARTIAL_DELIVERED_CANCEL)).toBe('courier_assigned');
  });

  it('lit un code reçu en chaîne, et rabat un code inconnu sur « en attente »', () => {
    expect(toMerchantStage('9')).toBe('delivered');
    expect(toMerchantStage(999)).toBe('pending');
    expect(toMerchantStage(undefined)).toBe('pending');
  });

  it('distingue livré, livraison partielle et retour', () => {
    expect(toMerchantStage(B.DELIVERED)).toBe('delivered');
    expect(toMerchantStage(B.PARTIAL_DELIVERED)).toBe('partial');
    expect(toMerchantStage(B.RETURNED_MERCHANT)).toBe('returned');
  });
});

describe('la timeline et les onglets', () => {
  it('la timeline suit le parcours nominal, sans les incidents', () => {
    expect(TIMELINE_ORDER).toEqual(['pending', 'pickup_assigned', 'warehouse', 'courier_assigned', 'delivered']);
    expect(TIMELINE_ORDER.some(isIncident)).toBe(false);
  });

  it('seuls la livraison partielle et le retour sont des incidents', () => {
    expect(STAGES.filter(isIncident)).toEqual(['partial', 'returned']);
  });

  it('les trois onglets couvrent les 7 étapes, chacune une seule fois', () => {
    const toutes = [...TAB_STAGES.ongoing, ...TAB_STAGES.delivered, ...TAB_STAGES.returns];
    expect([...toutes].sort()).toEqual([...STAGES].sort());
  });
});
