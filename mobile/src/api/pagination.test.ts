/**
 * S84 (M2) — le parcours d'une liste paginée (S78), exécuté.
 *
 * `MerchantAppCustomsContractTest` tient le contrat (les constantes `*_PER_PAGE`
 * contre les `paginate()` du serveur). Ici, la logique : `page` présent gagne,
 * sinon « une page incomplète est la dernière ».
 */
import { fetchAllPages, hasNextPage, toPaged } from './pagination';

const page = (current: number, last: number) => ({ current, per_page: 20, last, total: last * 20 });

describe('hasNextPage', () => {
  it('suit le bloc `page` quand le serveur le donne, quelle que soit la taille reçue', () => {
    expect(hasNextPage(page(1, 3), 20, 20)).toBe(true);
    expect(hasNextPage(page(3, 3), 20, 20)).toBe(false);
    expect(hasNextPage(page(1, 1), 20, 20)).toBe(false);
    // Une page pleine mais DERNIÈRE : sans `page`, l'app redemanderait une page vide.
    expect(hasNextPage(page(2, 2), 20, 20)).toBe(false);
  });

  it('retombe sur « une page pleine, donc il en reste » sur un serveur d\'avant S78', () => {
    expect(hasNextPage(null, 20, 20)).toBe(true);
    expect(hasNextPage(null, 19, 20)).toBe(false);
    expect(hasNextPage(null, 0, 20)).toBe(false);
  });
});

describe('toPaged', () => {
  it('rend les éléments et le drapeau', () => {
    expect(toPaged(['a', 'b'], page(1, 2), 2)).toEqual({ items: ['a', 'b'], hasMore: true });
    expect(toPaged(['a'], null, 2)).toEqual({ items: ['a'], hasMore: false });
  });
});

describe('fetchAllPages', () => {
  it('lit toutes les pages jusqu\'à la dernière, dans l\'ordre', async () => {
    const pages = [
      { items: [1, 2], hasMore: true },
      { items: [3, 4], hasMore: true },
      { items: [5], hasMore: false },
    ];
    const demandees: number[] = [];
    const tout = await fetchAllPages(async (n) => {
      demandees.push(n);
      return pages[n - 1] ?? { items: [], hasMore: false };
    });
    expect(tout).toEqual([1, 2, 3, 4, 5]);
    expect(demandees).toEqual([1, 2, 3]);
  });

  it('s\'arrête sur une page vide même si le serveur dit qu\'il en reste', async () => {
    let appels = 0;
    const tout = await fetchAllPages(async () => {
      appels++;
      return { items: [], hasMore: true };
    });
    expect(tout).toEqual([]);
    expect(appels).toBe(1);
  });

  it('plafonne le nombre de pages sur un serveur qui dirait toujours « il en reste »', async () => {
    let appels = 0;
    const tout = await fetchAllPages(async (n) => {
      appels++;
      return { items: [n], hasMore: true };
    }, 5);
    expect(tout).toEqual([1, 2, 3, 4, 5]);
    expect(appels).toBe(5);
  });
});
