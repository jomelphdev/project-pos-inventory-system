describe("POS order create page", () => {
  let skus = [];
  let store = null;

  before(() => {
    cy.login();

    cy.get("[data-test=scanUpc-input]").should("be.visible");

    cy.getVuexStore().then(({ getters }) => {
      store = getters.stores[0];

      cy.createSpecData("pos_create_order").then(data => {
        skus = data.skus;

        cy.visit("/pos/");
        cy.routeEquals("/pos/");
      });
    });
  });

  beforeEach(() => {
    cy.wrap(skus).as("skus");
    cy.wrap(store).as("selectedStore");

    cy.intercept("/api/orders/calculate-totals").as("orderTotals");
  });

  after(() => {
    cy.resetDB();
  });

  it("can select POS store", function() {
    cy.get("[data-test=store-select-modal]").should("be.visible");

    cy.getVuexStore().then(store => {
      cy.get(
        `[data-test=store-select-modal-store-${store.getters.stores[1].id}]`
      ).click();

      cy.get("[data-test=store-select-modal]").should("not.exist");
    });
  });

  it("can change POS store", function() {
    cy.get("[data-test=pos-changeStore-button]").click();

    cy.get(
      `[data-test=store-select-modal-store-${this.selectedStore.id}]`
    ).click();

    cy.get("[data-test=pos-search]").should("be.visible");
    cy.get("[data-test=pos-search-button]").should("be.visible");
    cy.get("[data-test=pos-addScratchItem-button]").should("be.visible");
    cy.testPosStandBy();
  });

  it("can start and go back", function() {
    cy.get("[data-test=pos-addScratchItem-button]").click();

    cy.testPosStandBy(false);
    cy.testPosOrderView();

    cy.get("[data-test=pos-start-over-button]").click();
    cy.testPosStandBy();
    cy.testPosOrderView(false);
  });

  it("can search UPC with only one result", function() {
    cy.intercept("/api/items/query/upc").as("orderItem");

    cy.get("[data-test=pos-search-input]").type("444333222111");

    cy.wait("@orderItem").then(({ response }) => {
      expect(response.statusCode).to.equal(200);
      expect(response.body.data.items.length).to.equal(1);
      cy.totalsWereCalculated();
      cy.testPosOrderView();
      cy.assertItemsTableLength(1);

      cy.get("[data-test=pos-start-over-button]").click();
    });
  });

  it("can search UPC with multiple result", function() {
    cy.intercept("/api/items/query/upc").as("orderItems");

    cy.get("[data-test=pos-search-input]").type("111222333444");

    cy.wait("@orderItems").then(({ response }) => {
      expect(response.statusCode).to.equal(200);
      expect(response.body.data.items.length).to.equal(2);

      cy.get("[data-test=upc-results-modal]").should("be.visible");
      cy.get("[data-test=upc-results-modal-items]")
        .children()
        .its("length")
        .should("eq", 2);

      cy.get("[data-test=upc-results-modal-item-1]").click();

      cy.totalsWereCalculated();
      cy.testPosOrderView();
      cy.assertItemsTableLength(1);

      cy.get("[data-test=pos-start-over-button]").click();
    });
  });

  it("can search sku", function() {
    cy.intercept("/api/items/query/sku").as("orderItem");

    cy.get("[data-test=pos-search-input]").type(this.skus[0]);
    cy.get("[data-test=pos-search-button]").click();

    cy.wait("@orderItem").then(({ response }) => {
      expect(response.statusCode).to.equal(200);
      cy.totalsWereCalculated();

      const item = response.body.data.item;

      cy.testPosOrderView();
      cy.assertItemsTableLength(1);

      // PosItemTable Assertions
      cy.get("[data-test=pos-item-table-item-0]").as("itemRow");

      cy.get("@itemRow")
        .find("[data-test=pos-item-row-title]")
        .should("contain.text", item.title);

      cy.getVuexStore().then(({ getters }) => {
        const classification = getters.classifications.find(
          c => c.id == item.classification_id
        );

        cy.get("@itemRow")
          .find("[data-test=pos-item-row-classification]")
          .should("contain.text", classification.name);
      });

      cy.get("@itemRow")
        .find("[data-test=pos-item-row-quantity]")
        .should("contain.text", 1);

      const priceTestValue = `$${(item.price / 100).toFixed(2)}`;
      cy.get("@itemRow")
        .find("[data-test=pos-item-row-price]")
        .should("contain.text", priceTestValue);

      cy.get("@itemRow")
        .find("[data-test=pos-item-row-total]")
        .should("contain.text", priceTestValue);

      // PosRight Assertions
      cy.get("[data-test=pos-right-itemTitle-input]").should("not.exist");
      cy.get("[data-test=pos-right-itemTitle]").should(
        "contain.text",
        item.title
      );
      cy.get("[data-test=pos-right-price-indicator]").should("not.exist");
      cy.get("[data-test=pos-right-itemPrice-input]").should(
        "have.value",
        priceTestValue
      );
      cy.get("[data-test=pos-right-quantityAvailable]").should(
        "contain.text",
        "Available: 1"
      );
      cy.get("[data-test=pos-right-itemQuantityOrdered-input").should(
        "have.value",
        1
      );
      cy.get("[data-test=Classification-custom-select]").should("not.exist");
      cy.get("[data-test=Discount-custom-select]").should("be.visible");
    });
  });

  it("can change price", function() {
    cy.intercept("/api/items/calculate-price").as("calculateItemPrice");

    cy.get("[data-test=pos-right-itemPrice-input]").as("itemPrice");
    cy.get("[data-test=pos-item-table-item-0]").as("itemRow");

    cy.get("@itemPrice").then(priceInput => {
      cy.wrap(priceInput.val()).as("originalPrice");
    });
    cy.get("@itemPrice").type("{selectall}{backspace}40");

    cy.wait("@calculateItemPrice").then(({ response }) => {
      expect(response.statusCode).to.equal(200);
      cy.totalsWereCalculated();

      cy.get("@itemRow")
        .find("[data-test=pos-item-row-price]")
        .should("contain.text", "$40.00");
      cy.get("@itemRow")
        .find("[data-test=pos-item-row-original-price]")
        .should("be.visible");
      cy.get("[data-test=pos-right-applyOriginalPrice-button]").should(
        "be.visible"
      );

      cy.get("[data-test=pos-right-applyOriginalPrice-button]").click();

      cy.get("@itemRow")
        .find("[data-test=pos-item-row-price]")
        .should("contain.text", this.originalPrice);
      cy.get("@itemRow")
        .find("[data-test=pos-item-row-original-price]")
        .should("not.exist");
      cy.get("[data-test=pos-right-applyOriginalPrice-button]").should(
        "not.exist"
      );
    });

    cy.get("@itemPrice").type("{selectall}{backspace}40");
  });

  it("can add quantity from POS Right", function() {
    cy.get("[data-test=pos-right-itemQuantityOrdered-input").type(
      "{selectall}{backspace}3"
    );

    cy.get("[data-test=quantityAdjustment-input]").should("have.value", 2);

    cy.decreaseQuantityAmount(3);
    cy.checkToastedMessage(
      "Negative quantities not allowed on this page.",
      "info"
    );
    cy.increaseQuantityAmount(2);

    cy.get("[data-test=returnToItem-button]").click();

    cy.get("[data-test=pos-right-quantityAvailable]").should(
      "contain.text",
      "Available: 3"
    );
    cy.get("[data-test=pos-right-itemQuantityOrdered-input]").should(
      "have.value",
      3
    );
    cy.totalsWereCalculated();
  });

  it("can add quantity from POS Search", function() {
    cy.intercept("/api/items/query/sku").as("orderItem");
    cy.get("[data-test=pos-search-input]").type(this.skus[1]);
    cy.wait("@orderItem");
    cy.get("[data-test=pos-search-input]").type(this.skus[1]);

    cy.assertItemsTableLength(2);

    cy.checkToastedMessage(
      `You do not have enough quantity to make this sale, add more quantity to this item and try again.`,
      "error"
    );

    cy.get("[data-test=quantityAdjustment-input]").should("have.value", 1);

    cy.get("[data-test=returnToItem-button]").click();

    cy.get("[data-test=pos-right-quantityAvailable]").should(
      "contain.text",
      "Available: 2"
    );
    cy.get("[data-test=pos-right-itemQuantityOrdered-input]").should(
      "have.value",
      2
    );
  });

  it("can discount item", function() {
    cy.getVuexStore().then(store => {
      const discount = store.getters.discountsVisible[0];
      cy.get("[data-test=pos-item-table-item-1]").as("itemRow");
      cy.get(
        `[data-test=Discount-custom-select-option-${discount.id}]`
      ).click();

      cy.totalsWereCalculated();
      cy.get("@itemRow")
        .find("[data-test=pos-item-row-temp-price]")
        .should("be.visible");

      cy.get("@itemRow")
        .find("[data-test=pos-item-row-price-discount-percentage]")
        .should("be.visible");
    });
  });

  it("can remove item from order", function() {
    cy.get("[data-test=pos-addScratchItem-button]").click();

    cy.assertItemsTableLength(3);

    cy.get("[data-test=pos-item-table-item-2]")
      .find("[data-test=pos-item-row-removeItem-button]")
      .click();

    cy.assertItemsTableLength(2);
  });

  it("can add scratch item", function() {
    cy.get("[data-test=pos-addScratchItem-button]").click();

    cy.get("[data-test=Classification-custom-select]").should("be.visible");

    cy.get("[data-test=pos-item-table-item-2]").as("itemRow");

    cy.get("[data-test=pos-right-price-indicator]").should("be.visible");
    cy.get("@itemRow")
      .find("[data-test=pos-item-row-price-indicator]")
      .should("be.visible");

    cy.get("[data-test=Classification-custom-select-indicator]").should(
      "be.visible"
    );
    cy.get("@itemRow")
      .find("[data-test=pos-item-row-classification-indicator]")
      .should("be.visible");

    cy.fillScratchItem();

    cy.get("[data-test=pos-right-price-indicator]").should("not.exist");
    cy.get("@itemRow")
      .find("[data-test=pos-item-row-price-indicator]")
      .should("not.exist");

    cy.get("[data-test=Classification-custom-select-indicator]").should(
      "not.exist"
    );
    cy.get("@itemRow")
      .find("[data-test=pos-item-row-classification-indicator]")
      .should("not.exist");

    cy.totalsWereCalculated();
  });

  it("can select item from order items list", function() {
    cy.get("[data-test=pos-item-table-item-2]").should(
      "have.class",
      "rr-table__tr--selected"
    );

    cy.get("[data-test=pos-item-table-item-1]").click();

    cy.get("[data-test=pos-item-table-item-1]").should(
      "have.class",
      "rr-table__tr--selected"
    );
  });

  it('checkout\'s "Go Back" works', function() {
    cy.get("[data-test=pos-bottom-cash-button]").click();

    cy.intercept("/api/orders/calculate-payment").as("calculatePayment");
    cy.get("[data-test=payment-input-cash]").type(`{selectall}{backspace}1`);

    cy.wait("@calculatePayment")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=payment-inputs]").should("be.visible");
    cy.get("[data-test=payment-input-cash]").should("contain.value", "1");

    cy.get("[data-test=pos-bottom-goBack-button]").click();

    cy.get("[data-test=payment-inputs]").should("not.exist");
  });

  it("can checkout using cash", function() {
    cy.intercept("/api/orders/calculate-payment").as("orderPayment");
    cy.get("[data-test=pos-bottom-cash-button]").click();

    cy.focused().should("have.attr", "id", "cashInput");
    cy.get("[data-test=payment-input-cash]").should("contain.value", "0");
    cy.get("[data-test=pos-bottom-amountRemaining]").should(
      "have.class",
      "text-red-700"
    );

    cy.window()
      .its("PosIndex")
      .then(({ order }) => {
        cy.get("[data-test=order-math-subTotal]").should(
          "contain.text",
          `$${(order.taxable_sub_total / 100).toFixed(2)}`
        );
        cy.get("[data-test=order-math-tax]").should(
          "contain.text",
          `$${(order.tax / 100).toFixed(2)}`
        );
        cy.get("[data-test=order-math-total]").should(
          "contain.text",
          `$${(order.total / 100).toFixed(2)}`
        );
        cy.get("[data-test=pos-bottom-amountRemaining]").should(
          "contain.text",
          `$${(order.total / 100).toFixed(2)}`
        );
      });

    cy.get("[data-test=payment-input-remainingToCash-button]").click();

    cy.wait("@orderPayment").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      const { amount_paid } = response.body.data;

      cy.get("[data-test=pos-bottom-cashPaid]").should(
        "contain.text",
        `$${(amount_paid / 100).toFixed(2)}`
      );
      cy.get("[data-test=pos-bottom-cardPaid]").should("contain.text", `$0.00`);
      cy.get("[data-test=pos-bottom-ebtPaid]").should("not.exist");
      cy.get("[data-test=pos-bottom-amountRemaining]").should(
        "contain.text",
        `$0.00`
      );
      cy.get("[data-test=pos-bottom-amountRemaining]").should(
        "have.class",
        "text-green-700"
      );
      cy.get("[data-test=pos-bottom-checkout-button]").should(
        "not.be.disabled"
      );
    });

    cy.get("[data-test=pos-bottom-checkout-button]").click();
    cy.selectPrinters(false, true);

    cy.intercept("/api/orders/create").as("createOrder");
    cy.get("[data-test=pos-bottom-checkout-button]").click();

    cy.checkToastedMessage("Creating order...");
    cy.wait("@createOrder")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=order-summary-modal-change-note]").should(
      "contain.text",
      "No Change Due"
    );

    cy.get("[data-test=order-summary-modal-newOrder-button]").click();
  });

  it("can discount entire order", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.addScratchItemToOrder();
      cy.addScratchItemToOrder();

      cy.get("[data-test=pos-editOrder-button]").click();

      cy.intercept("/api/order/calculate-totals").as("orderTotals");

      cy.get(
        `[data-test=Discount-custom-select-option-${getters.discountsVisible[0].id}`
      ).click();

      cy.wait("@orderTotals").then(({ response }) => {
        expect(response.statusCode).to.equal(200);

        cy.window()
          .its("PosIndex")
          .then(({ items }) => {
            for (let item of items) {
              if (!item.discount_id) {
                expect(item.discount_id).to.not.be.null;
              }
            }
          });
      });
    });
  });

  it("can apply no tax", function() {
    cy.get("[data-test=pos-right-orderNoTax-input]").click();
    cy.intercept("/api/orders/calculate-totals", req => {
      req.on("before:response", () => {
        expect(req.body.is_taxed).to.be.false;
      });

      req.on("response", res => {
        expect(res.statusCode).to.equal(200);
      });
    });
  });

  it("can re-apply tax", function() {
    cy.get("[data-test=pos-right-orderApplyTax-input]").click();
    cy.intercept("/api/orders/calculate-totals", req => {
      req.on("before:response", () => {
        expect(req.body.is_taxed).to.be.true;
      });

      req.on("response", res => {
        expect(res.statusCode).to.equal(200);
      });
    });
  });

  it("can checkout using card", function() {
    cy.get("[data-test=pos-bottom-card-button]").click();

    cy.focused().should("have.attr", "id", "cardInput");
    cy.get("[data-test=payment-input-card]").should("contain.value", "0");

    cy.intercept("/api/orders/calculate-payment").as("orderPayment");
    cy.get("[data-test=payment-input-remainingToCard-button]").click();

    cy.wait("@orderPayment").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      cy.get("[data-test=pos-bottom-cardPaid]").should(
        "contain.text",
        `$${(response.body.data.amount_paid / 100).toFixed(2)}`
      );
    });

    cy.get("[data-test=pos-bottom-checkout-button]").click();
    cy.get("[data-test=order-summary-modal-newOrder-button]").click();
  });

  it("cant checkout with EBT without EBT items", function() {
    cy.addScratchItemToOrder();
    cy.wait("@orderTotals")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=pos-bottom-ebt-button]").click();
    cy.checkToastedMessage(
      "This order is not eligible for EBT. Please try cash or card.",
      "error"
    );
  });

  it("can checkout with EBT and use multiple payment methods", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.intercept("/api/orders/calculate-totals").as("ebtTotals");
      cy.addScratchItemToOrder(getters.classificationsVisible[1].id);
      cy.wait("@ebtTotals")
        .its("response.statusCode")
        .should("eq", 200);
    });

    cy.get("[data-test=pos-bottom-ebt-button]").click();
    cy.wait("@ebtTotals").then(({ response }) => {
      cy.get("[data-test=order-math-ebtSubTotal]").should(
        "contain.text",
        `$${(response.body.data.ebt_sub_total / 100).toFixed(2)}`
      );
    });

    cy.get("[data-test=payment-input-remainingToEbt-button]").click();

    cy.intercept("/api/orders/calculate-payment").as("orderPayment");
    cy.get("[data-test=payment-input-cash]").type("{selectall}{backspace}20");

    cy.wait("@orderPayment").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      const paymentMath = response.body.data;

      cy.get("[data-test=pos-bottom-ebtPaid]").should("be.visible");
      cy.get("[data-test=pos-bottom-ebtPaid]").should(
        "contain.text",
        `$${(paymentMath.amount_paid / 100).toFixed(2)}`
      );
      cy.get("[data-test=pos-bottom-amountRemaining]").should(
        "contain.text",
        `$${(paymentMath.amount_remaining / 100).toFixed(2)}`
      );
    });

    cy.wait("@orderPayment").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      const paymentMath = response.body.data;

      cy.get("[data-test=pos-bottom-change]").should(
        "have.class",
        "text-green-700"
      );
      cy.get("[data-test=pos-bottom-change]").should(
        "contain.text",
        `$${(paymentMath.change / 100).toFixed(2)}`
      );
      cy.get("[data-test=pos-bottom-change-note]").should("be.visible");

      cy.get("[data-test=pos-bottom-checkout-button]").click();
      cy.get("[data-test=order-summary-modal-change-note]").should(
        "contain.text",
        `$${(paymentMath.change / 100).toFixed(2)} Change Due`
      );
      cy.get("[data-test=order-summary-modal-newOrder-button]").click();
    });
  });

  it("can edit a consignment fee", function() {
    cy.get("[data-test=pos-search-input]").type(this.skus[2]);
    cy.get("[data-test=pos-right-itemPrice-input]").type(
      "{selectall}{backspace}45"
    );

    cy.get("[data-test=consignmentFee-modal]").should("be.visible");
    cy.get("[data-test=consignmentFee-input]").should("contain.value", "10");

    cy.get("[data-test=consignmentFee-input]").type("{selectall}{backspace}8");

    cy.get("[data-test=saveFee-button]").click();

    cy.checkToastedMessage(
      "New fee will be used for this sale only.",
      "success"
    );
  });
});
