import auditImg from '../assets/audit.png';
import budgetAndPlanImg from '../assets/budget-and-plan.png';
import dashboardImg from '../assets/dashboard.png';
import expenseReviewImg from '../assets/expense-review.png';
import fundsImg from '../assets/funds.png';
import leadExpensesImg from '../assets/lead-expenses.png';
import linesAndMilestonesImg from '../assets/lines-and-milestones.png';
import pettyCashImg from '../assets/petty-cash.png';
import recordExpenseImg from '../assets/record-expense.png';
import settingsImg from '../assets/settings.png';
import signInImg from '../assets/sign-in.png';
import usersImg from '../assets/users.png';
import viewAsImg from '../assets/view-as.png';
import topicsEs from '../content/topics.es.json';

/**
 * The user manual. Its text is the Spanish translation in content/topics.es.json rather than i18n/es.ts: it is
 * long-form prose rather than interface labels, and each topic carries its own audience, keywords and screenshots
 * (named by key, e.g. "petty-cash"). Labels quoted there must match i18n/es.ts exactly, or the instructions stop
 * matching what people see.
 */
export type HelpAudience =
  'ADMIN' | 'SUPER_ADMIN' | 'PROJECT_MANAGER' | 'TEAM_LEAD';

export interface HelpSection {
  heading?: string;
  body?: string[];
  steps?: string[];
  note?: string;
  image?: {src: string; caption: string};
}

export interface HelpTopic {
  id: string;
  title: string;
  summary: string;
  /** Who it is for: a person sees it when one of these matches a role they hold. */
  audiences: HelpAudience[];
  /** Extra search words, including wording people may try that is not in the text. */
  keywords: string[];
  sections: HelpSection[];
  related?: string[];
}

/** The screenshots a topic can show, by the key its image names in topics.es.json. */
const IMAGES: Record<string, string> = {
  'audit': auditImg,
  'budget-and-plan': budgetAndPlanImg,
  'dashboard': dashboardImg,
  'expense-review': expenseReviewImg,
  'funds': fundsImg,
  'lead-expenses': leadExpensesImg,
  'lines-and-milestones': linesAndMilestonesImg,
  'petty-cash': pettyCashImg,
  'record-expense': recordExpenseImg,
  'settings': settingsImg,
  'sign-in': signInImg,
  'users': usersImg,
  'view-as': viewAsImg,
};

function withImages(section: HelpSection): HelpSection {
  if (!section.image) {
    return section;
  }
  const src = IMAGES[section.image.src];
  if (!src) {
    throw new Error(`Unknown help image: ${section.image.src}`);
  }

  return {...section, image: {...section.image, src}};
}

export const helpTopics: HelpTopic[] = (topicsEs as HelpTopic[]).map(
  (topic) => ({...topic, sections: topic.sections.map(withImages)}),
);

export function topicById(id: string): HelpTopic | undefined {
  return helpTopics.find((topic) => topic.id === id);
}
