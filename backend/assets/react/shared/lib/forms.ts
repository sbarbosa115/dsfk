import {type ChangeEvent, useCallback, useState} from 'react';
import {useSearchParams} from 'react-router';
import {ApiError} from '@/shared/api';
import {t} from '@/shared/i18n';
import {errorMessage} from './errors';

type FieldEvent =
  | ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  | {target: {value: string; type?: string; checked?: boolean}};

/** Form values with a `bind()` to spread onto an input, a select or a DateInput. */
export function useForm<V extends Record<string, unknown>>(initial: V) {
  const [values, setValues] = useState<V>(initial);

  const set = useCallback(
    <K extends keyof V>(name: K, value: V[K]) =>
      setValues((current) => ({...current, [name]: value})),
    [],
  );

  const bind = <K extends keyof V & string>(name: K) => ({
    name,
    value: (values[name] ?? '') as string,
    onChange: (event: FieldEvent) => {
      const target = event.target as {
        value: string;
        type?: string;
        checked?: boolean;
      };
      set(
        name,
        (target.type === 'checkbox' ? target.checked : target.value) as V[K],
      );
    },
  });

  return {values, setValues, set, bind};
}

export interface SubmitState {
  busy: boolean;
  errors: Record<string, string>;
  formError: string | null;
}

export type SubmitResult<T> =
  {ok: true; value: T} | {ok: false; error: unknown};

/**
 * Wraps a submit: tracks the busy state and maps an API error to messages per field (422) or one message for the
 * form.
 */
export function useSubmit() {
  const [state, setState] = useState<SubmitState>({
    busy: false,
    errors: {},
    formError: null,
  });

  const run = useCallback(
    async <T>(action: () => Promise<T>): Promise<SubmitResult<T>> => {
      setState({busy: true, errors: {}, formError: null});
      try {
        const value = await action();
        setState({busy: false, errors: {}, formError: null});

        return {ok: true, value};
      } catch (error) {
        const errors = error instanceof ApiError ? error.fieldErrors() : {};
        const hasFieldErrors = Object.keys(errors).length > 0;
        setState({
          busy: false,
          errors,
          formError: hasFieldErrors
            ? t('errors.validation_failed')
            : errorMessage(error),
        });

        return {ok: false, error};
      }
    },
    [],
  );

  /** Forget the last errors (the user is typing again). */
  const reset = useCallback(
    () => setState({busy: false, errors: {}, formError: null}),
    [],
  );

  return {...state, run, reset};
}

/** Empty strings become null, so optional fields are cleared on the API. */
export function emptyToNull<V extends Record<string, unknown>>(
  values: V,
): {[K in keyof V]: V[K] | null} {
  return Object.fromEntries(
    Object.entries(values).map(([key, value]) => [
      key,
      value === '' ? null : value,
    ]),
  ) as {[K in keyof V]: V[K] | null};
}

/**
 * The tab a page shows, kept in the URL so a link, a reload and the back button land on the same view. The first
 * tab is the default and leaves the URL clean.
 */
export function useTabParam<Tab extends string>(
  tabs: readonly Tab[],
  key = 'tab',
): [Tab, (next: Tab) => void] {
  const [params, setParams] = useSearchParams();
  const current = params.get(key);
  const value = (tabs as readonly string[]).includes(current ?? '')
    ? (current as Tab)
    : (tabs[0] as Tab);

  const setTab = useCallback(
    (next: Tab) => {
      const updated = new URLSearchParams(params);
      if (next === tabs[0]) {
        updated.delete(key);
      } else {
        updated.set(key, next);
      }
      setParams(updated, {replace: true});
    },
    [params, setParams, key, tabs],
  );

  return [value, setTab];
}
