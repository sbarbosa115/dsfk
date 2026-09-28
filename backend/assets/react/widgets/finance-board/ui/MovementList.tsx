import {
  attachmentUrl,
  isVoidable,
  entryLabel,
  movementsKey,
  movementsPath,
  movementTone,
  type Finance,
  type Movement,
} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney} from '@/shared/lib/format';
import {useList} from '@/shared/lib/list';
import {
  ActionButton,
  Actions,
  actionClass,
  FilterBar,
  IconButton,
  ListView,
  Row,
  RowLegend,
} from '@/shared/ui';

/** Deposits, draws and carry-overs, newest first; voided ones stay, greyed out, with who voided them and why. */
export function MovementList({
  projectId,
  finance,
  onVoid,
  onAttach,
}: {
  projectId: number;
  finance: Finance;
  onVoid: (movement: Movement) => void;
  onAttach: (movement: Movement) => void;
}) {
  const list = useList<Movement, {q: string}>(
    movementsKey(projectId),
    movementsPath(projectId),
    {q: ''},
  );
  const money = (amount: string) => formatMoney(amount, finance.currency);
  const admin = finance.permissions.void;

  return (
    <>
      <FilterBar
        search={list.filters.q}
        onSearch={(q) => list.update({q})}
        searchPlaceholder={t('finance.searchMovements')}
      />
      <RowLegend
        statuses={[{value: 'movement_voided', label: t('finance.voided')}]}
      />
      <ListView
        list={list}
        columns={[
          t('finance.date'),
          t('finance.movement'),
          t('finance.detail'),
          t('finance.amount'),
        ]}
        empty={t('finance.noMatches')}
        emptyAll={t('finance.noMovements')}
        renderRow={(m) => (
          <Row
            key={m.id}
            status={movementTone(m)}
            label={m.voided ? t('finance.voided') : null}
            muted={m.voided !== null && m.voided !== undefined}
          >
            <td>{formatDate(m.date)}</td>
            <td>
              <strong>{t(`finance.type.${m.type}`)}</strong>
              {m.method && (
                <div className="small muted">
                  {t(`finance.methods.${m.method}`)}
                  {m.reference && ` · ${m.reference}`}
                </div>
              )}
            </td>
            <td>
              <ul className="entry-list">
                {m.entries.map((e, i) => (
                  <li key={i}>
                    {entryLabel(e)}: {money(e.amount)}
                  </li>
                ))}
              </ul>
              {m.note && <div className="small muted">{m.note}</div>}
              {m.voided && (
                <div className="small">
                  {t('finance.voidedBy', {
                    by: m.voided.by,
                    date: formatDate(m.voided.at),
                    reason: m.voided.reason,
                  })}
                </div>
              )}
            </td>
            <td className="num">{money(m.amount)}</td>
            <Actions>
              {m.attachments.map((a) => (
                <a
                  key={a.id}
                  className={actionClass('file')}
                  href={attachmentUrl(a.id)}
                  target="_blank"
                  rel="noopener noreferrer"
                  title={a.name}
                >
                  {t('finance.proof')}
                </a>
              ))}
              {admin && m.type === 'DEPOSIT' && !m.voided && (
                <ActionButton action="setup" onClick={() => onAttach(m)}>
                  {t('finance.attach')}
                </ActionButton>
              )}
              {admin && isVoidable(m) && (
                <IconButton
                  icon="ban"
                  label={t('finance.void')}
                  onClick={() => onVoid(m)}
                />
              )}
            </Actions>
          </Row>
        )}
      />
    </>
  );
}
