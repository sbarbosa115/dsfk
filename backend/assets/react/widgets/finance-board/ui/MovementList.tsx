import {
  attachmentUrl,
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
  Actions,
  FilterBar,
  ListView,
  Row,
  RowActions,
  RowLegend,
  type RowAction,
} from '@/shared/ui';
import {movementRowActions, type MovementRowAction} from '../model/rowActions';

/** A movement's row: its proof (or asking for it), and voiding last (model/rowActions decides which). */
function MovementActions({
  movement: m,
  admin,
  onAttach,
  onVoid,
}: {
  movement: Movement;
  admin: boolean;
  onAttach: () => void;
  onVoid: () => void;
}) {
  const plan = movementRowActions(m, admin);
  const [first, ...others] = m.attachments ?? [];
  const proof = (a: NonNullable<typeof first>): RowAction => ({
    label: t('finance.proof'),
    action: 'file',
    icon: 'file',
    description: a.name,
    href: attachmentUrl(a.id),
  });
  const actions: Record<MovementRowAction, RowAction | null> = {
    attach: {
      label: t('finance.attach'),
      action: 'setup',
      icon: 'paperclip',
      onClick: onAttach,
    },
    proof: first ? proof(first) : null,
    void: {
      label: t('finance.void'),
      action: 'danger',
      icon: 'ban',
      onClick: onVoid,
    },
  };
  return (
    <RowActions
      name={`${t(`finance.type.${m.type}`)} · ${formatDate(m.date)}`}
      main={plan.main && actions[plan.main]}
      more={[
        {items: [...others.map(proof), ...plan.items.map((a) => actions[a])]},
        {items: plan.last.map((a) => actions[a])},
      ]}
    />
  );
}

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
              <MovementActions
                movement={m}
                admin={admin}
                onAttach={() => onAttach(m)}
                onVoid={() => onVoid(m)}
              />
            </Actions>
          </Row>
        )}
      />
    </>
  );
}
