export function closestActionElement(target: EventTarget | null): HTMLElement | null {
  return target instanceof Element ? target.closest<HTMLElement>('[data-action]') : null;
}

export function actionUrl(element: HTMLElement): string {
  const configured = element.dataset.action?.trim();

  if (configured) {
    return configured;
  }

  if (element instanceof HTMLFormElement) {
    return element.action || window.location.href;
  }

  const form = element.closest('form');
  return form?.action || window.location.href;
}

export function actionBody(element: HTMLElement): BodyInit | null {
  if (element instanceof HTMLFormElement) {
    return new FormData(element);
  }

  const form = element.closest('form');
  return form ? new FormData(form) : null;
}

export function actionForm(element: HTMLElement): HTMLFormElement | null {
  return element instanceof HTMLFormElement ? element : element.closest('form');
}

export async function refresh(selector: string): Promise<void> {
  const current = document.querySelector(selector);
  if (!current) {
    return;
  }

  const response = await fetch(window.location.href, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  const html = await response.text();
  const documentCopy = new DOMParser().parseFromString(html, 'text/html');
  const replacement = documentCopy.querySelector(selector);

  if (replacement) {
    current.replaceWith(document.importNode(replacement, true));
  }
}

export function refreshMany(selectors: string | string[] | undefined): Promise<void[]> {
  const list = Array.isArray(selectors) ? selectors : selectors ? [selectors] : [];

  return Promise.all(list.map((selector) => refresh(selector)));
}

export function clearValidationErrors(form: HTMLFormElement | null): void {
  if (!form) {
    return;
  }

  form.querySelectorAll('.nova-field-error').forEach((node) => node.remove());
  form.querySelectorAll<HTMLElement>('[aria-invalid="true"]').forEach((node) => {
    node.removeAttribute('aria-invalid');
  });
}

export function showValidationErrors(form: HTMLFormElement | null, errors: Record<string, string[]>): void {
  if (!form) {
    return;
  }

  clearValidationErrors(form);

  Object.entries(errors).forEach(([field, messages]) => {
    const control = form.querySelector<HTMLElement>(`[name="${cssEscape(field)}"]`);
    if (!control) {
      return;
    }

    control.setAttribute('aria-invalid', 'true');

    const error = document.createElement('p');
    error.className = 'nova-field-error';
    error.textContent = messages.join(' ');
    control.insertAdjacentElement('afterend', error);
  });
}

function cssEscape(value: string): string {
  if ('CSS' in window && typeof CSS.escape === 'function') {
    return CSS.escape(value);
  }

  return value.replace(/["\\]/g, '\\$&');
}
