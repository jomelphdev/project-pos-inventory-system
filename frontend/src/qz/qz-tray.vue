<template>
  <div>
    <ModalPersist
      ref="printSettingsModal"
      max-width="5xl"
      data-test="printSettingsModal"
    >
      <template v-slot:header> Printer Setup </template>
      <template v-slot:body>
        <div class="grid md:grid-cols-3 grid-cols-1 gap-x-12 gap-y-8">
          <qz-tray-connect></qz-tray-connect>
          <qz-tray-printers mode="label"></qz-tray-printers>
          <qz-tray-printers mode="receipt"></qz-tray-printers>
          <!-- <qz-tray-print-mode></qz-tray-print-mode> -->
          <!-- <qz-tray-input></qz-tray-input> -->
          <!-- <qz-tray-options ></qz-tray-options> -->
          <!-- <qz-tray-action></qz-tray-action> -->
        </div>
      </template>
      <template v-slot:footer>
        <button
          class="rr-button rr-button--lg rr-button--primary-solid"
          @click="$refs.printSettingsModal.closeModal()"
          :disabled="!qzReadyToPrint"
          data-test="closePrintSettingsModal-button"
        >
          Done
        </button>
      </template>
    </ModalPersist>
    <slot></slot>
  </div>
</template>

<script>
import * as qz from "qz-tray";
import { sha256 } from "js-sha256";
import QzConfig from "@/qz/qz-default-config.js";
import { mapGetters } from "vuex";
import ModalPersist from "@/components/ModalPersist";
import { formatCurrency, getClassificationName, getStore } from "@/helpers.js";

