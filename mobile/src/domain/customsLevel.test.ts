/**
 * S84 (M2) — la table des niveaux douaniers, exécutée et non plus lue.
 *
 * `MerchantAppCustomsContractTest` (côté `web/`) vérifie que les VALEURS sont
 * celles des enums du backend : c'est le contrat, et il reste là-bas. Ici on
 * exécute la fonction : ce qu'une lecture de source ne peut pas dire.
 */
import { CustomsAlertStatus, CustomsLevel, customsLevelColorName, highestCustomsLevel } from './customsLevel';

describe('customsLevelColorName', () => {
  it('peint chaque niveau avec la couleur de charte qui lui est réservée', () => {
    expect(customsLevelColorName(CustomsLevel.INFO)).toBe('info');
    expect(customsLevelColorName(CustomsLevel.WARNING)).toBe('warning');
    expect(customsLevelColorName(CustomsLevel.BLOCKING)).toBe('danger');
  });

  it("n'atténue jamais un niveau inconnu plus grave que BLOQUANT", () => {
    expect(customsLevelColorName(4)).toBe('danger');
    expect(customsLevelColorName(99)).toBe('danger');
  });

  it('rabat un niveau nul ou négatif sur INFO, jamais sur une alerte', () => {
    expect(customsLevelColorName(0)).toBe('info');
    expect(customsLevelColorName(-1)).toBe('info');
  });
});

describe('highestCustomsLevel', () => {
  it('rend 0 sur un lot vide', () => {
    expect(highestCustomsLevel([])).toBe(0);
  });

  it('retient le niveau le plus grave, quel que soit l\'ordre', () => {
    expect(highestCustomsLevel([{ level: 1 }, { level: 3 }, { level: 2 }])).toBe(3);
    expect(highestCustomsLevel([{ level: 2 }, { level: 1 }])).toBe(2);
  });
});

describe('les constantes du contrat', () => {
  it('gardent les valeurs des enums du backend', () => {
    expect(CustomsLevel).toEqual({ INFO: 1, WARNING: 2, BLOCKING: 3 });
    expect(CustomsAlertStatus).toEqual({ PENDING: 1, RESOLVED: 2 });
  });
});
