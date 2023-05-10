import { commonTests } from "../support/common";

const main_pages = [
  '/',
  '/online-services',
  '/forms-filings',
  '/self-help',
  'general-information',
];

describe('Smoke Test', () => {
  commonTests(main_pages, 'search', '0 results for');
})
