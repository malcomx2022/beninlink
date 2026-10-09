/**
 * Outil de test — un `fetch` simulé pour exercer le VRAI client d'API.
 *
 * Les tests d'intégration rendent un écran avec ses vrais modules d'API
 * (`src/api/*.ts` → `client.ts`) : seul `fetch` est remplacé. Ce fichier n'est
 * importé que par des fichiers `*.test.ts(x)` ; il n'entre pas dans le bundle.
 */

/** Ce que le client a réellement envoyé. */
export type FetchCall = {
  url: string;
  /** Chemin relatif à la base d'API, requête comprise (ex. `wallet/history?page=2`). */
  path: string;
  method: string;
  headers: Record<string, string>;
  body: unknown;
};

/** Base d'API des tests ; à reprendre dans le `jest.mock('…/api/config')` de chaque fichier. */
export const TEST_API_BASE_URL = 'https://example.test/api/v10';
export const TEST_API_KEY = 'cle-api-test';

/** Réponse minimale lue par `client.ts` : `ok`, `status`, `text()`. */
export function jsonResponse(status: number, body?: unknown): Response {
  const raw = body === undefined ? '' : typeof body === 'string' ? body : JSON.stringify(body);
  return { ok: status >= 200 && status < 300, status, text: async () => raw } as unknown as Response;
}

/** Enveloppe de succès du backend (`ApiReturnFormatTrait::responseWithSuccess`). */
export function ok(data: unknown, extra: Record<string, unknown> = {}): Response {
  return jsonResponse(200, { success: true, message: '', data, ...extra });
}

/** Installe un `fetch` simulé neuf (aucune réponse en file) et le rend. */
export function installFetch(): jest.Mock {
  const mock = jest.fn();
  globalThis.fetch = mock as unknown as typeof fetch;
  return mock;
}

/** Les appels reçus par le `fetch` simulé, décodés. */
export function fetchCalls(mock: jest.Mock): FetchCall[] {
  return mock.mock.calls.map(([url, init]: [string, RequestInit | undefined]) => {
    const body = init?.body;
    return {
      url: String(url),
      path: String(url).replace(`${TEST_API_BASE_URL}/`, ''),
      method: init?.method ?? 'GET',
      headers: { ...(init?.headers as Record<string, string> | undefined) },
      body: typeof body === 'string' ? JSON.parse(body) : undefined,
    };
  });
}

/** Le seul appel reçu ; échoue s'il y en a eu zéro ou plusieurs. */
export function singleCall(mock: jest.Mock): FetchCall {
  const calls = fetchCalls(mock);
  if (calls.length !== 1) throw new Error(`Un appel attendu, ${calls.length} reçu(s).`);
  return calls[0]!;
}
