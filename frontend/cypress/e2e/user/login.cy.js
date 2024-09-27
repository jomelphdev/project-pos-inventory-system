describe("the login screen", () => {
  beforeEach(() => {
    cy.visit(Cypress.env("baseUrl"));
    cy.get("[data-test=username-input]").as("usernameInput");
    cy.get("[data-test=password-input]").as("passwordInput");
    cy.get("[data-test=login-button]").as("loginButton");
  });

  it("redirects to /login when logged out", function() {
    cy.url().should("include", "/login");
  });

  it("shows the login form", function() {
    cy.contains(".h1", "Login");
    cy.contains(".rr-field__label", "Username");
    cy.contains(".rr-field__label", "Password");
    cy.contains(".rr-button--primary", "Login");
    cy.get("@loginButton").should("have.attr", "disabled");
  });

  it("notifies of non-existent user", function() {
    cy.get("@usernameInput").type("NonExistentUser");
    cy.get("@passwordInput").type("password");
    cy.get("@loginButton").click();
    cy.checkToastedMessage("User does not exist.", "error");
  });

  it("notifies of incorrect password", function() {
    cy.get("@usernameInput").type("TestUser");
    cy.get("@passwordInput").type("InvalidPassword");
    cy.get("@loginButton").click();
    cy.checkToastedMessage("Incorrect password.", "error");
  });

  it("logs in the user", function() {
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

    cy.get("@usernameInput").type("TestUser");
    cy.get("@passwordInput").type("Test123");
    cy.get("@loginButton").click();
    cy.url().should("include", "/scan");

    cy.getVuexStore().then(({ getters }) => {
      expect(getters.publicPages).to.have.length.greaterThan(1);
      expect(getters.currentUser).to.be.a("object");
      expect(getters.errors).to.be.empty;
      expect(getters.loggedIn).to.be.true;
      expect(getters.publicPages).to.have.length.greaterThan(1);
      expect(getters.token).to.not.be.null;
      expect(getters.userPermissions).to.have.length.greaterThan(1);
      expect(getters.userRole).to.be.a("object");
      expect(getters.verified).to.not.be.null;
    });
  });
});
