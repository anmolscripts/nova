import { actionBody, actionUrl } from './dom';

export async function sendAction(element: HTMLElement): Promise<Response> {
  const body = actionBody(element);

  return fetch(actionUrl(element), {
    method: 'POST',
    headers: headers(body),
    body,
  });
}

export async function refreshCsrfToken(): Promise<void> {
  const response = await fetch(window.location.href, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });
  const html = await response.text();
  const documentCopy = new DOMParser().parseFromString(html, 'text/html');
  const nextToken = csrfFrom(documentCopy);

  if (!nextToken) {
    return;
  }

  document.querySelectorAll<HTMLInputElement>('input[name="_token"]').forEach((input) => {
    input.value = nextToken;
  });

  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  if (meta) {
    meta.content = nextToken;
  }
}

function headers(body: BodyInit | null): HeadersInit {
  const values: Record<string, string> = {
    Accept: 'application/json',
    'X-CSRF-Token': csrfToken(),
    'X-Requested-With': 'XMLHttpRequest',
  };

  if (!(body instanceof FormData)) {
    values['Content-Type'] = 'application/json';
  }

  return values;
}

function csrfToken(): string {
  return csrfFrom(document) ?? '';
}

function csrfFrom(source: Document): string | null {
  const meta = source.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  if (meta?.content) {
    return meta.content;
  }

  return source.querySelector<HTMLInputElement>('input[name="_token"]')?.value ?? null;
}
