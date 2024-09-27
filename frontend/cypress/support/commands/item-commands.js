Cypress.Commands.add("fillEditItem", function(item) {
  // initial assertions
  cy.get("[data-test=printExtraLabels-button]").should("be.visible");
  cy.get("[data-test=itemHistory-button]").should("be.visible");
  cy.get("[data-test=itemDelete-button]").should("be.visible");
  cy.get("[data-test=itemOffer-div]").should("be.visible");
  cy.get("[data-test=itemUpc-div]").should("be.visible");
  cy.get("[data-test=item-images]").should("be.visible");
  cy.get("[data-test=goBack-button]").should("be.visible");
  cy.get("[data-test=itemReset-button]").should("be.disabled");
  cy.get("[data-test=quantityReceived-indicator]").should("not.exist");
  cy.get("[data-test=saveItem-button]")
    .as("saveItem-button")
    .should("be.disabled");

  // fill form with new data
  cy.get("[data-test=itemFormTitle-input]").type(
    `{selectall}{backspace}${item.title}`
  );
  cy.get("[data-test=itemFormPrice-input]").type(
    `{selectall}{backspace}${item.price}`
  );
  cy.get("[data-test=itemFormCost-input]").type(
    `{selectall}{backspace}${item.cost}`
  );
  cy.get("[data-test=itemFormDescription-input]").type(
    `{selectall}{backspace}${item.description}`
  );

  cy.getVuexStore().then(store => {
    const classification = store.getters.classificationsVisible[1].id;
    const condition = store.getters.conditionsVisible[1].id;
    item.classification = classification;
    item.condition = condition;

    cy.get("[data-test=itemFormClassification-select]").select(
      classification.toString()
    );
    cy.get("[data-test=itemFormCondition-select]").select(condition.toString());
  });

  cy.get("[data-test=storeQuantity-0]").click();
  cy.decreaseQuantityAmount(2);

  cy.checkToastedMessage(
    "We do not allow for the creation of negative quantities.",
    "error"
  );

  cy.get("[data-test=quantityMessage-input]").type(
    "This is a test quantity removal :)"
  );
  cy.get("[data-test=returnToItem-button]").click();
  cy.assertQuantitiesLength(1);

  cy.get("[data-test=storeQuantity-1]").click();
  cy.increaseQuantityAmount();

  cy.get("[data-test=returnToItem-button]").click();
  cy.assertQuantitiesLength(2);

  cy.get("[data-test=saveItem-button]").should("not.disabled");
  cy.get("[data-test=itemReset-button]").should("not.disabled");

  cy.getItemForm("ItemsEdit").should(itemForm => {
    expect(itemForm.isDirty).to.be.true;
  });
});

Cypress.Commands.add("fillItemCreateAside", function() {
  cy.window()
    .its("ItemsCreate")
    .then(({ displayClassifications, displayConditions }) => {
      // Select Classification & Condition
      cy.get(
        `[data-test=Classification-custom-select-option-${displayClassifications[0].id}]`
      ).click();
      cy.get(
        `[data-test=Condition-custom-select-option-${displayConditions[0].id}]`
      ).click();
    });

  // Create quantity
  cy.get("[data-test=storeQuantity-1]").click();

  cy.increaseQuantityAmount(3);
  cy.assertQuantitiesLength(1);
  cy.decreaseQuantityAmount();

  cy.get("[data-test=returnToItem-button]").click();
});

Cypress.Commands.add("getItemForm", function(itemPage = "ItemsCreate") {
  return cy
    .window()
    .its(itemPage)
    .its("itemForm");
});

Cypress.Commands.add("getUpcData", function(upc = "698813004362") {
  cy.get("[data-test=scanUpc-input]").type(upc);
  cy.get("[data-test=addFromUpc-button]").click();

  cy.intercept("/api/items/query/upc-data").as("getUpcData");
  cy.wait("@getUpcData", { timeout: 30000 });
});

Cypress.Commands.add("increaseQuantityAmount", function(times = 1) {
  for (let i = 0; i < times; i++) {
    cy.get("[data-test=increaseAmount-button]").click();
  }
});

