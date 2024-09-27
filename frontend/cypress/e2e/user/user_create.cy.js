describe("Tests user create page", () => {
  const deleteTestUser = () => cy.createSpecData("user_create");

  before(() => {
    cy.visit("/create");
    deleteTestUser();
  });

  after(() => {
    deleteTestUser();
  });

  it("can fill new user form", function() {
    cy.get("[data-test=user-create-form]").should("be.visible");
    cy.get("[data-test=user-create-firstName-indicator]").should("be.visible");
    cy.get("[data-test=user-create-lastName-indicator]").should("be.visible");
    cy.get("[data-test=user-create-email-indicator]").should("be.visible");
    cy.get("[data-test=user-create-username-indicator]").should("be.visible");
    cy.get("[data-test=user-create-password-indicator]").should("be.visible");
    cy.get("[data-test=user-create-button]").should("be.disabled");

    cy.get("[data-test=user-create-firstName-input]").type("Sam");
    cy.get("[data-test=user-create-lastName-input]").type("Walton");
    cy.get("[data-test=user-create-email-input]").type("swalton@test.com");
    cy.get("[data-test=user-create-password-input]").type("Test1234");

    cy.get("[data-test=user-create-passwordDoesntMatch-indicator]").should(
      "be.visible"
    );

    cy.get("[data-test=user-create-passwordConfirm-input]").type("Test1234");

    cy.get("[data-test=user-create-passwordDoesntMatch-indicator]").should(
      "not.exist"
    );
    cy.get("[data-test=user-create-button]").should("not.be.disabled");
  });

  it("can fill out questions", function() {
    cy.get("[data-test=user-feedback-0]")
      .find("input#feature-0")
      .click();
    cy.get("[data-test=user-feedback-0]")
      .find("input#feature-1")
      .click();
    cy.get("[data-test=user-feedback-1]")
      .find("input")
      .type("Facebook");
    cy.get("[data-test=user-feedback-2]")
      .find("input")
      .type("Shopify, Shipstation");
    cy.get("[data-test=user-feedback-3]")
      .find("select")
      .select("250K-1M");

    cy.window()
      .its("UserCreate")
      .then(({ user }) => {
        expect(user.feedback.length).to.equal(4);
      });
  });

  it("can create account with free trial", function() {
    cy.intercept("/api/users/create").as("createUser");
    cy.intercept("/api/stripe/subscription-plans").as("getPlans");
    cy.get("[data-test=user-create-button]").click();

    cy.wait("@createUser")
      .its("response.statusCode")
      .should("eq", 200);
    cy.wait("@getPlans")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=subscription-plans]").should("be.visible");

    cy.get("[data-test=subscription-plans-selectPlan-button]").click();

    cy.checkToastedMessage(
      "Congratulations! Your account has been created and your 14 day free trial has started."
    );
    cy.routeEquals("/login");

    cy.getVuexStore().then(({ dispatch }) => {
      dispatch("logout");
      cy.visit("/create");
    });
  });

  it("can create account with a paid subscription", function() {
    deleteTestUser();

    cy.get("[data-test=user-create-firstName-input]").type("Sam");
    cy.get("[data-test=user-create-lastName-input]").type("Walton");
    cy.get("[data-test=user-create-email-input]").type("swalton@test.com");
    cy.get("[data-test=user-create-password-input]").type("Test1234");
    cy.get("[data-test=user-create-passwordConfirm-input]").type("Test1234");

    cy.intercept("/api/users/create").as("createUser");
    cy.intercept("/api/stripe/subscription-plans").as("getPlans");
    cy.get("[data-test=user-create-button]").click();

    cy.wait("@createUser")
      .its("response.statusCode")
      .should("eq", 200);
    cy.wait("@getPlans")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=subscription-plans]").should("be.visible");

    cy.get("[data-test=subscription-plans-0]").click();
    cy.get("[data-test=subscription-plans-selectPlan-button]").click();

    cy.get("[data-test=payment-method-modal]").should("be.visible");

    cy.get("[data-test=payment-method-modal-cancel-button]").click();

    cy.get("[data-test=payment-method-modal]").should("not.be.visible");

    cy.get("[data-test=subscription-plans-selectPlan-button]").click();
    cy.get("[data-test=payment-method-modal-name-input]").type("Sam Walton");
    // BUG: cant enter card number
    // cy.get('iframe:first').iframe().then(iframes => {
    //   cy.wrap(iframes[0])
    //     .find(".InputElement")
    //     .first()
    //     .type("424242424242032912385374");
    // });

    cy.intercept("/api/stripe/update-payment-method").as("updatePaymentMethod");
    cy.intercept("/api/stripe/change-plan").as("selectPlan");
    cy.get(
      "[data-test=payment-method-modal-updatePaymentMethod-button]"
    ).click();

    cy.checkToastedMessage("Validating card...");
    cy.checkToastedMessage("Payment method updated successfully!");
    cy.checkToastedMessage("Subscribing to plan...");
    cy.checkToastedMessage(
      "Congratulations! Your account has been created and your 14 day free trial has started."
    );
    cy.get("[data-test=subscription-plans-0]").then(({ text }) => {
      cy.checkToastedMessage(
        `Subscription plan successfully set plan to ${text}!`
      );
    });
  });
});
