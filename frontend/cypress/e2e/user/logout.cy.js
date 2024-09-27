describe("the logout menu item", () => {
  beforeEach(() => {
    cy.login();
  });

  it("logs the user out", function() {
    cy.visit(Cypress.env("baseUrl") + "/scan");
    cy.get("[data-test=user-menu-icon]").click();
    cy.get("[data-test=user-menu-logout]").click();

    cy.url().should("include", "/login");

    cy.getVuexStore().then(({ getters }) => {
      expect(getters.all_permissions).to.be.null;
      expect(getters.currentUser).to.be.null;
      expect(getters.errors).to.be.empty;
      expect(getters.loggedIn).to.be.false;
      expect(getters.publicPages).to.have.length.greaterThan(1);
      expect(getters.token).to.be.null;
      expect(getters.userPermissions).to.be.null;
      expect(getters.userRole).to.be.null;
      expect(getters.verified).to.not.be.null;
    });
  });
});
