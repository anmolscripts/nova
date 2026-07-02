type ModalCommand =
  | string
  | {
      open?: string;
      close?: boolean | string;
      content?: string;
    };

export function modal(command?: ModalCommand): void {
  if (!command) {
    return;
  }

  if (typeof command === 'string') {
    open(command);
    return;
  }

  if (command.close) {
    close(typeof command.close === 'string' ? command.close : undefined);
  }

  if (command.content) {
    openContent(command.content);
  }

  if (command.open) {
    open(command.open);
  }
}

export function open(selector: string): void {
  const source = document.querySelector<HTMLElement>(selector);
  if (!source) {
    return;
  }

  openContent(source.innerHTML);
}

export function openContent(content: string): void {
  close();

  const backdrop = document.createElement('div');
  backdrop.className = 'nova-modal-backdrop';
  backdrop.dataset.novaModal = '';
  backdrop.innerHTML = `<div class="nova-modal-panel" role="dialog" aria-modal="true">${content}</div>`;
  backdrop.addEventListener('click', (event) => {
    if (event.target === backdrop) {
      close();
    }
  });

  document.body.appendChild(backdrop);
}

export function close(selector = '[data-nova-modal]'): void {
  document.querySelectorAll(selector).forEach((node) => node.remove());
}

(window as Window & { modal?: typeof modal }).modal = modal;
