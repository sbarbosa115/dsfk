import {useEffect, useRef, useState} from 'react';
import {LOCALE, t} from '@/shared/i18n';
import {partsOrder, toIso, toText} from '@/shared/lib/dates';
import Icon from './Icon';

/**
 * Every date field in the app. A native <input type="date"> is written in the browser's language (mm/dd/yyyy on
 * an English Chrome); this one is typed day first, as in Colombia, with the browser's calendar one click away.
 *
 * A drop-in for the native input: `value` and the `onChange` event carry YYYY-MM-DD, so `{...form.bind('x')}`
 * works. A date typed halfway (or impossible) reaches the form as '', so the API's validation answers it.
 */
export interface DateChange {
  target: {name?: string; value: string; type: 'date'};
}

export interface DateInputProps {
  value?: string;
  onChange?: (event: DateChange) => void;
  name?: string;
  min?: string;
  max?: string;
  required?: boolean;
  disabled?: boolean;
  id?: string;
}

const ORDER = partsOrder(LOCALE);

export default function DateInput({
  value = '',
  onChange,
  name,
  min,
  max,
  required,
  disabled,
  id,
}: DateInputProps) {
  const [text, setText] = useState(() => toText(value, ORDER));
  const emitted = useRef(value);
  const picker = useRef<HTMLInputElement>(null);

  // A value set from outside (a reset, the calendar, an edit modal opening) replaces what is typed; the echo of
  // what this box just sent does not, or typing "1/" would be wiped as soon as it emitted ''.
  useEffect(() => {
    if (value !== emitted.current) {
      emitted.current = value;
      setText(toText(value, ORDER));
    }
  }, [value]);

  const emit = (iso: string) => {
    emitted.current = iso;
    onChange?.({target: {name, value: iso, type: 'date'}});
  };

  const placeholder = ORDER.map((type) => t(`common.datePart.${type}`)).join(
    '/',
  );

  return (
    <span className="date-input">
      <input
        id={id}
        type="text"
        inputMode="numeric"
        autoComplete="off"
        name={name}
        value={text}
        placeholder={placeholder}
        required={required}
        disabled={disabled}
        onChange={(event) => {
          setText(event.target.value);
          emit(toIso(event.target.value, ORDER) ?? '');
        }}
        onBlur={() => {
          const iso = toIso(text, ORDER);
          if (iso) {
            setText(toText(iso, ORDER));
          }
        }}
      />
      <button
        type="button"
        className="date-input-picker"
        aria-label={t('common.pickDate')}
        data-tooltip={t('common.pickDate')}
        disabled={disabled}
        onClick={() => {
          try {
            picker.current?.showPicker();
          } catch {
            picker.current?.focus();
          }
        }}
      >
        <Icon name="calendar" size={16} />
      </button>
      {/* The browser's own calendar, opened by the button above; never shown or tabbed to itself. */}
      <input
        ref={picker}
        type="date"
        className="date-input-native"
        tabIndex={-1}
        aria-hidden="true"
        value={toIso(text, ORDER) || ''}
        min={min}
        max={max}
        onChange={(event) => {
          setText(toText(event.target.value, ORDER));
          emit(event.target.value);
        }}
      />
    </span>
  );
}
