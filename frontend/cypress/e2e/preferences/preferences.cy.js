describe("the preferences/settings page", () => {
  before(() => {
    cy.login();
    cy.createSpecData("preferences");
    cy.visit("/settings/stores");

    cy.get("[data-test=nav-title").should("be.visible");
    cy.get("[data-test=nav-title").should("contain.text", "Settings");
  });

  after(() => {
    cy.resetDB();
  });

  it("can start create and go back", function() {
    cy.get("[data-test=createStore-button]").click();

    cy.get("[data-test=store-form-modal]").should("be.visible");
    cy.get("[data-test=submit-button]").should("be.disabled");

    cy.get("[data-test=cancel-button]").click();

    cy.get("[data-test=store-form-modal]").should("not.exist");
  });

  it("can disable and re-enable classifications & conditions", function() {
    cy.intercept("/api/preferences/update").as("updatePreferences");
    cy.get("[data-test=disableClassifications-input]").click();

    cy.wait("@updatePreferences")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=disableClassifications-input]").should("be.checked");

    cy.intercept("/api/preferences/update").as("updatePreferences");
    cy.get("[data-test=disableConditions-input]").click();

    cy.wait("@updatePreferences")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=disableClassifications-input]").should("be.checked");

    cy.get("[data-test=disableClassifications-input]").click();
    cy.get("[data-test=disableConditions-input]").click();
  });

  it("can create a store", function() {
    cy.get("[data-test=noStores-indicator]").should("be.visible");
    cy.get("[data-test=createStore-button]").click();

    cy.get("[data-test=name-input]").type("Shoppe Right - Testopia");
    cy.get("[data-test=taxRate-input]").type("7.25");
    cy.get("[data-test=address-input]").type("123 Test St");
    cy.get("[data-test=phone-input]").type("5554445555");
    cy.get("[data-test=city-input]").type("Testopia");
    cy.get("[data-test=state-select]").select("Arizona | AZ");
    cy.get("[data-test=zip-input]").type("12345");
    cy.get("[data-test=receiptTitle-input]").type("Shoppe Right");

    cy.intercept("/api/preferences/update/preference").as("createStore");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createStore")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=store-form-modal]").should("not.exist");
    cy.checkToastedMessage("Preferences updated!");

    cy.get("[data-test=noStores-indicator]").should("not.exist");
    cy.get("[data-test=stores-table-body]")
      .children()
      .its("length")
      .should("eq", 1);
  });

  it("can edit a store", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.get(`[data-test=stores-table-body-${getters.stores[0].id}]`).trigger(
        "mouseover"
      );

      cy.get("[data-test=editStore-button]").click();

      cy.get("[data-test=taxRate-input]").type("{selectall}{backspace}7.5");

      cy.intercept("/api/preferences/update/preference").as("updateStore");
      cy.get("[data-test=submit-button]").click();

      cy.wait("@updateStore")
        .its("response.statusCode")
        .should("eq", 200);
      cy.get("[data-test=store-form-modal]").should("not.exist");
      cy.checkToastedMessage("Preferences updated!");

      cy.get(`[data-test=stores-table-body-${getters.stores[0].id}]`)
        .find("[data-test=store-body-taxRate]")
        .should("contain.text", "7.5");
    });
  });

  it("can go to store preference options", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.get(`[data-test=stores-table-body-${getters.stores[0].id}]`).trigger(
        "mouseover"
      );
      cy.get("[data-test=editStoreOptions-button]").click();

      cy.routeEquals(
        `/settings/classifications?store_id=${getters.stores[0].id}`
      );
      cy.get("[data-test=noClassifications-indicator]").should("be.visible");
      cy.get("[data-test=createDefaults-button]").should("be.visible");
      cy.get("[data-test=checkoutStations-nav]").should("not.exist");

      cy.get("[data-test=nav-title").should(
        "contain.text",
        getters.stores[0].name
      );
    });
  });

  it("can create default classifications", function() {
    cy.intercept("/api/preferences/seed-default").as("seedClassifications");
    cy.get("[data-test=createDefaults-button]").click();

    cy.wait("@seedClassifications")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=noClassifications-indicator]").should("not.exist");
    cy.get("[data-test=createDefaults-button]").should("not.exist");
    cy.get("[data-test=classifications-table-body]")
      .children()
      .its("length")
      .should("eq", 4);
  });

  it("can create classification", function() {
    cy.get("[data-test=createPreference-button]").click();

    cy.get("[data-test=classification-form-modal]").should("be.visible");

    cy.get("[data-test=name-input]").type("Test Classification");
    cy.get("[data-test=discount-input]").type("{selectall}{backspace}5");
    cy.get("[data-test=isEbt-input]").click();
    cy.get("[data-test=isTaxed-input]").click();

    cy.window()
      .its("Classifications")
      .then(({ preferenceForm }) => {
        const options = preferenceForm.preference_options;

        expect(options[0].value).to.be.true;
        expect(options[1].value).to.be.false;
      });

    cy.intercept("/api/preferences/update/preference").as(
      "createClassification"
    );
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createClassification")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=classification-form-modal]").should("not.exist");
    cy.checkToastedMessage("Preferences updated!");

    cy.getVuexStore().then(({ getters }) => {
      const classification = getters.classificationsVisible[0];

      cy.get(`[data-test=classifications-table-body-${classification.id}]`)
        .find("[data-test=isEbt-indicator]")
        .should("be.visible");
      cy.get(`[data-test=classifications-table-body-${classification.id}]`)
        .find("[data-test=noTax-indicator]")
        .should("be.visible");
    });
  });

  it("can edit/hide classification", function() {
    cy.getVuexStore().then(({ getters }) => {
      const classification = getters.classificationsVisible[0];

      cy.get(
        `[data-test=classifications-table-body-${classification.id}]`
      ).click();

      cy.get("[data-test=classification-form-modal]").should("be.visible");

      cy.get("[data-test=hidden-input]").click();

      cy.intercept("/api/preferences/update/preference").as(
        "updateClassification"
      );
      cy.get("[data-test=submit-button]").click();

      cy.wait("@updateClassification")
        .its("response.statusCode")
        .should("eq", 200);
      cy.get(
        `[data-test=classifications-table-body-${classification.id}]`
      ).should("have.class", "rr-table__tr--hidden");
    });
  });

  it("can go to conditions", function() {
    cy.get("[data-test=conditions-nav]").click();

    cy.getVuexStore().then(({ getters }) => {
      cy.routeEquals(`/settings/conditions?store_id=${getters.stores[0].id}`);
    });
  });

  it("can create default conditions", function() {
    cy.get("[data-test=noConditions-indicator]").should("be.visible");

    cy.intercept("/api/preferences/seed-default").as("seedConditions");
    cy.get("[data-test=createDefaults-button]").click();

    cy.wait("@seedConditions")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=noConditions-indicator]").should("not.exist");
    cy.get("[data-test=createDefaults-button]").should("not.exist");
    cy.get("[data-test=conditions-table-body]")
      .children()
      .its("length")
      .should("eq", 4);
  });

  it("can create condition", function() {
    cy.get("[data-test=createPreference-button]").click();

    cy.get("[data-test=condition-form-modal]").should("be.visible");

    cy.get("[data-test=name-input]").type("Test Condition");
    cy.get("[data-test=discount-input]").type("{selectall}{backspace}5");

    cy.intercept("/api/preferences/update/preference").as("createCondition");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createCondition")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=condition-form-modal]").should("not.exist");
    cy.checkToastedMessage("Preferences updated!");
  });

  it("can edit/hide condition", function() {
    cy.getVuexStore().then(({ getters }) => {
      const condition = getters.conditionsVisible[0];

      cy.get(`[data-test=conditions-table-body-${condition.id}]`).click();

      cy.get("[data-test=condition-form-modal]").should("be.visible");

      cy.get("[data-test=hidden-input]").click();

      cy.intercept("/api/preferences/update/preference").as("updateCondition");
      cy.get("[data-test=submit-button]").click();

      cy.wait("@updateCondition")
        .its("response.statusCode")
        .should("eq", 200);
      cy.get(`[data-test=conditions-table-body-${condition.id}]`).should(
        "have.class",
        "rr-table__tr--hidden"
      );
    });
  });

  it("can go to discounts", function() {
    cy.get("[data-test=discounts-nav]").click();

    cy.getVuexStore().then(({ getters }) => {
      cy.routeEquals(`/settings/discounts?store_id=${getters.stores[0].id}`);
    });
  });

  it("can create default discounts", function() {
    cy.get("[data-test=noDiscounts-indicator]").should("be.visible");

    cy.intercept("/api/preferences/seed-default").as("seedDiscounts");
    cy.get("[data-test=createDefaults-button]").click();

    cy.wait("@seedDiscounts")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=noDiscounts-indicator]").should("not.exist");
    cy.get("[data-test=createDefaults-button]").should("not.exist");
    cy.get("[data-test=discounts-table-body]")
      .children()
      .its("length")
      .should("eq", 2);
  });

  it("can create discount", function() {
    cy.get("[data-test=createPreference-button]").click();

    cy.get("[data-test=discount-form-modal]").should("be.visible");

    cy.get("[data-test=name-input]").type("Test Discount");
    cy.get("[data-test=discount-input]").type("{selectall}{backspace}5");

    cy.intercept("/api/preferences/update/preference").as("createDiscount");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createDiscount")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=discount-form-modal]").should("not.exist");
    cy.checkToastedMessage("Preferences updated!");
  });

  it("can edit/hide discount", function() {
    cy.getVuexStore().then(({ getters }) => {
      const discount = getters.discountsVisible[0];

      cy.get(`[data-test=discounts-table-body-${discount.id}]`).click();

      cy.get("[data-test=discount-form-modal]").should("be.visible");

      cy.get("[data-test=hidden-input]").click();

      cy.intercept("/api/preferences/update/preference").as("updateDiscount");
      cy.get("[data-test=submit-button]").click();

      cy.wait("@updateDiscount")
        .its("response.statusCode")
        .should("eq", 200);
      cy.get(`[data-test=discounts-table-body-${discount.id}]`).should(
        "have.class",
        "rr-table__tr--hidden"
      );
    });
  });

  it("can go back to stores", function() {
    cy.get("[data-test=goBack-button]").click();

    cy.routeEquals("/settings/stores");
  });

  it("can go to employees", function() {
    cy.get("[data-test=employees-nav]").click();

    cy.routeEquals("/settings/employees");
  });

  it("can create employee", function() {
    cy.get("[data-test=createPreference-button]").click();

    cy.get("[data-test=employee-form-modal]").should("be.visible");

    cy.get("[data-test=firstName-input]").type("Bruce");
    cy.get("[data-test=lastName-input]").type("Wayne");
    cy.get("[data-test=email-input]").type("bwayne@test.wayne");
    cy.get("[data-test=password-input]").type("Test1234");
    cy.get("[data-test=passwordConfirm-input]").type("Test1234");

    cy.get("[data-test=username-input]").should("contain.value", "brucewayne");

    cy.intercept("/api/users/create").as("createEmployee");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createEmployee")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=employee-form-modal]").should("not.exist");
    cy.checkToastedMessage("User has been created.");
  });

  it("can edit/hide employee", function() {
    cy.getVuexStore().then(({ getters }) => {
      const employee = getters.employees[0];

      cy.get(`[data-test=employees-table-body-${employee.id}]`).click();

      cy.get("[data-test=employee-form-modal]").should("be.visible");

      cy.get("[data-test=hidden-input]").click();

      cy.intercept("/api/users/update/*").as("updateEmployee");
      cy.get("[data-test=submit-button]").click();

      cy.wait("@updateEmployee")
        .its("response.statusCode")
        .should("eq", 200);
      cy.get(`[data-test=employees-table-body-${employee.id}]`).should(
        "have.class",
        "rr-table__tr--hidden"
      );
      cy.checkToastedMessage("User has been updated.");
    });
  });

  it("can go to consignors", function() {
    cy.get("[data-test=consignors-nav]").click();

    cy.routeEquals("/settings/consignors");
  });

  it("can create consignor", function() {
    cy.get("[data-test=noConsignors-indicator]").should("be.visible");

    cy.get("[data-test=createPreference-button]").click();

    cy.get("[data-test=consignor-form-modal]").should("be.visible");

    cy.get("[data-test=name-input]").type("Test Consigner");
    cy.get("[data-test=consignmentFeePercentage-input]").type("10");

    cy.intercept("/api/preferences/update/preference").as("createConsignor");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createConsignor")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=consignor-form-modal]").should("not.exist");
    cy.checkToastedMessage("Preferences updated!");
  });

  it("can update consignor", function() {
    cy.getVuexStore().then(({ getters }) => {
      cy.get(
        `[data-test=consignors-table-body-${getters.consignors[0].id}]`
      ).click();
    });

    cy.get("[data-test=consignor-form-modal]").should("be.visible");

    cy.get("[data-test=name-input]").type(
      "{selectall}{backspace}Test Consignor"
    );

    cy.intercept("/api/preferences/update/preference").as("updateConsignor");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@updateConsignor")
      .its("response.statusCode")
      .should("eq", 200);

    cy.get("[data-test=consignor-form-modal]").should("not.exist");
    cy.checkToastedMessage("Preferences updated!");
  });

  it("can go to online site preferences", function() {
    cy.get("[data-test=onlineSite-nav]").click();

    cy.routeEquals("/settings/online-site");
  });

  it("can set site slug", function() {
    cy.get("[data-test=slug-input]").as("slug");
    cy.get("@slug").type("shoppe right");

    cy.get("@slug").should("contain.value", "shoppe-right");

    cy.intercept("/api/organization/save-slug").as("saveSlug");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@saveSlug")
      .its("response.statusCode")
      .should("eq", 200);
    cy.checkToastedMessage("Successfully set site URL.");
    cy.routeEquals("/settings/stores");
    cy.get("[data-test=onlineSite-nav]").should("not.exist");
  });

  it("can go to POS preferences", function() {
    cy.get("[data-test=pos-nav]").click();

    cy.routeEquals("/settings/pos");
  });

  it("can hide pos sales", function() {
    cy.get("[data-test=hideSales-input]").should("be.visible");

    cy.get("[data-test=hideSales-input]").click();

    cy.intercept("/api/preferences/update").as("updatePos");

    cy.get("[data-test=save-button]").click();
    cy.wait("@updatePos")
      .its("response.statusCode")
      .should("eq", 200);
    cy.checkToastedMessage("Preferences updated!");

    cy.visit("/pos/");
    cy.routeEquals("/pos/");

    cy.get("[data-test=pos-daily-sales-tile]").should("not.exist");
    cy.get("[data-test=pos-daily-returns-tile]").should("not.exist");

    cy.visit("/settings/pos");
    cy.routeEquals("/settings/pos");
  });

  it("can set merchant preferences", function() {
    cy.get("[data-test=save-button]").should("be.disabled");

    cy.get("[data-test=paymentPartner-input]").click();

    cy.get("[data-test=save-button]").should("be.visible");
    cy.get("[data-test=required-indicator]").should("be.visible");
    cy.get("[data-test=length-indicator]").should("not.exist");

    cy.get("[data-test=merchantId-input]").type("800000000");

    cy.get("[data-test=required-indicator]").should("not.exist");
    cy.get("[data-test=length-indicator]").should("be.visible");

    cy.get("[data-test=merchantId-input]").type("417");

    cy.get("[data-test=length-indicator]").should("not.exist");

    cy.intercept("/api/preferences/update").as("saveMerchantData");
    cy.get("[data-test=save-button]").click();

    cy.wait("@saveMerchantData")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=save-button]").should("be.disabled");
  });

  it("can go to checkout stations", function() {
    cy.getVuexStore().then(store => {
      cy.get("[data-test=stores-nav]").click();
      cy.get(
        `[data-test=stores-table-body-${store.getters.stores[0].id}]`
      ).trigger("mouseover");
      cy.get("[data-test=editStoreOptions-button]").click();
    });

    cy.intercept("/api/card/terminals*").as("getTerminals");
    cy.get("[data-test=checkoutStations-nav]").click();

    cy.wait("@getTerminals")
      .its("response.statusCode")
      .should("eq", 200);

    cy.getVuexStore().then(({ getters }) => {
      cy.routeEquals(`/settings/stations?store_id=${getters.stores[0].id}`);
    });
    cy.get("[data-test=noStations-indicator]").should("be.visible");
  });

  it("can create checkout station", function() {
    cy.get("[data-test=createPreference-button]").click();

    cy.get("[data-test=station-form-modal]").should("be.visible");
    cy.get("[data-test=submit-button]").should("be.disabled");

    cy.get("[data-test=name-input]").type("Test Station");

    cy.get("[data-test=submit-button]").should("not.be.disabled");

    cy.get("[data-test=terminal-select]").select("18163PP84176695");

    cy.intercept("/api/preferences/update/preference").as("createStation");
    cy.get("[data-test=submit-button]").click();

    cy.wait("@createStation")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=station-form-modal]").should("not.exist");
  });

  it("duplicate terminal modal pops up", function() {
    cy.get("[data-test=createPreference-button]").click();
    cy.get("[data-test=name-input]").type("Test Station 2");
    cy.get("[data-test=terminal-select]").select("18163PP84176695");
    cy.get("[data-test=submit-button]").click();

    cy.checkToastedMessage(
      "You can only assign a terminal to one checkout station.",
      "error"
    );
    cy.get("[data-test=occupyingStation-modal]").should("be.visible");

    cy.intercept("/api/preferences/update/multiple").as("updateMultiple");
    cy.get("[data-test=replace-button]").click();

    cy.wait("@updateMultiple")
      .its("response.statusCode")
      .should("eq", 200);
    cy.get("[data-test=occupyingStation-modal]").should("not.exist");
    cy.get("[data-test=station-form-modal]").should("not.exist");
  });

  it("stations show in stations table", function() {
    cy.get("[data-test=stations-table-body]")
      .children()
      .its("length")
      .should("eq", 2);
  });
});
