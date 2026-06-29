export function commonTests(pages, search_keyword, search_expected) {
  describe("Check main pages", () => {
    pages.forEach((page) => {
      it(`visits ${page} and checks for copyright`, () => {
        cy.visit(page);
        cy.contains("© " + new Date().getFullYear());
      });
    });
  });

  describe("Validate search", () => {
    it("searches for 'trial'", () => {
      cy.visit("/");
      cy.get('input[name="' + search_keyword + '"]').type("trial{enter}", {
        force: true,
      });
      cy.contains(search_expected).should("exist");
    });
  });

  describe("User Login and Administration", () => {
    it("logs in user", () => {
      cy.visit("/user/login");
      // Scope to the user login form: the page can contain other forms
      // (e.g. Azure AD / OpenID Connect), so target this form explicitly and
      // click its submit button rather than relying on {enter}.
      cy.get(".user-login-form #edit-name").type("test");
      cy.get(".user-login-form #edit-pass").type("test");
      cy.get(".user-login-form #edit-submit").click();
      // Confirm login by checking the Drupal session cookie is set. Using
      // .should() retries until the cookie appears after the login request.
      cy.getCookies().should((cookies) => {
        const sessionCookie = cookies.find((cookie) => cookie.name.includes("SESS"));
        expect(sessionCookie).to.exist;
      });
    });

    it("logs out user", () => {
      cy.visit("/user/logout");
      // Confirm logout by checking the Drupal session cookie is cleared.
      cy.getCookies().should((cookies) => {
        const sessionCookie = cookies.find((cookie) => cookie.name.includes("SESS"));
        expect(sessionCookie).to.not.exist;
      });
    });
  });
}
