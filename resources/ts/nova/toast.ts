type ToastType = 'success' | 'warning' | 'info' | 'error';

type NovaWindow = Window & {
  Nova?: {
    ui?: {
      toast?: { duration?: number };
    };
  };
};

export function toast(message: string, type: ToastType = 'info'): void {
  if (!message) {
    return;
  }

  const item = document.createElement('div');
  item.className = `nova-toast nova-toast-${type}`;
  item.setAttribute('role', type === 'error' ? 'alert' : 'status');
  item.textContent = message;

  container().appendChild(item);

  window.setTimeout(() => {
    item.remove();
  }, duration());
}

export const success = (message: string): void => toast(message, 'success');
export const warning = (message: string): void => toast(message, 'warning');
export const info = (message: string): void => toast(message, 'info');
export const error = (message: string): void => toast(message, 'error');

function container(): HTMLElement {
  let node = document.querySelector<HTMLElement>('.nova-toast-stack');
  if (!node) {
    node = document.createElement('div');
    node.className = 'nova-toast-stack';
    document.body.appendChild(node);
  }

  return node;
}

function duration(): number {
  return (window as NovaWindow).Nova?.ui?.toast?.duration ?? 4000;
}
