import { clearValidationErrors, refreshMany, showValidationErrors } from './dom';
import { dispatch } from './events';
import { modal } from './modal';
import { refreshCsrfToken } from './request';
import * as toast from './toast';

type MessageType = 'success' | 'warning' | 'info' | 'error';

type ActionPayload = {
  ok?: boolean;
  data?: ActionData;
  errors?: Record<string, string[]>;
  message?: string;
  redirect?: string;
  redirectTo?: string;
};

type ActionData = {
  refresh?: string | string[];
  redirect?: string;
  redirectTo?: string;
  modal?: Parameters<typeof modal>[0];
  success?: string;
  warning?: string;
  info?: string;
  error?: string;
  message?: string;
};

export async function handleActionResponse(response: Response, form: HTMLFormElement | null): Promise<void> {
  const payload = await parse(response);

  if (response.redirected) {
    window.location.assign(response.url);
    return;
  }

  if (response.status === 419) {
    await refreshCsrfToken();
    toast.warning('Your session expired. Please try again.');
    dispatch('nova:error', { status: 419, payload });
    return;
  }

  if (response.status === 422) {
    const errors = payload?.errors ?? {};
    showValidationErrors(form, errors);
    toast.error(payload?.message ?? 'Validation failed.');
    dispatch('nova:error', { status: 422, payload });
    return;
  }

  if (response.status >= 500) {
    showServerError(response, payload);
    dispatch('nova:error', { status: response.status, payload });
    return;
  }

  if (!response.ok || payload?.ok === false) {
    toast.error(payload?.message ?? payload?.data?.error ?? 'Action failed.');
    dispatch('nova:error', { status: response.status, payload });
    return;
  }

  clearValidationErrors(form);
  await applySuccess(payload);
  dispatch('nova:success', payload);
}

async function applySuccess(payload: ActionPayload | null): Promise<void> {
  const data = payload?.data ?? {};
  const redirect = payload?.redirect ?? payload?.redirectTo ?? data.redirect ?? data.redirectTo;

  displayMessages(payload, data);

  if (data.modal) {
    modal(data.modal);
  }

  await refreshMany(data.refresh);

  if (redirect) {
    window.location.assign(redirect);
  }
}

async function parse(response: Response): Promise<ActionPayload | null> {
  const type = response.headers.get('Content-Type') ?? '';
  if (type.includes('application/json')) {
    return (await response.json().catch(() => null)) as ActionPayload | null;
  }

  return null;
}

function displayMessages(payload: ActionPayload | null, data: ActionData): void {
  const typed: Array<[MessageType, string | undefined]> = [
    ['success', data.success],
    ['warning', data.warning],
    ['info', data.info],
    ['error', data.error],
  ];

  typed.forEach(([type, message]) => {
    if (message) {
      toast[type](message);
    }
  });

  if (payload?.message && !typed.some(([, message]) => Boolean(message))) {
    toast.success(payload.message);
  }

  if (data.message && !payload?.message) {
    toast.info(data.message);
  }
}

function showServerError(response: Response, payload: ActionPayload | null): void {
  const message = payload?.message ?? `Nova action failed with status ${response.status}.`;

  if (isDevelopment()) {
    const overlay = document.createElement('pre');
    overlay.className = 'nova-error-overlay';
    overlay.textContent = message;
    document.body.appendChild(overlay);
    return;
  }

  toast.error(message);
}

function isDevelopment(): boolean {
  const host = window.location.hostname;
  const configured = (window as Window & { Nova?: { env?: string } }).Nova?.env;

  return configured === 'local' || configured === 'development' || host === 'localhost' || host === '127.0.0.1';
}
