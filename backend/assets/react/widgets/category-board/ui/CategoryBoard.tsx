import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {fetchFinance, financeKey} from '@/entities/finance';
import {fetchPlan, planKey, type Plan} from '@/entities/plan';
import {
  AddCategoryModal,
  RemoveCategoryModal,
  RenameCategoryModal,
} from '@/features/plan-categories';
import {t} from '@/shared/i18n';
import {formatMoney, formatPercent} from '@/shared/lib/format';
import {
  ActionButton,
  Actions,
  DataTable,
  EmptyState,
  ErrorState,
  Loading,
  ProgressBar,
  Row,
  RowActions,
  Stat,
  TabIntro,
} from '@/shared/ui';

type Category = Plan['categories'][number];

/** One category with its money, in major units. */
export type CategoryFigures = {
  category: Category;
  budget: number;
  spent: number;
  available: number;
  /** Spent over budgeted, in basis points; 10000 when something is spent against no budget at all. */
  executed: number;
  over: boolean;
};

/**
 * What each category has budgeted (the plan's lines) and spent (approved and reimbursed expenses), the largest
 * budget first.
 */
export function categoryFigures(
  plan: Plan,
  spentById: ReadonlyMap<number, string>,
): CategoryFigures[] {
  const budgets = new Map(
    plan.budget?.byCategory.map((c) => [c.categoryId, c.total]),
  );

  return plan.categories
    .map((category) => {
      const budget = Number(budgets.get(category.id) ?? 0);
      const spent = Number(spentById.get(category.id) ?? 0);
      const executed =
        budget > 0
          ? Math.round((spent / budget) * 10000)
          : spent > 0
            ? 10000
            : 0;

      return {
        category,
        budget,
        spent,
        available: budget - spent,
        executed,
        over: spent > budget,
      };
    })
    .sort(
      (a, b) =>
        b.budget - a.budget || a.category.name.localeCompare(b.category.name),
    );
}

type Dialog =
  | {kind: 'add'}
  | {kind: 'rename'; category: Category}
  | {kind: 'remove'; category: Category};

/** The "Categorías" tab of a project: budget against spending per category, and where categories are managed. */
export function CategoryBoard({projectId}: {projectId: number}) {
  const plan = useQuery({
    queryKey: planKey(projectId),
    queryFn: () => fetchPlan(projectId),
  });
  const financials = plan.data?.permissions.viewFinancials ?? false;
  // Spending comes with the finance summary, which only Admins and the PM may read.
  const finance = useQuery({
    queryKey: financeKey(projectId),
    queryFn: () => fetchFinance(projectId),
    enabled: financials,
  });
  const [dialog, setDialog] = useState<Dialog | null>(null);

  const error = plan.error ?? (financials ? finance.error : null);
  if (error) {
    return (
      <ErrorState
        error={error}
        onRetry={() => {
          void plan.refetch();
          if (financials) {
            void finance.refetch();
          }
        }}
      />
    );
  }
  if (!plan.data || (financials && !finance.data)) {
    return <Loading />;
  }
  const p = plan.data;
  const manage = p.permissions.manageCategories;
  const close = () => setDialog(null);

  return (
    <div className="settings-sections">
      <TabIntro>
        {financials ? t('categories.intro') : t('categories.introTeamLead')}
      </TabIntro>
      {financials ? (
        <Spending
          plan={p}
          spentById={
            new Map(finance.data?.categories.map((c) => [c.id, c.spent]))
          }
          manage={manage}
          onAdd={() => setDialog({kind: 'add'})}
          onRename={(category) => setDialog({kind: 'rename', category})}
          onRemove={(category) => setDialog({kind: 'remove', category})}
        />
      ) : (
        <Names plan={p} />
      )}
      {dialog?.kind === 'add' && <AddCategoryModal plan={p} onClose={close} />}
      {dialog?.kind === 'rename' && (
        <RenameCategoryModal category={dialog.category} onClose={close} />
      )}
      {dialog?.kind === 'remove' && (
        <RemoveCategoryModal category={dialog.category} onClose={close} />
      )}
    </div>
  );
}

