describe("the printer settings panel", () => {
  before(() => {
    cy.login();
  });

  it("displays when clicked", function() {
    cy.get("[data-test=printSettingsModal]").should("not.be.visible");
    cy.get("[data-test=printSettings-toolbar]").click();
    cy.get("[data-test=printSettingsModal]").should("be.visible");
    cy.get("[data-test=closePrintSettingsModal-button]").should(
      "have.attr",
      "disabled"
    );

    cy.get("[data-test=closeModal-button]").click();

    cy.get("[data-test=printSettingsModal]").should("not.be.visible");
  });

  it("selects a label & receipt printer", function() {
    cy.get("[data-test=printSettings-toolbar]").click();
    cy.selectPrinters(true, true);

    cy.get("[data-test=printSettingsModal]").should("not.be.visible");
  });
});
