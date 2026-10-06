import { CustomsLevel, customsLevelColorName } from './customsLevel';

describe('customsLevelColorName', () => {
  it('suit la charte : bloquant rouge, avertissement orange, info bleu', () => {
    expect(customsLevelColorName(CustomsLevel.BLOCKING)).toBe('danger');
    expect(customsLevelColorName(CustomsLevel.WARNING)).toBe('warning');
    expect(customsLevelColorName(CustomsLevel.INFO)).toBe('info');
  });

  it("un niveau inconnu n'est jamais atténué", () => {
    expect(customsLevelColorName(9)).toBe('danger');
  });
});
