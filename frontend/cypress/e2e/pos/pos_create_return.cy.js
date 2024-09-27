describe("POS return create page", () => {
  let items;
  let order;
  let totals;

  before(() => {
    cy.login();

    cy.getVuexStore().then(({ dispatch, getters }) => {
      const selectedStore = getters.stores[0];

      dispatch("selectStore", selectedStore);
    });

    cy.createSpecData("pos_create_return").then(data => {
      items = data.items;
      order = data.order;
      totals = data.totals;
    });

    cy.visit("/pos/returns");
    cy.routeEquals("/pos/returns");
  });

  beforeEach(() => {
    cy.wrap(items).as("items");
    cy.wrap(order).as("order");
    cy.wrap(totals).as("totals");
  });

  after(() => {
    cy.resetDB();
  });

  it("can search order", function() {
    cy.get("[data-test=order-search-input]").type(this.order.id);

    cy.intercept("/api/orders/return/*").as("orderSearch");
    cy.get("[data-test=order-search-button]").click();

    cy.wait("@orderSearch")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=pos-item-table]").should("be.visible");
    cy.assertItemsTableLength(3);
    cy.get("[data-test=returns-right]").should("be.visible");
    cy.get("[data-test=returns-bottom]").should("be.visible");
    cy.get("[data-test=returns-bottom-totals-display]").should("be.visible");
  });

  it("can start over", function() {
    cy.get("[data-test=returns-bottom-startOver-button]").click();

    cy.get("[data-test=returns-home]").should("be.visible");

    cy.get("[data-test=order-search-input]").type(`${this.order.id}{enter}`);
  });

  it("can not continue without valid items", function() {
    cy.get("[data-test=returns-bottom-continue-button]").click();
    cy.checkToastedMessage(
      "No items are ready for return, please double check you entered an action and a quantity.",
      "error"
    );
  });

  it("can add an item to the return", function() {
    cy.get("[data-test=returns-right-action-1]").click();

    cy.intercept("/api/returns/calculate-refund").as("calculateRefund");
    cy.get("[data-test=returns-right-quantityReturned-input]").type("2");

    cy.checkToastedMessage(
      "Cannot return more items than was ordered.",
      "info"
    );
    cy.get("[data-test=pos-item-row-quantity]").should("contain.text", "1");

    cy.wait("@calculateRefund").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      const { sub_total, tax, total } = response.body.data;

      cy.get("[data-test=returns-bottom-totals-items]").should(
        "contain.text",
        "1"
      );
      cy.get("[data-test=returns-bottom-totals-subTotal]").should(
        "contain.text",
        `$${(sub_total / 100).toFixed(2)}`
      );
      cy.get("[data-test=returns-bottom-totals-tax]").should(
        "contain.text",
        `$${(tax / 100).toFixed(2)}`
      );
      cy.get("[data-test=returns-bottom-totals-total]").should(
        "contain.text",
        `$${(total / 100).toFixed(2)}`
      );
    });
  });

  it("can continue and go back", function() {
    cy.get("[data-test=returns-bottom-continue-button]").click();

    cy.get("[data-test=returns-bottom-refunded-input]").should("not.exist");
    cy.get("[data-test=returns-bottom-finalize-button]").should("not.exist");
    cy.get("[data-test=returns-bottom-items-returning-table]").should(
      "be.visible"
    );
    cy.get("[data-test=returns-bottom-items-returning-table]")
      .children()
      .its("length")
      .should("eq", 1);

    cy.intercept("/api/orders/calculate-payment").as("calculatePayment");
    cy.get("[data-test=payment-input-remainingToCard-button]").click();

    cy.wait("@calculatePayment").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      const { amount_paid } = response.body.data;

      cy.get("[data-test=returns-bottom-refunded-input]").should("be.visible");
      cy.get("[data-test=returns-bottom-cardDue]").should(
        "contain.text",
        `$${(amount_paid / 100).toFixed(2)}`
      );
      cy.get("[data-test=returns-bottom-amountRemaining]").should(
        "contain.text",
        `$0.00`
      );
    });

    cy.get("[data-test=returns-bottom-goBack-button]").click();

    cy.get("[data-test=returns-bottom-totals-display]").should("be.visible");

    cy.get("[data-test=returns-bottom-continue-button]").click();

    cy.window()
      .its("PosReturns")
      .then(({ returnForm }) => {
        cy.get("[data-test=payment-input-cash]").should(
          "contain.value",
          "$0.00"
        );
        cy.get("[data-test=returns-bottom-cardDue]").should(
          "contain.text",
          `$0.00`
        );
        cy.get("[data-test=returns-bottom-amountRemaining]").should(
          "contain.text",
          `$${(returnForm.total / 100).toFixed(2)}`
        );
      });
  });

  it("can create return", function() {
    cy.get("[data-test=payment-input-remainingToCard-button]").click();
    cy.get("[data-test=returns-bottom-refunded-input]").click();

    cy.get("[data-test=returns-bottom-finalize-button]").should("be.visible");

    cy.get("[data-test=returns-bottom-finalize-button]").click();
    cy.selectPrinters(false, true);

    cy.intercept("/api/returns/create").as("createReturn");
    cy.get("[data-test=returns-bottom-finalize-button]").click();

    cy.checkToastedMessage("Creating return...");
    cy.wait("@createReturn")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=returns-home]").should("be.visible");
  });

  it("quantity was returned", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.request({
        url: `${getters.laravelUrl}/api/items/${this.items[0].id}`,
        auth: {
          bearer: getters.token.split(" ")[1]
        }
      }).then(({ body, status }) => {
        expect(status).to.equal(200);

        const item = body.data.item;
        const storeQuantity = item.store_quantities[0];

        expect(storeQuantity.quantity).to.equal(1);
        expect(storeQuantity.quantity_received).to.equal(1);
        expect(storeQuantity.quantity_sold).to.equal(0);
        expect(storeQuantity.quantity_returned).to.equal(1);
      });
    });
  });

  it("already returned items are hidden", function() {
    cy.intercept("/api/orders/return/*").as("orderSearch");
    cy.get("[data-test=order-search-input]").type(`${this.order.id}{enter}`);
    cy.wait("@orderSearch")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=pos-item-table-item-0]").should(
      "have.class",
      "rr-table__tr--hidden"
    );
    cy.get("[data-test=pos-item-table-item-0]")
      .find("[data-test=pos-item-row-quantity]")
      .should("contain.text", "0 left of 1");
    cy.get("[data-test=pos-item-table-item-1]").should(
      "have.class",
      "rr-table__tr--selected"
    );
  });

  it("can not select hidden row", function() {
    cy.get("[data-test=pos-item-table-item-0]").click();

    cy.checkToastedMessage("Item has already been returned.", "info");
    cy.get("[data-test=pos-item-table-item-1]").should(
      "have.class",
      "rr-table__tr--selected"
    );
  });

  it("can return EBT item and normal item together", function() {
    const fillReturnItem = () => {
      cy.get("[data-test=returns-right-action-0]").click();
      cy.get("[data-test=returns-right-quantityReturned-input]").type("1");
    };

    cy.intercept("/api/returns/calculate-refund").as("calculateRefund");
    fillReturnItem();

    cy.wait("@calculateRefund").then(({ response }) => {
      expect(response.statusCode).to.equal(200);

      const refundMath = response.body.data;

      expect(refundMath.tax).to.equal(0);
      expect(refundMath.ebt_sub_total).to.equal(2000);
    });

    cy.get("[data-test=pos-item-table-item-2]").click();

    fillReturnItem();

    cy.get("[data-test=returns-bottom-continue-button]").click();

    cy.intercept("/api/orders/calculate-payment").as("calculatePayment");
    cy.get("[data-test=payment-input-remainingToEbt-button]").click();
    cy.wait("@calculatePayment")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=payment-input-remainingToCard-button]").click();
    cy.get("[data-test=returns-bottom-refunded-input]").click();

    cy.intercept("/api/returns/create").as("createReturn");
    cy.get("[data-test=returns-bottom-finalize-button]").click();
    cy.wait("@createReturn")
      .its("response.statusCode")
      .should("eq", 200);
  });

  it("discarded quantity wasn't returned", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.request({
        url: `${getters.laravelUrl}/api/items/${this.items[1].id}`,
        auth: {
          bearer: getters.token.split(" ")[1]
        }
      }).then(({ body, status }) => {
        expect(status).to.equal(200);

        const item = body.data.item;
        const storeQuantity = item.store_quantities[0];

        expect(storeQuantity.quantity).to.equal(0);
        expect(storeQuantity.quantity_received).to.equal(1);
        expect(storeQuantity.quantity_sold).to.equal(0);
        expect(storeQuantity.quantity_returned).to.equal(0);
        expect(storeQuantity.quantity_discarded).to.equal(1);
      });
    });
  });
});
