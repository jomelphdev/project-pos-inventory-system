describe("the item create page", () => {
  before(() => {
    cy.login();
    cy.routeEquals("/scan");

    // Makes sure the tested UPC has no existing data
    cy.getVuexStore().then(store => {
      store.dispatch("queryItems", { query: "698813004362" });

      cy.intercept("api/items/query").as("itemUpcQuery");
      cy.wait("@itemUpcQuery").then(intereception => {
        const body = intereception.response.body;
        const items = body.data.items;

        if (items.length > 0) {
          for (let item of items) {
            store.dispatch("deleteItem", item.id);
          }
        }
      });
    });
  });

  beforeEach(() => {
    cy.visit("/scan");
    cy.url({ timeout: 10000 }).should("contain", "scan");
  });

  after(() => {
    cy.resetDB();
  });

  /**
   * TODO:
   * - Manifest item. (come back to this)
   */

  it("can start and go back", function() {
    // navigate to create
    cy.get("[data-test=addFromScratch-button]").click();

    // navigate to scan
    cy.url({ timeout: 10000 }).should("contain", "items");
    cy.get("[data-test=goBack-button]").click();
  });

  it("creates an item from scratch without label", function() {
    // navigate from scan
    cy.url({ timeout: 10000 }).should("contain", "scan");
    cy.get("[data-test=addFromScratch-button]").click();

    // Check initial state
    cy.get("[data-test=item-images]").should("be.visible");
    cy.get("[data-test=goBack-button]").should("be.visible");
    cy.get("[data-test=itemReset-button]").should("be.disabled");
    cy.get("[data-test=quantityReceived-indicator]").should("not.exist");
    cy.get("[data-test=selectLabelPrinter-button]").should("be.visible");
    cy.get("[data-test=itemFormPrice-input]").should("not.exist");
    cy.get("[data-test=saveItem-button]").should("be.disabled");

    cy.getScratchData().then(item => {
      // Fill form
      cy.fillItemForm(item);

      cy.window()
        .its("ItemsCreate")
        .then(({ displayClassifications, displayConditions }) => {
          // Select Classification & Condition
          cy.testCustomSelect(displayClassifications, "Classification", [
            "[data-test=Condition-custom-select]",
            "[data-test=storeQuantities]"
          ]);
          cy.testCustomSelect(displayConditions, "Condition", [
            "[data-test=Classification-custom-select]",
            "[data-test=storeQuantities]"
          ]);

          item.classification = displayClassifications[0].id;
          item.condition = displayConditions[0].id;
        });

      // Create quantities
      cy.get("[data-test=storeQuantity-0]").click();

      cy.increaseQuantityAmount(4);
      cy.assertQuantitiesLength(1);
      cy.decreaseQuantityAmount();

      cy.get("[data-test=returnToItem-button]").click();

      cy.get("[data-test=storeQuantity-1]").click();

      cy.decreaseQuantityAmount();
      cy.checkToastedMessage(
        "We do not allow for the creation of negative quantities.",
        "error"
      );

      cy.increaseQuantityAmount();
      cy.assertQuantitiesLength(2);
      cy.decreaseQuantityAmount();
      cy.assertQuantitiesLength(1);
      cy.increaseQuantityAmount();
      cy.assertQuantitiesLength(2);

      cy.get("[data-test=returnToItem-button]").click();

      item.amountOfQuantities = 2;
      item.quantity = 4;

      // Valid form state.
      cy.get("[data-test=quantityReceived-indicator]").should("be.visible");
      cy.get("[data-test=itemReset-button]").should("be.visible");
      cy.get("[data-test=itemFormPrice-input]").should("be.visible");

      cy.getItemForm().should($i => {
        // Validate item
        const originalPrice = item.original_price
          ? item.original_price
          : item.price * 100;

        expect($i.title).to.equal(item.title);
        expect(Number($i.original_price).toFixed(2)).to.equal(
          Number(originalPrice).toFixed(2)
        );
        expect(Number($i.cost).toFixed(2)).to.equal(
          Number(item.cost * 100).toFixed(2)
        );
        expect($i.description).to.equal(item.description);
        expect($i.classification_id).to.equal(item.classification);
        expect($i.condition_id).to.equal(item.condition);
        expect($i.quantities.length).to.equal(item.amountOfQuantities);

        let totalQty = 0;
        for (let quantity of $i.quantities) {
          totalQty += quantity.quantity_received;
        }

        expect(totalQty).to.equal(item.quantity);
      });

      cy.saveItemWithoutLabels();
      cy.checkToastedMessage(`Item Created: "${item.title}"`);
      cy.url().should("contain", "scan");
    });
  });

  it("can route to edit from similar title", function() {
    cy.get("[data-test=addFromScratch-button]").click();
    cy.get("[data-test=itemFormTitle-input]").type("Test Item");
    cy.get("[data-test=searchViaTitle-input]").click();
    cy.get("[data-test=similar-titles]").click();

    cy.get("[data-test=title-items-modal-items]")
      .children()
      .its("length")
      .should("eq", 1);

    cy.get("[data-test=title-items-modal-title-item-0]").trigger("mouseover");
    cy.intercept("/api/items/*").as("editItem");
    cy.get("[data-test=editItem-button]").click();

    cy.wait("@editItem").then(({ response }) => {
      const body = response.body;
      const itemId = body.data.item.id;

      cy.routeEquals(`/items/${itemId}/edit`);
    });
  });

  it("can create variant from similar title", function() {
    cy.get("[data-test=addFromScratch-button]").click();
    cy.get("[data-test=itemFormTitle-input]").type("Test Item");
    cy.get("[data-test=searchViaTitle-input]").click();
    cy.get("[data-test=similar-titles]").click();

    cy.get("[data-test=title-items-modal-items]")
      .children()
      .its("length")
      .should("eq", 1);

    cy.get("[data-test=title-items-modal-title-item-0]").trigger("mouseover");
    cy.intercept("/api/items/*").as("fetchItem");
    cy.intercept("/api/items/query/count").as("titleCount");
    cy.get("[data-test=createTitleVariant-button]").click();

    cy.wait("@fetchItem").then(({ response }) => {
      const body = response.body;
      const item = body.data.item;

      cy.window()
        .its("ItemsCreate")
        .then(({ itemForm }) => {
          expect(itemForm.title).to.equal(item.title);
          expect(itemForm.classification_id).to.equal(item.classification_id);
          cy.get(
            `[data-test=Condition-custom-select-options-${item.condition_id}]`
          ).should("not.exist");
          cy.get("[data-test=offers-list]")
            .children()
            .its("length")
            .should("eq", 1);
        });
    });

    cy.wait("@titleCount")
      .its("response.statusCode")
      .should("eq", 200);
  });

  it("creates an item from scratch with label and remembers", function() {
    cy.get("[data-test=addFromScratch-button]").click();

    cy.getScratchData().then(item => {
      cy.fillItemForm(item);
      cy.fillItemCreateAside();
    });

    cy.saveItemWithLabels(true);
    cy.url().should("contain", "scan");

    cy.getVuexStore().then(store => {
      store.dispatch("setWantsLabelsConfirmation", true);
    });
  });

  it("creates an item from upc data with label and checks that listed before routes to edit", function() {
    cy.url({ timeout: 10000 }).should("contain", "scan");
    cy.getUpcData("698813004362");

    cy.get("[data-test=itemReset-button]").should("be.visible");

    // Test images
    // removed 2
    cy.get("[data-test=item-image-0]").click();
    cy.get("[data-test=item-image-1]").click();

    cy.assertRemovedImages(2);

    // selected 1 back
    cy.get("[data-test=item-image-1]").click();

    cy.assertRemovedImages(1);

    cy.uploadItemImage();
    cy.assertRemovedImages(0);

    cy.get("[data-test=offer-option-0]").click();

    cy.fillItemCreateAside();

    // Create UPC Item
    cy.intercept("/api/items/create").as("itemCreated");
    cy.saveItemWithLabels();
    cy.url().should("contain", "scan");

    cy.wait("@itemCreated").then(intereception => {
      const body = intereception.response.body;
      const itemId = body.data.item.id;

      // checks listed before modal routes correctly
      cy.getUpcData();

      cy.get("[data-test=listed-before-modal]").should("be.visible");
      cy.get("[data-test=listed-before-modal-item-0]").click();

      cy.url().should("contain", `items/${itemId}/edit`);

      // deletes the item we just created to avoid errors in future tests.
      cy.getVuexStore().then(store => {
        store.dispatch("deleteItem", itemId);

        cy.intercept("/api/items/delete/*").as("itemDeleted");
        cy.wait("@itemDeleted").then(intereception => {
          expect(intereception.response.body.success).to.be.true;
        });
      });
    });
  });

  it("can create a consignment item", function() {
    cy.get("[data-test=addFromScratch-button]").click();

    cy.get("[data-test=isConsignment-radio]").should("not.exist");

    cy.get("[data-test=goBack-button]").click();

    cy.getVuexStore().then(({ dispatch }) => {
      cy.intercept("/api/preferences").as("getPreferences");
      cy.intercept("/api/preferences/update/preference").as("createConsignor");

      dispatch("updatePreference", {
        type: "consignors",
        update: {
          name: "Test Consignor",
          consignment_fee_percentage: 10
        }
      });

      cy.wait("@createConsignor")
        .its("response.statusCode")
        .should("eq", 200);
      cy.wait("@getPreferences")
        .its("response.statusCode")
        .should("eq", 200);
    });

    cy.getVuexStore().then(({ getters }) => {
      cy.routeEquals("/scan");

      cy.get("[data-test=addFromScratch-button]").click();

      cy.get("[data-test=isConsignment-radio]").should("be.visible");
      cy.get("[data-test=consignor-select]").should("not.exist");
      cy.get("[data-test=consignmentFee-input]").should("not.exist");

      cy.getScratchData().then(item => {
        cy.fillItemForm(item);
        cy.fillItemCreateAside();
      });

      cy.get("[data-test=isConsignment-radio]").click();

      cy.get("[data-test=consignor-select]").should("be.visible");
      cy.get("[data-test=consignmentFee-input]").should("be.visible");

      cy.intercept("/api/items/calculate-consignment-fee*").as(
        "calculateConsignmentFee"
      );
      let consignor = getters.consignors[0];
      cy.get("[data-test=consignor-select]").select(consignor.name);

      cy.wait("@calculateConsignmentFee")
        .its("response.statusCode")
        .should("eq", 200);

      cy.intercept("/api/items/create").as("itemCreated");
      cy.saveItemWithoutLabels();

      cy.wait("@itemCreated").then(({ response }) => {
        expect(response.statusCode).to.equal(200);

        cy.visit(`/items/${response.body.data.item.id}/edit`);
        cy.routeEquals(`/items/${response.body.data.item.id}/edit`);

        cy.get("[data-test=consignor-fields]").should("be.visible");
        cy.get("[data-test=consignor]").should("contain.text", consignor.name);
      });
    });
  });
});