export default {
  name: "QzTray",

  label: "base",

  components: {
    ModalPersist,
  },

  props: {
    config: {
      type: Object,
      default: function () {
        return {};
      },
    },
  },

  data: function () {
    return {
      $qz: null,
      defaultConfig: QzConfig,

      qzConnected: false,
      qzPrinter: "",
      qzPrintOptions: JSON.parse(
        JSON.stringify(QzConfig["print-options"] || {})
      ),
      qzInput: JSON.parse(JSON.stringify(QzConfig["input"] || {})),
      qzPages: [],

      actionStatus: {
        loading: false,
        printed: false,
        failed: false,
      },

      show: false,
    };
  },

  computed: {
    ...mapGetters([
      "loggedIn",
      "qzPanel",
      "qzReadyToPrint",
      "qzLabelPrinter",
      "qzReceiptPrinter",
      "classifications_disabled",
      "discounts",
    ]),

    $qzConfig: function () {
      return Object.assign({}, this.defaultConfig, this.config);
    },
  },

  methods: {
    connectionChanged: function (newValue) {
      this.qzConnected = newValue;
      this.$store.dispatch("updateQzConnected", newValue);
    },

    printerChanged: function (newValue) {
      this.qzPrinter = newValue;
      this.$store.dispatch("updateQzPrinter", newValue);
    },

    optionsChanged: function (newValue) {
      this.qzPrintOptions = newValue;
    },

    inputChanged: function (newValue) {
      this.qzInput = newValue;
    },

    inputPagesChanged: function (newValue) {
      this.qzPages = newValue;
    },

    chr(n) {
      return String.fromCharCode(n);
    },

    printLabel: function (item) {
      this.qzPrinter = this.qzLabelPrinter;
      let data = [];
      const today = new Date();

      // location line: ^FO275,10^FD${item.location}^FS
      data.push(
        `^XA

        ^FX Price
        ^CF0,40
        ^FO20,10^FD${this.formatCurrency(item.price)}^FS
        ^FO300,10^FD${today.getMonth() + 1}/${today.getFullYear() % 100}^FS

        ^FX Location, store, and condition
        ^CF0,20

        ^FO20,55^FD${item.store}^FS
        ^FO20,80^FD${item.conditionName != "None" ? item.conditionName : ""}^FS

        ^FX Barcode.
        ^BY2,2
        ^FO45,125^^B3N,N,40,Y,N^FD${item.sku}^FS

        ^FX Print quantity
        ^PQ${item.quantity}
        ^XZ`
      );

      return this.validateForPrint("label")
        .then(this.getPrinterConfig)
        .then((config) => {
          return this.$qz.print(config, data);
        })
        .then(() => {
          this.$root.$emit("printed", true);
        })
        .catch(() => {
          this.$toasted.show(
            "Something went wrong while trying to print label.",
            { type: "error" }
          );
          this.$root.$emit("printed", false);
        });

      // console.log({
      //   config: id,
      //   data: data,
      // });

      // return Promise.resolve();
    },

    printOrderReceipt: function (order) {
      this.qzPrinter = this.qzReceiptPrinter;
      const receiptOptions = order.receiptOptions;
      // let image = NaN;
      let data = [];

      // Note for Ty: Delete or edit this comment after image print is working

      // Comment out this if... statement to run a receipt without trying to print store logo image
      // if (receiptOptions.storeLogo) {
      //   try {
      //     API.getImage(receiptOptions.storeLogo)
      //       .then(res => {
      //         // // Base 64 attempt
      //         // let reader = new FileReader();
      //         // reader.onload = () => {
      //         //   var b64 = reader.result.replace(/^data:.+;base64,/, 'data:image/png;base64,');
      //         //   console.log(b64)
      //         //   let image = b64;
      //         //   console.log(atob(image))
      //         //   data.push({
      //         //     type: "raw",
      //         //     format: "image",
      //         //     flavor: "file",
      //         //     data: image,
      //         //     options: {
      //         //       language: "ESCPOS",
      //         //       dotDensity: "double"
      //         //     }
      //         //   });

      //         // }
      //         // reader.readAsDataURL(res.data);

      //         // File creation attempt
      //         var blob = res.data;
      //         blob.lastModifiedDate = new Date();
      //         blob.name = "image.png";

      //         if (blob) {
      //           data.push({
      //             type: "raw",
      //             format: "image",
      //             flavor: "file",
      //             data: blob,
      //             options: {
      //               language: "ESCPOS",
      //               dotDensity: "double"
      //             }
      //           });
      //         }
      //       })
      //       .catch(err => {
      //         console.log(err);
      //       });
      //   } catch (err) {
      //     console.log(err);
      //   }
      // }

      setTimeout(() => {
        const totalLength = this.formatCurrency(order.total).length;
        const orderId = order.id.toString();

        data = data.concat(
          this.getReceiptHeader(receiptOptions),
          this.getItemsReceiptData(order),
          this.getOrderSummaryData(order)
        );

        var barcode =
          "\x1D" +
          "h" +
          this.chr(80) + //barcode height
          "\x1D" +
          "f" +
          this.chr(0) + //font for printed number
          "\x1D" +
          "k" +
          this.chr(69) +
          this.chr(orderId.length) +
          orderId +
          this.chr(0); //code39

        const amountPaid = order.amount_paid.amount
          ? order.amount_paid.amount
          : order.amount_paid;

        const store = this.getStore(order.store_id);
        data.push(
          `Total Paid: ${this.formatCurrency(amountPaid)}`.padStart(55, " "),
          "\x0A",
          "\x0A",
          `Change: ${this.formatCurrency(order.change).padStart(
            totalLength,
            " "
          )}`.padStart(55, " "),
          "\x0A",
          "\x0A",
          "\x1B\x61\x31",
          `T = ${store.state.abbreviation} TAX of ${
            store.tax_rate * 100
          }%`.padStart(55, " ")
        );

        if (order.processing_details) {
          let longestValue = 0;
          const details = order.processing_details;

          for (const detail in details) {
            const value = details[detail];
            if (longestValue < value.length) {
              longestValue = value.length;
            }
          }

          data.push(
            "\x0A",
            `Entry Method: ${details.entry_method.padStart(
              longestValue,
              " "
            )}`.padStart(30, " "),
            "\x0A",
            `Mode: ${details.mode.padStart(longestValue, " ")}`.padStart(
              30,
              " "
            ),
            "\x0A",
            `Card Type: ${details.card_label.padStart(
              longestValue,
              " "
            )}`.padStart(30, " "),
            "\x0A",
            `Auth Code: ${details.auth_code.padStart(
              longestValue,
              " "
            )}`.padStart(30, " "),
            "\x0A"
          );
        }

        if (receiptOptions.footer) {
          data = data.concat(this.receiptFooter(receiptOptions.footer));
        }

        data.push("\x0A\x0A", `Order ID: ${order.id}`, "\x0A", barcode);

        data = data.concat(this.cutAndKick());

        return this.validateForPrint("receipt")
          .then(this.getPrinterConfig)
          .then((config) => {
            return this.$qz.print(config, data);
          })
          .then(() => {
            this.$root.$emit("printed", order);
          });
      }, 1000);
    },

    printReturnReceipt: function (order) {
      this.qzPrinter = this.qzReceiptPrinter;
      const receiptOptions = order.receiptOptions;
      let data = [];

      setTimeout(() => {
        data = data.concat(this.getReceiptHeader(receiptOptions));
        data.push("\x0A", "REFUND RECEIPT", "\x0A", "\x0A");
        data = data.concat(this.getItemsReceiptData(order, true));
        data = data.concat(this.getOrderSummaryData(order));
        const store = this.getStore(order.store_id);
        data.push(
          "\x0A",
          "\x0A",
          `T = ${store.state.abbreviation} TAX of ${
            store.tax_rate * 100
          }%`.padStart(30, " ")
        );
        data = data.concat(this.receiptFooter(receiptOptions.footer));
        data = data.concat([
          "\x0A",
          "\x0A",
          `Order ID: ${order.pos_order_id}`,
          "\x0A",
          `Return ID: ${order.id}`,
          "\x0A",
        ]);
        data = data.concat(this.cutAndKick());
        data.push("\x0A", "\x0A");

        return this.validateForPrint("receipt")
          .then(this.getPrinterConfig)
          .then((config) => {
            return this.$qz.print(config, data);
          })
          .then(() => {
            this.$root.$emit("printed", order);
          });
      }, 1000);
    },

    getReceiptHeader(receiptOptions) {
      return [
        "\x1B\x40",
        "\x1B\x61\x01",
        "\x1b\x45\x01",
        `${receiptOptions.name}`,
        "\x1b\x45\x00",
        "\x1B\x21\x01",
        "\x0A",
        `${receiptOptions.address}`,
        "\x0A",
        `${receiptOptions.city}, ${receiptOptions.state} ${receiptOptions.zipcode}`,
        "\x0A",
        `${receiptOptions.phone}`,
        "\x0A",
        `${new Date().toLocaleString().replace(",", "")}`,
        "\x0A",
      ];
    },

    getItemsReceiptData(order, forReturn = false) {
      const items = order.items;
      let data = [
        "\x1B\x61\x00",
        "-".repeat(56),
        "\x1b\x45\x01",
        "SKU".padEnd(11, " "),
        "Item".padEnd(21, " "),
        "Qty".padEnd(4, " "),
        "Price".padStart(9, " "),
        "Total".padStart(10, " "),
        "\x1b\x45\x00",
        "\x0A",
      ];

      for (let item of items) {
        const title = item.title.slice(0, 20).toUpperCase().padEnd(20, " ");
        const total = forReturn
          ? item.quantity_returned * item.price
          : item.total;
        let quantity = forReturn
          ? item.quantity_returned
          : item.quantity_ordered;
        const originalPrice = item.temp_price
          ? item.temp_price
          : item.original_price;
        const discountAmount =
          item.discount_amount > 0
            ? item.discount_amount_type === "total"
              ? item.discount_amount / quantity
              : item.discount_amount
            : originalPrice - item.price;

        data.push(
          `${
            item.sku ? item.sku.padEnd(11, " ") : " ".repeat(11)
          }${title}${quantity.toString().padStart(4, " ")}${this.formatCurrency(
            originalPrice ? originalPrice : item.price
          )
            .replace("$", "")
            .padStart(10, " ")}${this.formatCurrency(total)
            .replace("$", "")
            .toString()
            .padStart(10, " ")}${
            item.is_taxed ? "\x1b\x45\x01T\x1b\x45\x00" : ""
          }`
        );

        if (item.discount_id || item.discount_amount) {
          const discount = this.discounts.find((d) => d.id == item.discount_id);
          const discountTotal =
            order.discount_amount ||
            item.discount_amount_type === "total" ||
            item.discount_amount_type === "order_total"
              ? discountAmount * quantity
              : item.discount_amount;
          const discountStr = ` *DISCOUNT:${
            discount
              ? discount.discount * 100 + "%"
              : `${this.formatCurrency(discountTotal)}`
          } OFF`;
          data.push(
            "\x0A",
            discountStr +
              `-${this.formatCurrency(discountAmount).replace(
                "$",
                ""
              )}`.padStart(45 - discountStr.length, " ")
          );
        }

        if (item.discount_description) {
          data.push("\x0A", ` *${item.discount_description}`);
        }

        if (item.added_item && !this.classifications_disabled) {
          const className = this.getClassificationName(item.classification_id);
          data.push("\x0A", ` *C: ${className}`);
        }

        quantity =
          !forReturn && item.item_specific_discount_times_applied > 0
            ? item.quantity_ordered -
              item.item_specific_discount_quantity *
                item.item_specific_discount_times_applied
            : quantity;
        if (quantity > 1 || (item.discount_description && quantity > 0)) {
          const eachStr = `${
            item.discount_description ? "  AND " : " *"
          }${quantity}@${this.formatCurrency(item.price)}/ea`;
          data.push(`\x0A${eachStr}`);
        }

        data.push("\x0A");
      }

      return data;
    },

    getOrderSummaryData(order) {
      let data = [];
      const totalLength = this.formatCurrency(order.total).length;
      const tax = order.tax;
      // const taxExempt = order.all_non_taxed_sub_total;
      const total = order.total;

      data.push("-".repeat(56), "\x0A", "\x1B\x61\x30");

      // if (taxExempt > 0) {
      //   if (order.taxable_sub_total) {
      //     data.push(
      //       `Taxable Sub-Total: ${this.formatCurrency(
      //         order.taxable_sub_total
      //       ).padStart(totalLength, " ")}`.padStart(30, " ")
      //     );
      //   }

      //   data.push(
      //     "\x0A",
      //     `Tax Exempt Total: ${this.formatCurrency(taxExempt).padStart(
      //       totalLength,
      //       " "
      //     )}`.padStart(30, " ")
      //   );
      // }

      data.push(
        `Sub-Total: ${this.formatCurrency(order.sub_total).padStart(
          totalLength,
          " "
        )}`.padStart(55, " "),
        "\x0A",
        `Tax: ${this.formatCurrency(tax).padStart(totalLength, " ")}`.padStart(
          55,
          " "
        ),
        "\x0A",
        "\x0A",
        "\x1b\x45\x01",
        `Total: ${this.formatCurrency(total)}`.padStart(55, " "),
        "\x1b\x45\x00",
        "\x0A",
        "\x0A",
        "\x0A"
      );

      if (order.cash && order.cash > 0) {
        data.push(
          `Cash: ${this.formatCurrency(order.cash).padStart(
            totalLength,
            " "
          )}`.padStart(55, " "),
          "\x0A"
        );
      }
      if (order.card && order.card > 0) {
        data.push(
          `Card: ${this.formatCurrency(order.card).padStart(
            totalLength,
            " "
          )}`.padStart(55, " "),
          "\x0A"
        );
      }
      if (order.ebt && order.ebt > 0) {
        data.push(
          `EBT: ${this.formatCurrency(order.ebt).padStart(
            totalLength,
            " "
          )}`.padStart(55, " "),
          "\x0A"
        );
      }

      return data;
    },

    receiptFooter(footer) {
      return ["\x0A\x0A", "\x1B\x61\x31", `${footer}`];
    },

    cutAndKick() {
      return [
        "\x0A",
        "\x0A",
        "\x0A",
        "\x0A",
        "\x0A",
        "\x1D" + "\x56" + "\x00",
        "\x10" + "\x14" + "\x01" + "\x00" + "\x05",
      ];
    },

    startPrint: function () {
      return this.validateForPrint()
        .then(this.getPrinterConfig)
        .then((config) => {
          let data = [];

          this.qzPages.forEach((page) => {
            let pageData = JSON.parse(JSON.stringify(this.qzInput));

            if (typeof page === "string" || page instanceof String) {
              pageData.data = page;
            } else {
              pageData.data = page.data;
            }

            data.push(pageData);
          });

          // data = ['^XA^FO50,50^ADN,36,20^FDRAW ZPL EXAMPLE^FS^XZ'];
          // data = [
          //   "^XA ^FO90,30^ADN,26^FDRAW ZPL EXAMPLE^FS ^FX Third section with barcode. ^BY2,1,40 ^FO90,60^BC^FD12345678^FS ^XZ"
          // ];

          return this.$qz.print(config, data);
        })
        .then(() => {
          this.$emit("printed");
        });
    },

    validateForPrint: function (printType) {
      // TODO extend validation??

      if (
        (printType == "label" && !this.qzLabelPrinter) ||
        (printType == "receipt" && !this.qzReceiptPrinter)
      ) {
        return Promise.reject(["No printer selected", this.qzPrinter]);
      }

      if (!this.qzPrintOptions) {
        return Promise.reject([
          "No printer options available",
          this.qzPrintOptions,
        ]);
      }

      if (!this.qzInput || !this.qzInput.type || !this.qzInput.type.length) {
        return Promise.reject(["No (valid) input available", this.qzInput]);
      }

      // if (!this.qzPages || !this.qzPages.length) {
      //   return Promise.reject(["No (valid) pages available", this.qzPages]);
      // }

      // let invalidPages = [];

      /*
      this.qzPages.forEach(page => {
        if (typeof page === "string" || page instanceof String) {
          if (!page.length) {
            invalidPages.push(page);
          }
        } else if (!page.data || !page.data.length) {
          invalidPages.push(page);
        }
      });

      if (invalidPages.length) {
        return Promise.reject([
          invalidPages.length + " page(s) are invalid",
          invalidPages
        ]);
      }
      */

      return Promise.resolve();
    },

    getPrinterConfig: function () {
      return this.$qz.configs.create(
        this.qzPrinter,
        this.mapConfig(this.qzPrintOptions)
      );
    },

    /* eslint-disable no-unused-vars */

    mapConfig: function (qzPrintOptions) {
      let printerOptions = {};

      return qzPrintOptions;

      /*
      let mapConfig = this.mapConfig;

      Object.keys(qzPrintOptions).map(function(key) {
        if (!qzPrintOptions.hasOwnProperty(key)) {
          console.error("ignoring because not own property", qzPrintOptions);

          return;
        }

        if (!qzPrintOptions[key].hasOwnProperty("value")) {
          console.error("ignoring, because no value key", qzPrintOptions[key]);

          return;
        }

        let value = qzPrintOptions[key].value;

        printerOptions[key] =
          value && Object.prototype.toString.call(value) === "[object Object]"
            ? mapConfig(value)
            : value;
      });

      return printerOptions;
      */
    },

    /* eslint-enable no-unused-vars */
    formatCurrency,
    getClassificationName,
    getStore,
  },

  beforeCreate: function () {
    // SETUP AND INITIALIZATION OF QZ (if needed)
    if (this.$root.$qz) {
      this.$qz = this.$root.$qz;

      return;
    }

    qz.api.setSha256Type((data) => sha256(data));
    qz.api.setPromiseType((resolver) => new Promise(resolver));

    this.$root.$qz = this.$qz = qz;
  },

  created: function () {
    this.$on("connect-connection-changed", this.connectionChanged);
    this.$on("printers-printer-changed", this.printerChanged);
    // this.$on("print-options-options-changed", this.optionsChanged);
    // this.$on("input-options-changed", this.inputChanged);
    // this.$on("input-pages-changed", this.inputPagesChanged);

    this.$root.$on("print-label", (item) => {
      this.printLabel(item);
    });
    this.$root.$on("print-order-receipt", (order) => {
      this.printOrderReceipt(order);
    });
    this.$root.$on("print-return-receipt", (order) => {
      this.printReturnReceipt(order);
    });

    this.$store.dispatch("updateQzPanel", false);
  },

  beforeDestroy: function () {
    this.$off("connect-connection-changed", this.connectionChanged);
    this.$off("printers-printer-changed", this.printerChanged);
    // this.$off("print-options-options-changed", this.optionsChanged);
    // this.$off("input-options-changed", this.inputChanged);
    // this.$off("input-pages-changed", this.inputPagesChanged);
  },

  provide: function () {
    return {
      $qz: this.$root.$qz,
      $qzRoot: this,
      $qzConfig: this.$qzConfig,
    };
  },

  watch: {
    qzPanel: function (value) {
      this.show = value;
      if (value) {
        this.$refs.printSettingsModal.openModal();
      } else {
        this.$refs.printSettingsModal.closeModal();
      }
    },
  },
};
</script>
