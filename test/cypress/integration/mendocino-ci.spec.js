import { commonTests } from "../support/common";

const main_pages = [
  '/',
  '/forms-filing',
  '/self-help',
  '/divisions',
  'general-information',
];

describe('Smoke Test', () => {
  commonTests(main_pages, 'search', '0 results for');
})
