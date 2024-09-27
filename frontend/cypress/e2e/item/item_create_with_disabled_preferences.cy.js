describe("item creation without classification or condition", () => {
  before(() => {
    cy.login();
    cy.routeEquals("/scan");
  });

  beforeEach(() => {
    cy.intercept("api/preferences/update").as("updatePreferences");
    cy.visit("/settings/stores");
  });

  after(() => {
    cy.resetDB();
  });

  it("can create an item without classification", function() {
    cy.get("[data-test=disableClassifications-input]").click();

    cy.wait("@updatePreferences")
      .its("response.statusCode")
      .should("eq", 200);
    cy.visit("/scan");

    cy.get("[data-test=addFromScratch-button]").click();

    cy.get("[data-test=Classification-custom-select]").should("not.exist");

    cy.getScratchData().then(item => {
      cy.fillItemForm(item);

      // Create quantity
      cy.get("[data-test=storeQuantity-0]").click();
      cy.increaseQuantityAmount(1);
      cy.get("[data-test=returnToItem-button]").click();

      cy.get("[data-test=saveItem-button]").should("be.disabled");

      cy.window()
        .its("ItemsCreate")
        .then(({ displayConditions }) => {
          // Select Condition
          cy.get(
            `[data-test=Condition-custom-select-option-${displayConditions[0].id}]`
          ).click();
          item.condition = displayConditions[0].id;
        });
    });

    cy.saveItemWithoutLabels();
  });

  it("can create an item without condition", function() {
    cy.get("[data-test=disableClassifications-input]").click();

    cy.wait("@updatePreferences")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=disableConditions-input]").click();

    cy.wait("@updatePreferences")
      .its("response.statusCode")
      .should("eq", 200);
    cy.visit("/scan");

    cy.get("[data-test=addFromScratch-button]").click();

    cy.get("[data-test=Condition-custom-select]").should("not.exist");

    cy.getScratchData().then(item => {
      cy.fillItemForm(item);

      // Create quantity
      cy.get("[data-test=storeQuantity-0]").click();
      cy.increaseQuantityAmount(1);
      cy.get("[data-test=returnToItem-button]").click();

      cy.get("[data-test=saveItem-button]").should("be.disabled");

      cy.window()
        .its("ItemsCreate")
        .then(({ displayClassifications }) => {
          // Select Classification
          cy.get(
            `[data-test=Classification-custom-select-option-${displayClassifications[0].id}]`
          ).click();
          item.classification = displayClassifications[0].id;
        });
    });

    cy.saveItemWithoutLabels();
  });

  it("can create an item without classification & condition", function() {
    cy.get("[data-test=disableClassifications-input]").click();

    cy.wait("@updatePreferences")
      .its("response.statusCode")
      .should("eq", 200);
    cy.visit("/scan");

    cy.get("[data-test=addFromScratch-button]").click();

    cy.get("[data-test=Classification-custom-select]").should("not.exist");
    cy.get("[data-test=Condition-custom-select]").should("not.exist");

    cy.getScratchData().then(item => {
      cy.fillItemForm(item);

      // Create quantity
      cy.get("[data-test=storeQuantity-0]").click();
      cy.increaseQuantityAmount(1);
      cy.get("[data-test=returnToItem-button]").click();
    });

    cy.saveItemWithoutLabels();
  });
});
