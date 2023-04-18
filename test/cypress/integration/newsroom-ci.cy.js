import { commonTests } from "../support/common";

const main_pages = [
  '/',
  '/news',
  '/calendar',
  '/multimedia',
  '/for-media',
];

describe('Newsroom Smoke Test', () => {
  commonTests(main_pages, 'keywords', 'No results found.');
})
