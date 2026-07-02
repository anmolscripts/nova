export type NovaEventName = 'nova:start' | 'nova:success' | 'nova:error' | 'nova:complete';

export function dispatch(name: NovaEventName, detail: unknown = null): void {
  window.dispatchEvent(new CustomEvent(name, { detail }));
}
