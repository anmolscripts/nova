import { closestActionElement, actionForm } from './dom';
import { dispatch } from './events';
import { finishProgress, installLoaderStyles, setLoading, startProgress } from './loader';
import { sendAction } from './request';
import { handleActionResponse } from './response';
import { error as toastError } from './toast';

let initialized = false;

export function start(): void {
  if (initialized) {
    return;
  }

  initialized = true;
  installLoaderStyles();
  document.addEventListener('click', onClick);
  document.addEventListener('submit', onSubmit);
}

async function run(element: HTMLElement): Promise<void> {
  const form = actionForm(element);

  dispatch('nova:start', { element });
  setLoading(element, true);
  startProgress();

  try {
    const response = await sendAction(element);
    await handleActionResponse(response, form);
  } catch (error) {
    dispatch('nova:error', { error });
    toastError('Network error. Please try again.');
  } finally {
    setLoading(element, false);
    finishProgress();
    dispatch('nova:complete', { element });
  }
}

function onClick(event: MouseEvent): void {
  const element = closestActionElement(event.target);
  if (!element || element instanceof HTMLFormElement) {
    return;
  }

  event.preventDefault();
  void run(element);
}

function onSubmit(event: SubmitEvent): void {
  const form = event.target instanceof HTMLFormElement ? event.target : null;
  if (!form?.matches('[data-action]')) {
    return;
  }

  event.preventDefault();
  void run(form);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', start, { once: true });
} else {
  start();
}
