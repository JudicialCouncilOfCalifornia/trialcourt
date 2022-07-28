import { commonTests } from "../support/common";

const main_pages = [
  '/',
  '/online-services',
  '/forms-filing',
  '/self-help-center',
  '/divisions',
  'general-information',
];

describe('Butte Smoke Test', () => {
  commonTests(main_pages, 'search', '0 results for');
})
