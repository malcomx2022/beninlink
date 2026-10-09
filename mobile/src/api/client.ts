/**
 * Client HTTP unique de l'app. Aucun écran n'appelle `fetch` directement.
 *
 * Deux couches d'authentification, comme l'exige le backend :
 *   1. en-tête `apiKey` sur toutes les routes (CheckApiKeyMiddleware) ;
 *   2. `Authorization: Bearer <jeton>` sur les routes protégées (auth:sanctum).
 *
 * Tolérance de forme assumée : les réponses passent par `ApiReturnFormatTrait`,
 * mais la structure de `data` **change selon l'endpoint** et les Resource ne
 * couvrent qu'une partie de l'API (constat de la revue fonctionnelle). On ne
 * suppose donc rien au-delà de `success` / `message` / `data`.
 */
import { API_BASE_URL, API_KEY, REQUEST_TIMEOUT_MS } from './config';
import { clearToken, getToken } from './session';

export type ApiEnvelope<T> = {
  success?: boolean;
  message?: string;
  data?: T;
  /** Présent sur les réponses paginées (S78) : où finit la liste. */
  page?: ApiPage;
};

/**
 * Bloc `page` d'une réponse paginée (`ApiReturnFormatTrait::responseWithPage`,
 * S78). Il vit à la **racine** de l'enveloppe, à côté de `data`, parce que
 * `data` est tantôt un objet, tantôt un tableau. `current < last` : il en reste.
 */
export type ApiPage = {
  current: number;
  per_page: number;
  last: number;
  total: number;
};

/** Charge utile et bloc `page` d'une réponse paginée ; `page` vaut null sur un serveur d'avant S78. */
export type PagedResponse<T> = {
  data: T;
  page: ApiPage | null;
};

/** Erreur d'API porteuse du statut HTTP et des erreurs de validation. */
export class ApiError extends Error {
  readonly status: number;
  /** Erreurs par champ renvoyées par Laravel en 422. */
  readonly errors: Record<string, string[]>;
  /**
   * `data` de l'enveloppe d'erreur, tel quel.
   *
   * Certains refus portent autre chose qu'un message : le refus pour solde
   * insuffisant à la création d'un colis renvoie ce qui manque, en entiers XOF,
   * pour que l'écran propose la bonne recharge. On ne l'interprète pas ici —
   * chaque appelant lit ce qu'il attend, comme partout ailleurs dans ce client.
   */
  readonly data: unknown;

  constructor(
    message: string,
    status: number,
    errors: Record<string, string[]> = {},
    data: unknown = null,
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
    this.data = data;
  }

  /** Session expirée ou jeton révoqué : l'app doit renvoyer vers la connexion. */
  get isUnauthenticated(): boolean {
    return this.status === 401;
  }

  get isValidation(): boolean {
    return this.status === 422;
  }
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
  /** Routes publiques (signin, register, password/*) : pas de Bearer. */
  authenticated?: boolean;
  query?: Record<string, string | number | boolean | undefined>;
  signal?: AbortSignal;
};

function buildUrl(path: string, query?: RequestOptions['query']): string {
  const url = `${API_BASE_URL}/${path.replace(/^\/+/, '')}`;
  if (!query) return url;
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined) params.append(key, String(value));
  }
  const qs = params.toString();
  return qs ? `${url}?${qs}` : url;
}

/** Extrait un message lisible, la forme des erreurs variant d'un endpoint à l'autre. */
function extractMessage(payload: unknown, fallback: string): string {
  if (typeof payload === 'string' && payload.trim() !== '') return payload;
  if (payload && typeof payload === 'object') {
    const p = payload as Record<string, unknown>;
    if (typeof p.message === 'string' && p.message.trim() !== '') return p.message;
    if (p.data && typeof p.data === 'object') {
      const inner = (p.data as Record<string, unknown>).message;
      if (typeof inner === 'string' && inner.trim() !== '') return inner;
    }
  }
  return fallback;
}

function extractValidationErrors(payload: unknown): Record<string, string[]> {
  if (!payload || typeof payload !== 'object') return {};
  const p = payload as Record<string, unknown>;
  // Laravel place tantôt les erreurs sous `errors`, tantôt sous `data.message`.
  const candidates = [p.errors, (p.data as Record<string, unknown> | undefined)?.message];
  for (const candidate of candidates) {
    if (candidate && typeof candidate === 'object' && !Array.isArray(candidate)) {
      const out: Record<string, string[]> = {};
      for (const [field, value] of Object.entries(candidate as Record<string, unknown>)) {
        if (Array.isArray(value)) out[field] = value.map(String);
        else if (typeof value === 'string') out[field] = [value];
      }
      if (Object.keys(out).length > 0) return out;
    }
  }
  return {};
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const payload = await perform(path, options);

  // Certains endpoints renvoient l'objet nu, d'autres l'enveloppent dans `data`.
  if (payload && typeof payload === 'object' && 'data' in (payload as object)) {
    return (payload as ApiEnvelope<T>).data as T;
  }
  return payload as T;
}

