describe("the item edit page", () => {
  const item = {
    title: "Cypress Item",
    description: "Added description...",
    price: 20,
    cost: 10,
    classification: null,
    condition: null
  };

  before(() => {
    cy.login();

    cy.get("[data-test=scanUpc-input]").should("be.visible");
    cy.createSpecData("item_edit");
  });

  beforeEach(() => {
    cy.visit("/items");
    cy.routeEquals("/items");
    cy.selectItemFromItemsTable();
  });

  after(() => {
    cy.resetDB();
  });

  it("can route to edit and back to index", function() {
    cy.get("[data-test=goBack-button]").click();
    cy.routeEquals("/items/");
  });

  it("can print extra labels", function() {
    cy.get("[data-test=printExtraLabels-button]").click();

    cy.selectPrinters(true);

    cy.get("[data-test=printExtraLabels-button]").click();

    cy.get("[data-test=print-exta-labels-modal]").should("be.visible");
    cy.get("[data-test=printLabels-button]").should("be.disabled");

    cy.getVuexStore().then(store => {
      cy.get("[data-test=print-exta-labels-modal-store-select]").select(
        store.getters.stores[0].name
      );
    });

    cy.get("[data-test=printLabels-button]").click();
  });

  it("can reset item", function() {
    cy.fillEditItem(item);

    cy.get("[data-test=itemReset-button]").click();

    cy.getItemForm("ItemsEdit").should(itemForm => {
      expect(itemForm.isDirty).to.be.false;
    });
  });

  it("can save edited item", function() {
    cy.fillEditItem(item);

    cy.getItemForm("ItemsEdit").should(itemForm => {
      expect(itemForm.isDirty).to.be.true;
      expect(itemForm.title).to.equal(item.title);
      expect(itemForm.description).to.eq(item.description);
      expect(itemForm.price).to.equal(item.price * 100);
      expect(itemForm.cost).to.equal(item.cost * 100);
      expect(itemForm.classification_id).to.eq(item.classification);
      expect(itemForm.condition_id).to.eq(item.condition);
    });

    cy.get("[data-test=saveItem-button]").click();
    cy.get("[data-test=labels-confirmation-printLabels-button").click();

    cy.checkToastedMessage(`Item Updated: "${item.title}".`);
    cy.routeEquals("/items/");
  });

  it("can open and close quantity transfer modal", function() {
    cy.get("[data-test=quantityTransfer-button]").click();

    cy.get("[data-test=quantityTransferModal]").should("be.visible");

    cy.get("[data-test=closeTransferModal-button]").click();

    cy.get("[data-test=quantityTransferModal]").should("not.exist");
  });

  it("can create a quantity transfer", function() {
    cy.get("[data-test=quantityTransfer-button]").click();

    cy.get("[data-test=sendingStores]")
      .children()
      .its("length")
      .should("eq", 1);

    cy.get("[data-test=sendingStore_0]").click();

    cy.get("[data-test=bodyStep-1]").should("be.visible");
    cy.get("[data-test=availableQuantity]").should("contain.text", "1");

    cy.get("[data-test=increaseTransferAmount-button]").click();

    cy.get("[data-test=quantityTransferAdjustment-input]").should(
      "contain.value",
      "1"
    );

    cy.get("[data-test=modalGoBack-button]").click();

    cy.get("[data-test=bodyStep-0]").should("be.visible");

    cy.get("[data-test=sendingStore_0]").click();

    cy.get("[data-test=quantityTransferAdjustment-input]").should(
      "contain.value",
      "0"
    );
    cy.get("[data-test=decreaseTransferAmount-button]").should("not.exist");

    cy.get("[data-test=quantityTransferAdjustment-input]").type(
      "{backspace}-1"
    );

    cy.checkToastedMessage("Cannot transfer a negative amount.", "info");

    cy.get("[data-test=increaseTransferAmount-button]").click();
    cy.get("[data-test=increaseTransferAmount-button]").click();

    cy.checkToastedMessage("The max available for transfer is 1.", "info");

    cy.get("[data-test=continue-button]").click();

    cy.get("[data-test=bodyStep-2]").should("be.visible");
    cy.get("[data-test=receivingStores]")
      .children()
      .its("length")
      .should("eq", 2);

    cy.get("[data-test=receivingStore_0]").click();

    cy.get("[data-test=bodyStep-3]").should("be.visible");
    cy.get("[data-test=quantityToTransfer]").should("contain.text", "1");
    cy.get("[data-test=sendingStoreName]").should("contain.text", "Unit Falls");
    cy.get("[data-test=receivingStoreName]").should("contain.text", "Testopia");

    cy.get("[data-test=confirmTransfer-button]").click();

    cy.checkToastedMessage("Quantity transfer successful.");
  });

  it("quantity log shows correct data", function() {
    cy.get("[data-test=itemHistory-button]").click();

    cy.get("[data-test=quantity-log-tables]").should("be.visible");

    cy.get("[data-test=quantity-log-table-0-body]")
      .children()
      .its("length")
      .should("eq", 3);
    cy.get("[data-test=quantity-log-table-1-body]")
      .children()
      .its("length")
      .should("eq", 2);

    cy.get("[data-test=modalClose-button]").click();
  });

  it("can delete item", function() {
    cy.get("[data-test=itemDelete-button").click();

    cy.get("[data-test=confirm-delete-modal]").should("be.visible");

    cy.get("[data-test=cancelDelete-button").click();

    cy.get("[data-test=confirm-delete-modal]").should("not.exist");

    cy.get("[data-test=itemDelete-button").click();
    cy.intercept("/api/items/delete/*").as("deleteItem");
    cy.get("[data-test=confirmDelete-button").click();

    cy.wait("@deleteItem").then(interception => {
      const response = interception.response;

      expect(response.statusCode).to.equal(200);
      cy.checkToastedMessage("Item Successfully Deleted.");
    });
  });
});
