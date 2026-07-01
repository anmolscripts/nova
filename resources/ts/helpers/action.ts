export type ActionPayload = Record<string, unknown>;

export class ActionError extends Error {
  public readonly status: number;
  public readonly payload: unknown;

  public constructor(message: string, status: number, payload: unknown) {
    super(message);
    this.name = 'ActionError';
    this.status = status;
    this.payload = payload;
  }
}

export async function action<T = unknown>(url: string, payload: ActionPayload = {}): Promise<T> {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ActionError(actionMessage(data), response.status, data);
  }

  return data as T;
}

function csrfToken(): string {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');

  if (meta?.content) {
    return meta.content;
  }

  const input = document.querySelector<HTMLInputElement>('input[name="_token"]');

  return input?.value ?? '';
}

function actionMessage(payload: unknown): string {
  if (payload && typeof payload === 'object' && 'message' in payload) {
    return String((payload as { message: unknown }).message);
  }

  return 'Action request failed.';
}
