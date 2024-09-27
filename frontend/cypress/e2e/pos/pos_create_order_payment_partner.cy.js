describe("POS order create page for payment partners", () => {
  let store = null;

  before(() => {
    cy.login();

    cy.getVuexStore().then(({ getters }) => {
      store = getters.stores[0];

      cy.createSpecData("pos_create_order_payment_partner").then(() => {
        cy.visit("/pos/");
        cy.routeEquals("/pos/");
      });
    });
  });

  beforeEach(() => {
    cy.wrap(store).as("selectedStore");
  });

  it("can select a station", function() {
    cy.get(
      `[data-test=store-select-modal-store-${this.selectedStore.id}]`
    ).click();

    cy.get("[data-test=station-select-modal]").should("be.visible");

    cy.get("[data-test=station-select-modal-station-0]").click();

    cy.get("[data-test=station-select-modal]").should("not.exist");
  });

  it("can change station", function() {
    cy.get("[data-test=pos-changeStation-button]").should("be.visible");

    cy.get("[data-test=pos-changeStation-button]").click();

    cy.get("[data-test=station-select-modal]").should("be.visible");

    cy.get("[data-test=station-select-modal-station-0]").click();
  });

  it("shows station data on POS home", function() {
    cy.get("[data-test=selectedStation-data]").should("be.visible");
  });

  it("can create a terminal session when needed", function() {
    cy.addScratchItemToOrder();

    cy.intercept("/api/card/connect?*").as("connectToTerminal");
    cy.get("[data-test=pos-bottom-card-button]").click();

    cy.get("[data-test=connectingToTerminal-text]").should("be.visible");
    cy.wait("@connectToTerminal")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=pos-bottom-cash-button]").click();

    cy.get("[data-test=connectingToTerminal-text]").should("not.exist");

    cy.get("[data-test=payment-input-remainingToCard-button]").click();

    cy.get("[data-test=pos-bottom-checkout-button]").should("be.disabled");
    cy.get("[data-test=pos-start-over-button]").should("be.disabled");
    cy.get("[data-test=connectingToTerminal-text]").should("be.visible");
    cy.wait("@connectToTerminal")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=pos-bottom-checkout-button]").should("not.be.disabled");
    cy.get("[data-test=pos-start-over-button]").should("not.be.disabled");
  });

  it("sends correct data", function() {
    cy.get("[data-test=pos-bottom-checkout-button]").click();
    cy.selectPrinters(false, true);

    cy.get("[data-test=pos-bottom-checkout-button]").click();

    cy.get("[data-test=card-type-modal]").should("be.visible");

    cy.get("[data-test=credit-button]").click();

    cy.get("[data-test=loading-modal]").should("be.visible");
    cy.window()
      .its("PosIndex")
      .then(({ order }) => {
        cy.expect(order.terminal_hsn).to.not.be.null;
        cy.expect(order.session_key).to.not.be.null;
      });
  });

  it("can disconnect terminal", function() {
    cy.visit("/pos");

    cy.intercept("api/card/disconnect?*").as("disconnectTerminal");
    cy.get("[data-test=disconnectTerminal-button]").click();

    cy.wait("@disconnectTerminal")
      .its("response.statusCode")
      .should("eq", 200);
  });
});