/**
 * Comme `request`, mais garde le bloc `page` de l'enveloppe (S78).
 *
 * `page` est lu seulement s'il a la forme attendue : un serveur d'avant S78 ne le
 * renvoie pas, et l'appelant retombe alors sur « une page incomplète est la
 * dernière » (`src/api/pagination.ts`).
 */
export async function requestPaged<T>(
  path: string,
  options: RequestOptions = {},
): Promise<PagedResponse<T>> {
  const payload = await perform(path, options);
  if (payload && typeof payload === 'object' && 'data' in (payload as object)) {
    const envelope = payload as ApiEnvelope<T>;
    return { data: envelope.data as T, page: readPage(envelope.page) };
  }
  return { data: payload as T, page: null };
}

function readPage(candidate: unknown): ApiPage | null {
  if (!candidate || typeof candidate !== 'object') return null;
  const p = candidate as Record<string, unknown>;
  const ints = [p.current, p.per_page, p.last, p.total];
  if (!ints.every((v) => typeof v === 'number' && Number.isFinite(v))) return null;
  return {
    current: p.current as number,
    per_page: p.per_page as number,
    last: p.last as number,
    total: p.total as number,
  };
}

/** Envoie la requête et rend la réponse JSON brute (enveloppe comprise), ou lève `ApiError`. */
/** Le renoncement de l'appelant (devis devenu obsolète…), distinct d'un délai dépassé. */
function requeteAnnulee(): ApiError {
  return new ApiError('Requête annulée.', 0);
}

async function perform(path: string, options: RequestOptions = {}): Promise<unknown> {
  const { method = 'GET', body, authenticated = true, query, signal } = options;

  const headers: Record<string, string> = {
    Accept: 'application/json',
    apiKey: API_KEY,
  };
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  if (authenticated) {
    const token = await getToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  // S148 : un appelant qui a renoncé — avant l'appel ou pendant la lecture du jeton —
  // n'envoie rien ; son renoncement se dit « annulée », jamais « expirée ».
  if (signal?.aborted) throw requeteAnnulee();

  // Abandon au-delà du délai, en respectant un signal éventuellement fourni.
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
  const relayerAbandon = () => controller.abort();
  signal?.addEventListener('abort', relayerAbandon, { once: true });
  const liberer = () => {
    clearTimeout(timeout);
    signal?.removeEventListener('abort', relayerAbandon);
  };

  let response: Response;
  try {
    response = await fetch(buildUrl(path, query), {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      signal: controller.signal,
    });
  } catch {
    liberer();
    if (signal?.aborted) throw requeteAnnulee();
    if (controller.signal.aborted) {
      throw new ApiError('La requête a expiré. Vérifiez votre connexion.', 0);
    }
    throw new ApiError('Connexion au serveur impossible.', 0);
  }
  liberer();

  // S147 : le jeton est refusé quel que soit le corps — un 401 en page HTML (proxy,
  // pare-feu) doit ramener à la connexion comme un 401 JSON (S144).
  if (response.status === 401) await clearToken();

  const raw = await response.text();
  let payload: unknown = null;
  if (raw.trim() !== '') {
    try {
      payload = JSON.parse(raw);
    } catch {
      // Réponse non JSON : page d'erreur HTML, redirection vers l'installeur…
      throw new ApiError(
        `Réponse inattendue du serveur (HTTP ${response.status}).`,
        response.status,
      );
    }
  }

  if (!response.ok) {
    throw new ApiError(
      extractMessage(payload, `Erreur serveur (HTTP ${response.status}).`),
      response.status,
      extractValidationErrors(payload),
      payload && typeof payload === 'object'
        ? (payload as Record<string, unknown>).data ?? null
        : null,
    );
  }

  return payload;
}

export const api = {
  get: <T>(path: string, options?: Omit<RequestOptions, 'method' | 'body'>) =>
    request<T>(path, { ...options, method: 'GET' }),
  /** `GET` qui garde le bloc `page` (listes paginées, S78). */
  getPaged: <T>(path: string, options?: Omit<RequestOptions, 'method' | 'body'>) =>
    requestPaged<T>(path, { ...options, method: 'GET' }),
  post: <T>(path: string, body?: unknown, options?: Omit<RequestOptions, 'method' | 'body'>) =>
    request<T>(path, { ...options, method: 'POST', body }),
  put: <T>(path: string, body?: unknown, options?: Omit<RequestOptions, 'method' | 'body'>) =>
    request<T>(path, { ...options, method: 'PUT', body }),
  delete: <T>(path: string, options?: Omit<RequestOptions, 'method' | 'body'>) =>
    request<T>(path, { ...options, method: 'DELETE' }),
};
