Cypress.Commands.add("testPosStandBy", function(shouldBeVisible = true) {
  if (shouldBeVisible) {
    cy.get("[data-test=pos-standby]").should("be.visible");
    cy.get("[data-test=pos-daily-sales-tile]").should("be.visible");
    cy.get("[data-test=pos-daily-returns-tile]").should("be.visible");
    cy.get("[data-test=pos-idle-tile]").should("be.visible");
  } else {
    cy.get("[data-test=pos-standby]").should("not.exist");
    cy.get("[data-test=pos-daily-sales-tile]").should("not.exist");
    cy.get("[data-test=pos-daily-returns-tile]").should("not.exist");
    cy.get("[data-test=pos-idle-tile]").should("not.exist");
  }
});

Cypress.Commands.add("testPosOrderView", function(shouldBeVisible = true) {
  if (shouldBeVisible) {
    cy.get("[data-test=pos-item-table]").should("be.visible");
    cy.get("[data-test=pos-right]").should("be.visible");
    cy.get("[data-test=pos-bottom]").should("be.visible");
  } else {
    cy.get("[data-test=pos-item-table]").should("not.exist");
    cy.get("[data-test=pos-right]").should("not.exist");
    cy.get("[data-test=pos-bottom]").should("not.exist");
  }
});

Cypress.Commands.add("assertItemsTableLength", function(expectedLength) {
  cy.get("[data-test=pos-item-table-items]")
    .children()
    .its("length")
    .should("eq", expectedLength);
});

Cypress.Commands.add("totalsWereCalculated", function() {
  cy.wait("@orderTotals").then(({ response }) => {
    expect(response.statusCode).to.equal(200);

    const orderMath = response.body.data;

    cy.get("[data-test=pos-bottom-subTotal-display]").should(
      "contain.text",
      `$${(orderMath.sub_total / 100).toFixed(2)}`
    );
    cy.get("[data-test=pos-bottom-tax-display]").should(
      "contain.text",
      `$${(orderMath.tax / 100).toFixed(2)}`
    );
    cy.get("[data-test=pos-bottom-total-display]").should(
      "contain.text",
      `$${(orderMath.total / 100).toFixed(2)}`
    );
  });
});

Cypress.Commands.add("addScratchItemToOrder", function(
  classificationId = null
) {
  cy.get("[data-test=pos-addScratchItem-button]").click();
  cy.fillScratchItem(classificationId);
});

Cypress.Commands.add("fillScratchItem", function(classificationId = null) {
  cy.getVuexStore().then(({ getters }) => {
    if (!classificationId) {
      classificationId = getters.classificationsVisible[0].id;
    }

    cy.get("[data-test=pos-right-itemPrice-input]").type(
      "{selectall}{backspace}10"
    );
    cy.get(
      `[data-test=Classification-custom-select-option-${classificationId}`
    ).click();
  });
});
