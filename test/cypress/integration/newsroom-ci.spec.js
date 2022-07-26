const common_pages = [
  '/',
  '/news',
  '/calendar',
  '/multimedia',
  '/for-media',
];

describe('Newsroom Smoke Test', () => {

  describe('Check main pages', () => {
    common_pages.forEach((page) => {
      it(`visits ${page} and check for copyright`, () => {
        cy.visit(page)
        cy.contains('© 2022');
      })
    })
  });

  describe('Validate search', () => {
    it("searches for 'pretrial'", () => {
      cy.visit('/');
      cy.get('input[name="keywords"]').type('pretrial{enter}', { force: true });
      cy.contains('No results found.').should('not.exist');
    })
  });

  describe('User Login and Administration', () => {
    it("logs in user", () => {
      const username = Cypress.env('username')
      const password = Cypress.env('password')

      cy.visit('/user/login');
      cy.get('input[name="name"]').type(username, { force: true });
      cy.get('input[name="pass"]').type(password + '{enter}', { force: true });
      cy.contains('Member for').should('exist');
    })
  });

})
