import type {ReactNode} from 'react';
import {NumericFormat, type NumericFormatProps} from 'react-number-format';
import {LOCALE, t} from '@/shared/i18n';
import {formatAmountForInput, separators} from '@/shared/lib/format';
import {Field} from './ui';

// Amounts are BIGINT minor units; the API takes up to 13 whole digits.
const MAX_WHOLE_DIGITS = 13;

/**
 * The input behind every amount in the app: it groups thousands as the person types, in the person's locale
 * ("2.500.000,50" in es-CO, "2,500,000.50" in en-US). The value stays that formatted text: convert it with
 * parseAmountInput() on submit, so amounts never become floats. Use it bare where a table row or a list already
 * labels the amount; otherwise use MoneyField.
 */
type AmountInputProps = Omit<
  NumericFormatProps,
  'value' | 'onChange' | 'onValueChange'
> & {
  locale?: string;
  /** The formatted text, as typed. */
  value: string | null | undefined;
  onChange: (text: string) => void;
};

export function AmountInput({
  locale = LOCALE,
  value,
  onChange,
  className = '',
  ...props
}: AmountInputProps) {
  const {group, decimal} = separators(locale);

  return (
    <NumericFormat
      // The library reads a string value as a plain number ("2500.5"), so the person's formatted text is
      // converted first: handed "2.500" as is, es-CO's group dot would be read as a decimal point.
      value={toNumericString(value, group, decimal)}
      valueIsNumericString
      onValueChange={({formattedValue}, {source}) => {
        // "prop" changes are the parent setting the value: echoing them back would loop.
        if (source !== 'prop') onChange(formattedValue);
      }}
      thousandSeparator={group}
      decimalSeparator={decimal}
      // Only the locale's decimal key: in es-CO "." groups thousands, so it must not start the decimals.
      allowedDecimalSeparators={[decimal]}
      decimalScale={2}
      allowNegative={false}
      allowLeadingZeros={false}
      isAllowed={({value: digits}) =>
        (digits.split('.')[0] ?? '').length <= MAX_WHOLE_DIGITS
      }
      inputMode="decimal"
      autoComplete="off"
      className={`money-input ${className}`.trim()}
      {...props}
    />
  );
}

/**
 * "2.500.000,5" (es-CO) → "2500000.5". A trailing decimal separator is kept ("2.500," → "2500."), so the one the
 * person just typed isn't dropped on the next render.
 */
function toNumericString(
  text: string | null | undefined,
  group: string,
  decimal: string,
): string {
  const value = String(text ?? '')
    .split(group)
    .join('')
    .replace(decimal, '.');
  return value.replace(/[^\d.]/g, '');
}

/** A labelled amount field: "Canon mensual (COP)", with an example in the person's format. */
interface MoneyFieldProps {
  label: string;
  currency: string;
  locale?: string;
  error?: string | null;
  optional?: boolean;
  hint?: ReactNode;
  value: string | null | undefined;
  onChange: (text: string) => void;
}

export default function MoneyField({
  label,
  currency,
  locale = LOCALE,
  error,
  optional,
  hint,
  value,
  onChange,
}: MoneyFieldProps) {
  return (
    <Field
      label={`${label} (${currency})`}
      error={error}
      optional={optional}
      hint={
        hint ??
        t('money.hint', {example: formatAmountForInput('2500000.50', locale)})
      }
    >
      <AmountInput locale={locale} value={value} onChange={onChange} />
    </Field>
  );
}