Cypress.Commands.add("decreaseQuantityAmount", function(times = 1) {
  for (let i = 0; i < times; i++) {
    cy.get("[data-test=decreaseAmount-button]").click();
  }
});

Cypress.Commands.add("assertQuantitiesLength", function(expectedLength) {
  cy.window()
    .its("StoreQuantities")
    .then(data => {
      expect(data.quantities.length).to.equal(expectedLength);
    });
});

Cypress.Commands.add("uploadItemImage", function() {
  cy.getContents("item_test_image.jpg").then(dataTransfer => {
    cy.get("[data-test=item-images-upload-div]").trigger("drop", {
      dataTransfer
    });
  });

  cy.intercept("/api/images/upload").as("imageUpload");
  cy.wait("@imageUpload")
    .its("response.statusCode")
    .should("eq", 200);
});

Cypress.Commands.add("assertRemovedImages", function(amountRemoved) {
  cy.window()
    .its("ItemImages")
    .then(data => {
      expect(data.selectedImages.length).to.equal(
        data.images.length - amountRemoved
      );
    });
});

Cypress.Commands.add("saveItemWithoutLabels", function(rememberAnswer = false) {
  cy.getVuexStore().then(store => {
    cy.get("[data-test=saveItem-button]").click();

    if (store.getters.wantsLabelsConfirmation) {
      if (rememberAnswer) {
        cy.clickDontAskAgain();
      }

      cy.get("[data-test=labels-confirmation-noLabels-button").click();
    } else {
      cy.get("[data-test=labels-confirmation]").should("not.exist");
    }
  });
});

Cypress.Commands.add("saveItemWithLabels", function(rememberAnswer = false) {
  cy.getVuexStore().then(store => {
    cy.get("[data-test=saveItem-button]").click();

    if (store.getters.wantsLabelsConfirmation) {
      if (rememberAnswer) {
        cy.clickDontAskAgain(false);
      }

      cy.get("[data-test=labels-confirmation-printLabels-button").click();

      if (!store.getters.qzLabelPrinter) {
        cy.selectPrinters(true, false);
        cy.saveItemWithLabels();
        return;
      }
    } else {
      cy.get("[data-test=labels-confirmation]").should("not.exist");
    }
  });
});

Cypress.Commands.add("clickDontAskAgain", function(expectedBool = true) {
  cy.get("[data-test=labels-confirmation-dontAskAgain").click();

  cy.getVuexStore().then(store => {
    expect(store.getters.wantsLabelsConfirmation).to.equal(expectedBool);
  });
});

Cypress.Commands.add("getScratchData", function() {
  let item = {
    value: Math.floor(5000 + Math.random() * 15000),
    quantity: Math.floor(Math.random() * 6) + 2,
    store: null,
    classification: null,
    condition: null
  };

  item.title = `Test Item #${item.value}`;
  item.price = Number(item.value * 0.001).toFixed(2);
  item.cost = Number(item.price - 0.99).toFixed(2);
  item.description = `A description of ${item.title}.`;

  return item;
});

Cypress.Commands.add("fillItemForm", function({
  title = null,
  price = null,
  cost = null,
  description = null
}) {
  if (title) {
    cy.get("[data-test=itemFormTitle-input]").type(title);
  }
  if (price) {
    cy.get("[data-test=itemFormOriginalPrice-input]").type(price);
  }
  if (cost) {
    cy.get("[data-test=itemFormCost-input]").type(cost);
  }
  if (description) {
    cy.get("[data-test=itemFormDescription-input]").type(description);
  }
});

Cypress.Commands.add("selectItemFromItemsTable", function() {
  cy.get("[data-test=items-table]").should("be.visible");

  cy.intercept("GET", "/api/items/*").as("getItem");
  cy.get("[data-test=items-table-item-0]").click();

  cy.wait("@getItem").then(interception => {
    const body = interception.response.body;

    cy.routeEquals(`/items/${body.data.item.id}/edit`);
  });
});