function Spending({
  plan,
  spentById,
  manage,
  onAdd,
  onRename,
  onRemove,
}: {
  plan: Plan;
  spentById: ReadonlyMap<number, string>;
  manage: boolean;
  onAdd: () => void;
  onRename: (category: Category) => void;
  onRemove: (category: Category) => void;
}) {
  const money = (amount: number) =>
    formatMoney(amount.toFixed(2), plan.project.currency);
  const rows = categoryFigures(plan, spentById);
  const budget = rows.reduce((sum, r) => sum + r.budget, 0);
  const spent = rows.reduce((sum, r) => sum + r.spent, 0);
  const over = rows.filter((r) => r.over).length;

  return (
    <section className="card">
      <Header count={rows.length} over={over} manage={manage} onAdd={onAdd} />
      {rows.length > 0 && (
        <div className="stat-grid">
          <Stat label={t('categories.budgeted')} value={money(budget)} money />
          <Stat label={t('categories.spent')} value={money(spent)} money>
            <span className="small muted">
              {t('categories.executedTotal', {
                percent: formatPercent(
                  budget > 0 ? Math.round((spent / budget) * 10000) : 0,
                ),
              })}
            </span>
          </Stat>
          <Stat
            label={t('categories.available')}
            value={money(budget - spent)}
            money
          >
            {over > 0 && (
              <span className="small text-danger strong">
                {t('categories.over', {count: over})}
              </span>
            )}
          </Stat>
        </div>
      )}
      {rows.length === 0 ? (
        <EmptyState>{t('categories.empty')}</EmptyState>
      ) : (
        <DataTable
          label={t('categories.title')}
          columns={[
            t('plan.category'),
            t('categories.budgeted'),
            t('categories.spent'),
            t('categories.available'),
            t('categories.executed'),
          ]}
          actions={manage}
          rows={rows}
          renderRow={(r) => (
            <Row
              key={r.category.id}
              status={r.over ? 'category_over_budget' : null}
              label={r.over ? t('categories.overBudget') : null}
            >
              <td className="strong">{r.category.name}</td>
              <td className="num">{money(r.budget)}</td>
              <td className="num">{money(r.spent)}</td>
              <td className={`num ${r.over ? 'text-danger strong' : ''}`}>
                {money(r.available)}
              </td>
              <td className="category-executed">
                <ProgressBar
                  value={r.executed}
                  label={t('categories.executedOf', {
                    name: r.category.name,
                  })}
                />
              </td>
              {manage && (
                <Actions>
                  <CategoryActions
                    category={r.category}
                    onRename={onRename}
                    onRemove={onRemove}
                  />
                </Actions>
              )}
            </Row>
          )}
        />
      )}
    </section>
  );
}

/** Team Leads see no money: only the names they file their expenses under. */
function Names({plan}: {plan: Plan}) {
  const names = [...plan.categories].sort((a, b) =>
    a.name.localeCompare(b.name),
  );

  return (
    <section className="card">
      <Header count={names.length} over={0} manage={false} onAdd={() => {}} />
      {names.length === 0 ? (
        <EmptyState>{t('categories.emptyTeamLead')}</EmptyState>
      ) : (
        <ul className="category-names">
          {names.map((c) => (
            <li key={c.id}>{c.name}</li>
          ))}
        </ul>
      )}
    </section>
  );
}

function Header({
  count,
  over,
  manage,
  onAdd,
}: {
  count: number;
  over: number;
  manage: boolean;
  onAdd: () => void;
}) {
  return (
    <div className="card-header">
      <div>
        <h2>{t('categories.title')}</h2>
        {count > 0 && (
          <p className="small muted">
            {t('categories.count', {count})}
            {over > 0 && (
              <>
                {' · '}
                <span className="text-danger strong">
                  {t('categories.over', {count: over})}
                </span>
              </>
            )}
          </p>
        )}
      </div>
      {manage && (
        <ActionButton action="setup" main size="md" icon="plus" onClick={onAdd}>
          {t('categories.add')}
        </ActionButton>
      )}
    </div>
  );
}

/** Renaming is the everyday fix; removing only works while nothing uses the category. Both stay quiet. */
function CategoryActions({
  category,
  onRename,
  onRemove,
}: {
  category: Category;
  onRename: (category: Category) => void;
  onRemove: (category: Category) => void;
}) {
  return (
    <RowActions
      quiet
      name={category.name}
      edit={{label: t('plan.rename'), onClick: () => onRename(category)}}
      more={[
        {
          items: [
            {
              label: t('plan.remove'),
              action: 'danger',
              icon: 'ban',
              onClick: () => onRemove(category),
            },
          ],
        },
      ]}
    />
  );
}
