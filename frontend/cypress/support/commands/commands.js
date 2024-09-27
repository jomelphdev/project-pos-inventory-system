// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add("login", function() {
  // Load the app in order for `cy.window` to work
  cy.visit("/login");
  cy.resetDB();

  cy.window().then(() => {
    cy.intercept("/api/users/authenticate").as("loginRequest");

    cy.getVuexStore().then(store => {
      store.dispatch("login", {
        username: "TestUser",
        password: "Test123"
      });

      cy.wait("@loginRequest").then(({ response }) => {
        if (
          response.statusCode != 200 &&
          response.body.message == "User does not exist."
        ) {
          cy.request(
            "POST",
            store.getters.laravelUrl + "/api/test/seed-test-user"
          )
            .its("status")
            .should("eq", 200);

          return cy.login();
        }

        expect(response.statusCode).to.equal(200);
      });

      cy.routeEquals("/scan", 10000);
    });
  });
});

Cypress.Commands.add("loginUsingForm", function() {
  cy.visit(Cypress.env("baseUrl") + "/login");
  cy.get("[data-test=username-input]").as("usernameInput");
  cy.get("[data-test=password-input]").as("passwordInput");
  cy.get("[data-test=login-button]").as("loginButton");
  cy.get("@usernameInput").type("HarvardHoodie");
  cy.get("@passwordInput").type("Temp123");
  cy.get("@loginButton").click();
});

Cypress.Commands.add("selectPrinters", function(
  forLabel = false,
  forReceipt = false
) {
  if (forLabel) {
    cy.get("[data-test=printerList-label]")
      .contains("Zebra", { matchCase: false, timeout: 8000 })
      .click();
  }

  if (forReceipt) {
    cy.get("[data-test=printerList-receipt]")
      .contains("Epson", { matchCase: false })
      .click();
  }

  cy.get("[data-test=closePrintSettingsModal-button]").click();
});

Cypress.Commands.add("testCustomSelect", function(
  selectOptions,
  identifier,
  disabledElements = []
) {
  let dataTestBase = `data-test=${identifier}-custom-select`;

  if (selectOptions.length > 3) {
    cy.get(`[${dataTestBase}-showAll]`).click();

    cy.get(`[${dataTestBase}-options]`).then(list => {
      list = list[0];

      expect(list.children.length).to.be.greaterThan(3);
    });

    if (disabledElements.length > 0) {
      cy.checkIfElementsAreHidden(disabledElements);
    }

    cy.get(`[${dataTestBase}-back]`).click();
  }

  const selectedOption = selectOptions[0].id;
  cy.get(`[${dataTestBase}-option-${selectedOption}]`).click();
});

Cypress.Commands.add("checkIfElementsAreHidden", function(elements) {
  for (let element of elements) {
    cy.get(element).should("not.exist");
  }
});

Cypress.Commands.add("checkToastedMessage", function(
  expectedMessage,
  type = "success",
  timeout = 4000
) {
  cy.get(`.toasted.${type}`, { timeout: timeout }).should(
    "contain.text",
    expectedMessage
  );
});

Cypress.Commands.add(
  "getContents",
  {
    prevSubject: false
  },
  fileName => {
    Cypress.log({
      name: "getContents"
    });
    return cy
      .fixture(fileName, "base64")
      .then(Cypress.Blob.base64StringToBlob)
      .then(blob => {
        // instantiate File from `application` window, not cypress window
        return cy.window().then(win => {
          const file = new win.File([blob], fileName);
          const dataTransfer = new win.DataTransfer();
          dataTransfer.items.add(file);
          return dataTransfer;
        });
      });
  }
);

Cypress.Commands.add("routeEquals", function(route, timeout = 4000) {
  cy.url({ timeout: timeout }).should("eq", Cypress.env("baseUrl") + route);
});

Cypress.Commands.add("getVuexStore", function() {
  return cy.window().its("app.$store");
});

Cypress.Commands.add("resetDB", function() {
  cy.getVuexStore().then(({ getters }) => {
    cy.request("POST", getters.laravelUrl + "/api/test/clear").then(
      ({ status }) => {
        expect(status).to.equal(200);
      }
    );
  });
});

Cypress.Commands.add("deleteTestUser", function() {
  cy.getVuexStore().then(({ getters }) => {
    cy.request("POST", getters.laravelUrl + "/api/test/delete-test-user")
      .its("status")
      .should("eq", 200);
  });
});

Cypress.Commands.add("createSpecData", function(specName) {
  cy.getVuexStore().then(({ getters }) => {
    cy.request("POST", getters.laravelUrl + "/api/test/seed-spec-data", {
      spec_name: specName
    }).then(({ body, status }) => {
      expect(status).to.equal(200);

      return body.data;
    });
  });
});

Cypress.Commands.add("iframeLoaded", { prevSubject: "element" }, $iframe => {
  const contentWindow = $iframe.prop("contentWindow");
  return new Promise(resolve => {
    if (contentWindow && contentWindow.document.readyState === "complete") {
      resolve(contentWindow);
    } else {
      $iframe.on("load", () => {
        resolve(contentWindow);
      });
    }
  });
});

Cypress.Commands.add(
  "getInDocument",
  { prevSubject: "document" },
  (document, selector) => Cypress.$(selector, document)
);

Cypress.Commands.add("getWithinIframe", targetElement =>
  cy
    .get("iframe")
    .iframeLoaded()
    .its("document")
    .getInDocument(targetElement)
);

const isIframeLoaded = $iframe => {
  const contentWindow = $iframe.contentWindow;

  const src = $iframe.attributes.src;
  const href = contentWindow.location.href;
  if (contentWindow.document.readyState === "complete") {
    return href !== "about:blank" || src === "about:blank" || src === "";
  }

  return false;
};

Cypress.Commands.add(
  "iframe",
  { prevSubject: "element" },
  $iframes =>
    new Cypress.Promise(resolve => {
      const loaded = [];

      $iframes.each((_, $iframe) => {
        loaded.push(
          new Promise(subResolve => {
            if (isIframeLoaded($iframe)) {
              subResolve($iframe.contentDocument.body);
            } else {
              Cypress.$($iframe).on("load.appearHere", () => {
                if (isIframeLoaded($iframe)) {
                  subResolve($iframe.contentDocument.body);
                  Cypress.$($iframe).off("load.appearHere");
                }
              });
            }
          })
        );
      });

      return Promise.all(loaded).then(resolve);
    })
);

Cypress.Commands.add("getIframe", iframe => {
  return cy
    .get(iframe)
    .its("0.contentDocument.body")
    .should("be.visible")
    .then(cy.wrap);
});
