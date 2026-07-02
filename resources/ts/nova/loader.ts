type UiConfig = {
  loading?: { class?: string };
  progress?: { color?: string };
};

type NovaWindow = Window & {
  Nova?: {
    ui?: UiConfig;
  };
};

let progress: HTMLDivElement | null = null;
let activeRequests = 0;

export function startProgress(): void {
  activeRequests += 1;

  if (!progress) {
    progress = document.createElement('div');
    progress.className = 'nova-progress';
    progress.style.background = progressColor();
    document.body.appendChild(progress);
  }

  progress.style.opacity = '1';
  progress.style.transform = 'scaleX(0.72)';
}

export function finishProgress(): void {
  activeRequests = Math.max(0, activeRequests - 1);
  if (activeRequests > 0 || !progress) {
    return;
  }

  progress.style.transform = 'scaleX(1)';
  window.setTimeout(() => {
    if (progress) {
      progress.style.opacity = '0';
      progress.style.transform = 'scaleX(0)';
    }
  }, 180);
}

export function setLoading(element: HTMLElement, loading: boolean): void {
  const target = loadingTarget(element);
  const loadingClass = config().loading?.class ?? 'nova-is-loading';

  if (loading) {
    if (!target.dataset.novaOriginalText && target instanceof HTMLButtonElement) {
      target.dataset.novaOriginalText = target.textContent ?? '';
    }

    target.classList.add(loadingClass);
    target.setAttribute('aria-busy', 'true');

    if ('disabled' in target) {
      (target as HTMLButtonElement | HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement).disabled = true;
    }

    const label = element.dataset.loading?.trim();
    if (label && target instanceof HTMLButtonElement) {
      target.textContent = label;
    }

    return;
  }

  target.classList.remove(loadingClass);
  target.removeAttribute('aria-busy');

  if ('disabled' in target) {
    (target as HTMLButtonElement | HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement).disabled = false;
  }

  if (target.dataset.novaOriginalText && target instanceof HTMLButtonElement) {
    target.textContent = target.dataset.novaOriginalText;
    delete target.dataset.novaOriginalText;
  }
}

export function installLoaderStyles(): void {
  if (document.getElementById('nova-ui-styles')) {
    return;
  }

  const style = document.createElement('style');
  style.id = 'nova-ui-styles';
  style.textContent = `
.nova-progress{position:fixed;top:0;left:0;z-index:2147483647;width:100%;height:3px;opacity:0;transform:scaleX(0);transform-origin:left;transition:transform .22s ease,opacity .18s ease}
.nova-toast-stack{position:fixed;right:16px;bottom:16px;z-index:2147483646;display:grid;gap:8px;max-width:min(360px,calc(100vw - 32px))}
.nova-toast{padding:10px 12px;border-radius:6px;color:#fff;background:#111827;box-shadow:0 10px 30px rgba(15,23,42,.18);font:14px/1.4 system-ui,sans-serif}
.nova-toast-success{background:#047857}.nova-toast-warning{background:#b45309}.nova-toast-info{background:#1d4ed8}.nova-toast-error{background:#b91c1c}
.nova-field-error{margin:6px 0 0;color:#b91c1c;font:13px/1.4 system-ui,sans-serif}
.nova-modal-backdrop{position:fixed;inset:0;z-index:2147483645;display:grid;place-items:center;background:rgba(15,23,42,.45);padding:24px}
.nova-modal-panel{max-width:min(640px,100%);max-height:calc(100vh - 48px);overflow:auto;border-radius:8px;background:#fff;color:#111827;box-shadow:0 24px 70px rgba(15,23,42,.28)}
.nova-error-overlay{position:fixed;inset:0;z-index:2147483647;overflow:auto;background:#111827;color:#fff;padding:24px;font:14px/1.5 ui-monospace,SFMono-Regular,Consolas,monospace;white-space:pre-wrap}
`;
  document.head.appendChild(style);
}

function loadingTarget(element: HTMLElement): HTMLElement {
  if (element instanceof HTMLFormElement) {
    return element.querySelector<HTMLElement>('[type="submit"]') ?? element;
  }

  return element;
}

function progressColor(): string {
  return config().progress?.color ?? '#2563eb';
}

function config(): UiConfig {
  return ((window as NovaWindow).Nova?.ui ?? {});
}
