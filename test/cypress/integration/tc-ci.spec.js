describe('TC-SLO CI', () => {

  it("Gives results for this search", () => {
    cy.setResolution([1920, 1080]);
    cy.visit('/');
    cy.get('[action="/search"]')
      .type('How do I pay a fine?');
    cy.get('.jcc-fieldset-search button')
      .click();
    cy.contains('No results found.').should('not.exist');
  });

  it("Has no detectable a11y violations", () => {
    cy.setResolution([1920, 1080]);
    cy.visit('/');
    cy.injectAxe();
    cy.checkA11y();
  });
});

