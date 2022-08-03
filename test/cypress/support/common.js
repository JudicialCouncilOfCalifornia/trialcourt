export function commonTests(pages, search_keyword, search_expected) {
  describe('Check main pages', () => {
    pages.forEach((page) => {
      it(`visits ${page} and checks for copyright`, () => {
        cy.visit(page)
        cy.contains('© 2022');
      })
    })
  });

  describe('Validate search', () => {
    it("searches for 'trial'", () => {
      cy.visit('/');
      cy.get('input[name="' + search_keyword + '"]').type('trial{enter}', {force: true});
      cy.contains(search_expected).should('not.exist');
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
}
