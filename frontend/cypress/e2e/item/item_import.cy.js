describe("importing items into inventory", function() {
  before(() => {
    cy.login();

    cy.get("[data-test=scanUpc-input]").should("be.visible");

    cy.visit("/items");
    cy.routeEquals("/items");
  });

  after(() => {
    cy.resetDB();
  });

  it("can upload inventory", function() {
    cy.get("[data-test=item-import]").should("be.visible");
    cy.get("[data-test=item-import]").click();

    cy.get("[data-test=itemUploadModal]").should("be.visible");
    cy.get("[data-test=file-required]").should("be.visible");

    cy.get("[data-test=file-input]").selectFile(
      "./cypress/fixtures/Test_Inventory_Upload.xlsx"
    );

    cy.get("[data-test=file-required]").should("not.exist");

    cy.intercept("/api/items/import").as("uploadItems");
    cy.get("[data-test=upload-file]").click();

    cy.wait("@uploadItems")
      .its("response.statusCode")
      .should("eq", 200);

    cy.checkToastedMessage(
      'Inventory file was successfully processed, the items should appear under the "Items" tab.',
      "success",
      20000
    );

    cy.reload();
  });

  it("has items from the file upload", function() {
    cy.get("[data-test=items-table-items]")
      .children()
      .its("length")
      .should("eq", 9);
    cy.get("[data-test=items-table-item-0]")
      .children("[data-test=no-item-image]")
      .should("not.exist");

    cy.get("[data-test=items-table-item-7]").click();

    cy.get("[data-test=itemFormCondition-select]").should(
      "contain.text",
      "Like New"
    );
    cy.get("[data-test=itemFormClassification-select]").should(
      "contain.text",
      "Appliances"
    );
  });
});
