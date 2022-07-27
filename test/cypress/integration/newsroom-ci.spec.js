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
      it(`visits ${page} and checks for copyright`, () => {
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
      cy.visit('/user/login');
      cy.get('input[name="name"]').type(Cypress.env('username'));
      cy.get('input[name="pass"]').type(Cypress.env('password') + '{enter}');
      cy.contains('Member for').should('exist');
    })

    it("logs out user", () => {
      cy.visit('/user/logout');
      cy.contains('Bueno, Ivan').should('not.exist');
    })
  });

})
