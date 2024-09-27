const { mount, shallowMount, createLocalVue } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import Index from "../Index";
import ModalWall from "@/components/ModalWall";
import PosSearch from "@/components/PosSearch";
import LoadingModal from "@/components/LoadingModal";
import ReceiptFailedModal from "@/components/ReceiptFailedModal";
import Vuex from "vuex";
import { CurrencyInput } from "vue-currency-input";
import moment from "moment";

describe("PosIndex", () => {
  let getters;
  let actions;
  let store;
  let _store;

  beforeEach(() => {
    _store = {
      id: 1,
      name: "Test Store",
      receipt_option: {},
      state: {},
    };

    getters = {
      stationsVisible: () => [],
      storesVisible: () => [_store],
      currentUser: () => {
        return {
          id: 1,
        };
      },
      posStore: () => {
        return _store;
      },
      posStation: () => {
        return {};
      },
      paymentPartner: () => false,
      hide_pos_sales: () => true,
      classifications_disabled: () => false,
    };
    actions = {
      getPreferences: jest.fn(),
      selectStore: jest.fn(),
      selectStation: jest.fn(),
      getDailySalesReportData: jest.fn().mockResolvedValue({
        order_totals: { total: 10000 },
        return_totals: { total: 5000 },
      }),
    };
    store = new Vuex.Store({
      getters,
      actions,
    });
  });

  it("can getPreferences", () => {
    getters.posStore = () => {};
    getters.storesVisible = () => [];
    store.hotUpdate({ getters });

    mount(Index, { store });

    expect(actions.getPreferences).toHaveBeenCalledTimes(1);
  });

  it("can select a store and get daily sales for store", async () => {
    getters.posStore = () => {};
    getters.hide_pos_sales = () => false;
    store.hotUpdate({ getters, actions });
    const wrapper = mount(Index, { store });

    expect(wrapper.find("[data-test=pos-ui]").exists()).toBeFalsy();
    expect(
      wrapper.find("[data-test=store-select-modal]").isVisible()
    ).toBeTruthy();

    await wrapper
      .find("[data-test=store-select-modal]")
      .setData({ show: true });
    await wrapper.findComponent({ ref: "storeModal" }).setData({ show: true });
    wrapper.find("[data-test=store-select-modal-store-1]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.selectedStore).toEqual(_store);
    expect(actions.getDailySalesReportData).toHaveBeenCalledTimes(1);
    expect(actions.getDailySalesReportData).toHaveBeenCalledWith(
      expect.any(Object),
      {
        storeId: 1,
        date: moment().format(),
        options: {
          hideMessages: true,
        },
      }
    );
    expect(actions.selectStore).toHaveBeenCalledTimes(1);
    expect(actions.selectStore).toHaveBeenCalledWith(
      expect.any(Object),
      _store
    );
    expect(wrapper.vm.dailySales).toEqual(100);
    expect(wrapper.vm.dailyReturns).toEqual(50);
    expect(wrapper.find("[data-test=pos-ui]").isVisible()).toBeTruthy();
    expect(wrapper.find("[data-test=pos-order-ui]").exists()).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-daily-sales-tile]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-daily-returns-tile]").isVisible()
    ).toBeTruthy();
  });

  it("can hide daily sales", async () => {
    const wrapper = mount(Index, { store });

    jest.spyOn(wrapper.vm, "getTodaysSales");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.getTodaysSales).toHaveBeenCalledTimes(1);
    expect(actions.getDailySalesReportData).toHaveBeenCalledTimes(0);
    expect(
      wrapper.find("[data-test=pos-daily-sales-tile]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-daily-returns-tile]").exists()
    ).toBeFalsy();
  });

  it("can change store", async () => {
    const wrapper = mount(Index, { store });
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.selectedStore).toEqual(_store);
    expect(wrapper.find("[data-test=pos-standby]").isVisible()).toBeTruthy();
    wrapper.find("[data-test=pos-changeStore-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.selectedStore).toEqual(null);
  });

  it("can select a station", async () => {
    const station = {
      id: 1,
      name: "Test Station",
      store_id: 1,
      drawer_balance: 100,
    };
    getters.stationsVisible = () => [station];
    store.hotUpdate({ getters });
    const wrapper = mount(Index, { store });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=station-select-modal]").isVisible()
    ).toBeTruthy();

    await wrapper
      .find("[data-test=station-select-modal]")
      .setData({ show: true });
    await wrapper
      .findComponent({ ref: "stationModal" })
      .setData({ show: true });
    wrapper.find("[data-test=station-select-modal-station-0]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.selectedStation).toEqual(station);
    expect(actions.selectStation).toHaveBeenCalledTimes(1);
    expect(actions.selectStation).toHaveBeenCalledWith(
      expect.any(Object),
      station
    );
    expect(wrapper.find("[data-test=pos-ui]").isVisible()).toBeTruthy();
    expect(wrapper.find("[data-test=pos-order-ui]").exists()).toBeFalsy();
  });

  it("can change station", async () => {
    const wrapper = mount(Index, { store });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=pos-changeStation-button]").exists()
    ).toBeFalsy();

    await wrapper.setData({ selectedStation: { id: 1 } });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=pos-changeStation-button]").isVisible()
    ).toBeTruthy();

    wrapper.find("[data-test=pos-changeStation-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.selectedStation).toEqual(null);
  });

  it("can add scratch item", async () => {
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=pos-order-ui]").exists()).toBeFalsy();

    wrapper.find("[data-test=pos-addScratchItem-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=pos-order-ui]").isVisible()).toBeTruthy();
    expect(wrapper.vm.addedItemNumber).toEqual(1);
    expect(wrapper.vm.items).toHaveLength(1);
    const scratchItem = {
      id: `addedItem_1`,
      title: `Added Item #1`,
      price: 0,
      item_images: [],
      quantity_ordered: 1,
      sku: "",
      added_item: true,
      classification_id: null,
      discount_amount: null,
    };
    expect(wrapper.vm.items[0]).toEqual(scratchItem);
    expect(wrapper.vm.selectedItem).toEqual(scratchItem);
    expect(wrapper.vm.editMode).toEqual(false);
  });

  it("can add inventory item", async () => {
    window.scrollTo = jest.fn();
    actions.calculateOrderTotals = jest
      .fn()
      .mockResolvedValue({ item_totals: { totals: [] } });
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
    });
    wrapper.vm.scrollDown = jest.fn();
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=pos-order-ui]").exists()).toBeFalsy();

    const item = {
      id: 1,
      title: "Test",
      price: 1000,
      item_images: [],
      sku: "0123456789",
      classification_id: 1,
    };
    wrapper.vm.addToCart(item);
    await wrapper.vm.$nextTick();
    await new Promise((r) => setTimeout(r, 400));

    expect(wrapper.find("[data-test=pos-order-ui]").isVisible()).toBeTruthy();
    expect(wrapper.vm.items).toHaveLength(1);
    expect(wrapper.vm.items[0]).toEqual(item);
    expect(wrapper.vm.selectedItem).toEqual(
      Object.assign(
        {
          original_price: 1000,
          temp_price: 1000,
          quantity_ordered: 1,
          discount_amount: null,
        },
        item
      )
    );
    expect(wrapper.vm.scrollDown).toHaveBeenCalledTimes(1);
    expect(actions.calculateOrderTotals).toHaveBeenCalledTimes(1);
  });

  it("shows consignment modal when required", async () => {
    const localVue = createLocalVue();
    localVue.filter("percent", (value, decimals) => {
      value = value === null || isNaN(value) ? 0 : value;

      return `${value.toFixed(decimals)}%`;
    });

    const data = () => {
      return {
        selectedItem: {
          id: 1,
          title: "Test",
          price: 1000,
          original_price: 1000,
          item_images: [],
          sku: "0123456789",
          classification_id: 1,
          consignor_id: 1,
          consignment_fee: 100,
        },
      };
    };
    actions.calculateConsignmentFee = jest.fn().mockResolvedValue({
      consignment_fee: 75,
      consignment_fee_percentage: 0.1,
    });
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
        "currency-input": CurrencyInput,
      },
      data,
      localVue,
    });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.findComponent("[data-test=consignmentFee-modal]").vm.show
    ).toBeFalsy();

    wrapper.vm.selectedItem.price = 750;
    await wrapper.vm.$nextTick();
    await Promise.resolve();

    expect(actions.calculateConsignmentFee).toHaveBeenCalledTimes(1);
    expect(actions.calculateConsignmentFee).toHaveBeenCalledWith(
      expect.any(Object),
      {
        consignor_id: 1,
        price: 750,
      }
    );
    expect(wrapper.vm.consignmentFeeEstimate).toEqual(75);
    expect(wrapper.vm.consingmentFeePercentage).toEqual(0.1);
    expect(
      wrapper.findComponent("[data-test=consignmentFee-modal]").vm.show
    ).toBeTruthy();
  });

  it("card modal works as expected", async () => {
    getters.paymentPartner = () => true;
    getters.qzReadyToPrint = () => true;
    getters.qzReceiptPrinter = () => true;
    actions.createOrder = jest.fn().mockResolvedValue({});
    store.hotUpdate({ getters, actions });
    const checkoutSpy = jest.spyOn(Index.methods, "checkout");
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
        LoadingModal: LoadingModal,
      },
      mocks: {
        $toasted: {
          show: jest.fn(),
        },
      },
    });
    wrapper.vm.calculatePayment = jest.fn();
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.cardTypeRequired).toBeFalsy();
    expect(
      wrapper.findComponent("[data-test=card-type-modal]").vm.show
    ).toBeFalsy();

    wrapper.vm.orderForm.card = 500;
    wrapper.vm.$options.methods.checkout.call(wrapper.vm);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.cardTypeRequired).toBeTruthy();
    expect(
      wrapper.findComponent("[data-test=card-type-modal]").vm.show
    ).toBeTruthy();

    wrapper.find("[data-test=credit-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(checkoutSpy).toHaveBeenCalledTimes(2);
    expect(wrapper.vm.calculatePayment).toHaveBeenCalledTimes(1);
    expect(actions.createOrder).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith("Creating order...", {
      type: "success",
    });
    expect(wrapper.vm.isDebit).toBeFalsy();

    checkoutSpy.mockClear();
    actions.createOrder.mockClear();
    wrapper.vm.$toasted.show.mockClear();
    wrapper.vm.isDebit = null;
    wrapper.vm.$options.methods.checkout.call(wrapper.vm);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.cardTypeRequired).toBeTruthy();
    expect(
      wrapper.findComponent("[data-test=card-type-modal]").vm.show
    ).toBeTruthy();

    wrapper.find("[data-test=debit-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(checkoutSpy).toHaveBeenCalledTimes(2);
    expect(actions.createOrder).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith("Creating order...", {
      type: "success",
    });
    expect(wrapper.vm.isDebit).toBeTruthy();
  });

  it("order summary modal works as expected", async () => {
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
    });
    await wrapper.vm.$nextTick();

    wrapper.findComponent("[data-test=order-summary-modal]").vm.openModal();
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=order-summary-modal-change-note]").text()
    ).toContain("No Change Due");

    wrapper.vm.orderForm.change = 100;
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=order-summary-modal-change-note]").text()
    ).toContain("$1.00 Change Due");

    const spy = jest.fn();
    wrapper.vm.$root.$on("reload-pos", spy);
    wrapper
      .find("[data-test=order-summary-modal-newOrder-button]")
      .trigger("click");
    await wrapper.vm.$nextTick();

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it("checkout error handling works as expected", async () => {
    getters.qzReadyToPrint = () => false;
    getters.qzReceiptPrinter = () => false;
    actions.updateQzPanel = jest.fn();
    store.hotUpdate({ getters });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: true,
        "modal-wall": ModalWall,
      },
      mocks: {
        $toasted: {
          show: jest.fn(),
        },
      },
    });
    await wrapper.vm.$nextTick();

    wrapper.vm.checkout();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "QZ Tray is not connected.",
      { type: "error" }
    );
    expect(actions.updateQzPanel).toHaveBeenCalledTimes(1);

    wrapper.vm.$toasted.show.mockClear();
    actions.updateQzPanel.mockClear();
    getters.qzReadyToPrint = () => true;
    store.hotUpdate({ getters });
    await wrapper.vm.$nextTick();

    wrapper.vm.checkout();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "You do not currently have a receipt printer selected, select one and try again.",
      { type: "error" }
    );
    expect(actions.updateQzPanel).toHaveBeenCalledTimes(1);

    wrapper.vm.$toasted.show.mockClear();
    getters.qzReceiptPrinter = () => true;
    store.hotUpdate({ getters });
    wrapper.vm.orderForm.amount_paid = 1000;
    wrapper.vm.orderForm.total = 1500;
    await wrapper.vm.$nextTick();

    wrapper.vm.checkout();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "Customer has not paid full amount of order.",
      {
        type: "error",
      }
    );
  });

  it("can checkout", async () => {
    getters.qzReadyToPrint = () => true;
    getters.qzReceiptPrinter = () => true;
    actions.createOrder = jest.fn().mockResolvedValue({});
    store.hotUpdate({ getters, actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: true,
        "modal-wall": ModalWall,
        LoadingModal: LoadingModal,
      },
      mocks: {
        $toasted: {
          show: jest.fn(),
        },
      },
    });
    wrapper.vm.items = [
      {
        id: 1,
        sku: "1234567890",
        price: 1000,
        temp_price: 1000,
        original_price: 1000,
        quantity_ordered: 1,
        title: "Test Item",
      },
    ];
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.validItems).toHaveLength(1);

    const receiptSpy = jest.fn();
    wrapper.vm.$root.$on("print-order-receipt", receiptSpy);
    wrapper.vm.checkout();
    await wrapper.vm.$nextTick();
    wrapper.vm.$root.$emit("printed");

    expect(receiptSpy).toHaveBeenCalledTimes(1);
    expect(receiptSpy).toHaveBeenCalledWith(
      Object.assign(wrapper.vm.orderForm, wrapper.vm.receiptData)
    );
    expect(actions.createOrder).toHaveBeenCalledTimes(1);
    expect(actions.createOrder).toHaveBeenCalledWith(
      expect.any(Object),
      expect.objectContaining({
        store_id: 1,
        items: wrapper.vm.items,
        created_by: 1,
      })
    );
    expect(
      wrapper.findComponent("[data-test=order-summary-modal]").vm.show
    ).toBeTruthy();
  });

  it("fails checkout as expected", async () => {
    getters.qzReadyToPrint = () => true;
    getters.qzReceiptPrinter = () => true;
    actions.createOrder = jest.fn().mockRejectedValue(true);
    store.hotUpdate({ getters, actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: true,
        "modal-wall": ModalWall,
        LoadingModal: LoadingModal,
        ReceiptFailedModal: ReceiptFailedModal,
      },
      mocks: {
        $toasted: {
          show: jest.fn(),
        },
      },
    });
    await wrapper.vm.$nextTick();

    const modalSpy = jest.fn();
    wrapper.findComponent("[data-test=receipt-failed-modal]").vm.openModal =
      modalSpy;
    wrapper.vm.checkout();
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.createOrder).toHaveBeenCalledTimes(1);
    expect(modalSpy).toHaveBeenCalledTimes(1);
  });

  it("listens to remove-item event", async () => {
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: true,
        "modal-wall": ModalWall,
      },
    });
    wrapper.vm.calculateOrderTotals = jest.fn();
    wrapper.vm.items = [
      {
        id: 1,
        title: "Test Item",
        quantity_ordered: 1,
      },
      {
        id: 2,
        title: "Test Item 2",
        quantity_ordered: 2,
      },
    ];
    await wrapper.vm.$nextTick();

    wrapper.vm.$root.$emit("remove-item", wrapper.vm.items[1]);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.items[1].quantity_ordered).toEqual(1);
    expect(wrapper.vm.mostRecentCartItem).toEqual(wrapper.vm.items[1]);
    expect(wrapper.vm.selectedItem).toEqual(wrapper.vm.items[1]);

    wrapper.vm.$root.$emit("remove-item", wrapper.vm.items[1]);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.items).toHaveLength(1);
    expect(wrapper.vm.mostRecentCartItem).toEqual(wrapper.vm.items[0]);
    expect(wrapper.vm.selectedItem).toEqual(wrapper.vm.items[0]);
    expect(wrapper.vm.calculateOrderTotals).toHaveBeenCalledTimes(2);
  });

  it("listens to order-wide-discount event", async () => {
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: true,
        "modal-wall": ModalWall,
      },
    });
    wrapper.vm.calculatePriceForAllItems = jest.fn();
    wrapper.vm.items = [
      {
        id: 1,
        title: "Test Item",
        quantity_ordered: 1,
      },
      {
        id: 2,
        title: "Test Item 2",
        quantity_ordered: 2,
      },
    ];
    await wrapper.vm.$nextTick();

    wrapper.vm.$root.$emit("order-wide-discount", 1);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.items[0].discount_id).toEqual(1);
    expect(wrapper.vm.items[1].discount_id).toEqual(1);
    expect(wrapper.vm.calculatePriceForAllItems).toHaveBeenCalledTimes(1);

    wrapper.vm.$root.$emit("order-wide-discount", null);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.items[0].discount_id).toEqual(null);
    expect(wrapper.vm.items[1].discount_id).toEqual(null);
  });

  it("listens to reload-pos event", async () => {
    actions.calculatePosPayment = jest.fn();
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
    });
    wrapper.vm.addedItemNumber = 5;
    wrapper.vm.orderForm.cash = 1000;
    wrapper.vm.orderForm.card = 1000;
    wrapper.vm.orderForm.ebt = 1000;
    wrapper.vm.orderForm.sub_total = 3000;
    wrapper.vm.orderForm.tax = 250;
    wrapper.vm.orderForm.total = 3250;
    wrapper.vm.items = [
      {
        id: 1,
        title: "Test Item",
        quantity_ordered: 1,
      },
      {
        id: 2,
        title: "Test Item 2",
        quantity_ordered: 2,
      },
    ];
    wrapper.vm.paymentMethod = "card";
    wrapper.vm.noTax = true;
    wrapper.vm.isDebit = true;
    wrapper.vm.checkingOut = true;
    await wrapper.vm.$nextTick();

    wrapper.vm.getTodaysSales = jest.fn();
    wrapper.vm.$root.$emit("reload-pos");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.addedItemNumber).toEqual(0);
    expect(wrapper.vm.orderForm.cash).toEqual(0);
    expect(wrapper.vm.orderForm.card).toEqual(0);
    expect(wrapper.vm.orderForm.ebt).toEqual(0);
    expect(wrapper.vm.orderForm.sub_total).toEqual(0);
    expect(wrapper.vm.orderForm.tax).toEqual(0);
    expect(wrapper.vm.orderForm.total).toEqual(0);
    expect(wrapper.vm.items).toEqual([]);
    expect(wrapper.vm.paymentMethod).toEqual(null);
    expect(wrapper.vm.noTax).toEqual(false);
    expect(wrapper.vm.isDebit).toEqual(null);
    expect(wrapper.vm.checkingOut).toEqual(false);
    expect(wrapper.vm.getTodaysSales).toHaveBeenCalledTimes(1);
  });

  it("can calculatePriceForAllItems", async () => {
    actions.calculateOrderTotals = jest
      .fn()
      .mockResolvedValue({ item_totals: { totals: [] } });
    actions.calculatePriceForMultipleItems = jest.fn().mockResolvedValue([
      {
        id: 1,
        price: 2000,
      },
      {
        id: 2,
        price: 1000,
      },
    ]);
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
    });
    await wrapper.vm.$nextTick();

    wrapper.vm.calculatePriceForAllItems();
    await wrapper.vm.$nextTick();
    await new Promise((r) => setTimeout(r, 260));

    expect(actions.calculatePriceForMultipleItems).toHaveBeenCalledTimes(0);

    wrapper.vm.items = [
      {
        id: 1,
        title: "Test Item",
        sku: "1234567890",
        quantity_ordered: 1,
        price: 1500,
      },
      {
        id: 2,
        title: "Test Item 2",
        sku: "1234567891",
        quantity_ordered: 1,
        price: 750,
      },
    ];
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.validItems).toHaveLength(2);

    wrapper.vm.calculatePriceForAllItems();
    await wrapper.vm.$nextTick();
    await new Promise((r) => setTimeout(r, 660));

    expect(actions.calculatePriceForMultipleItems).toHaveBeenCalledTimes(1);
    expect(actions.calculatePriceForMultipleItems).toHaveBeenCalledWith(
      expect.any(Object),
      [
        {
          id: 1,
          price: 1500,
          discount_id: undefined,
          discount_amount: null,
          discount_amount_type: undefined,
          quantity_ordered: 1,
        },
        {
          id: 2,
          price: 750,
          discount_id: undefined,
          discount_amount: null,
          discount_amount_type: undefined,
          quantity_ordered: 1,
        },
      ]
    );
    expect(actions.calculateOrderTotals).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.items[0].price).toEqual(2000);
    expect(wrapper.vm.items[1].price).toEqual(1000);
  });

  it("can caclulatePayment", async () => {
    actions.calculatePosPayment = jest.fn().mockResolvedValue({
      amount_remaining: 1000,
      amount_paid: 1500,
      change: 0,
    });
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
    });
    await wrapper.vm.$nextTick();

    wrapper.vm.calculatePayment();
    await wrapper.vm.$nextTick();

    expect(actions.calculatePosPayment).toHaveBeenCalledTimes(0);

    wrapper.vm.orderForm.total = 2500;
    wrapper.vm.orderForm.cash = 500;
    await wrapper.vm.$nextTick();

    expect(actions.calculatePosPayment).toHaveBeenCalledTimes(1);
    expect(actions.calculatePosPayment).toHaveBeenCalledWith(
      expect.any(Object),
      {
        total: 2500,
        cash: 500,
        card: 0,
        ebt: 0,
      }
    );

    wrapper.vm.orderForm.card = 500;
    wrapper.vm.orderForm.ebt = 500;
    await wrapper.vm.$nextTick();

    expect(actions.calculatePosPayment).toHaveBeenCalledTimes(3);
    expect(wrapper.vm.orderForm.amount_paid).toEqual(1500);
    expect(wrapper.vm.orderForm.amount_remaining).toEqual(1000);
  });

  it("can calculateOrderTotals", async () => {
    const mockResolve = {
      item_totals: {
        totals: [
          {
            id: 1,
            price: 3000,
            is_taxed: true,
          },
          {
            id: 2,
            price: 1500,
            is_taxed: true,
          },
        ],
        sub_total: 4500,
      },
      sub_total: 4500,
      prior_sub_total: 4500,
      taxable_sub_total: 4500,
      ebt_sub_total: 0,
      non_taxed_sub_total: 0,
      tax: 500,
      total: 5000,
      ebt_eligible: false,
    };
    actions.calculateOrderTotals = jest.fn().mockResolvedValue(mockResolve);
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Index, {
      store,
      stubs: {
        PosRight: true,
        PosSearch: PosSearch,
        "modal-wall": ModalWall,
      },
      mocks: {
        $toasted: {
          show: jest.fn(),
        },
      },
    });
    wrapper.vm.items = [
      {
        id: 1,
        price: 1500,
        quantity_ordered: 2,
        title: "Test Item",
      },
      {
        id: 2,
        price: 1500,
        quantity_ordered: 1,
        title: "Test Item 2",
      },
    ];
    wrapper.vm.paymentMethod = "ebt";
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.validItems).toHaveLength(2);

    wrapper.vm.calculateOrderTotals();
    await wrapper.vm.$nextTick();
    await new Promise((r) => setTimeout(r, 410));

    expect(actions.calculateOrderTotals).toHaveBeenCalledTimes(1);
    expect(actions.calculateOrderTotals).toHaveBeenCalledWith(
      expect.any(Object),
      {
        items: [
          {
            id: 1,
            price: 1500,
            discount_id: null,
            discount_amount: null,
            quantity_ordered: 2,
            added_item: undefined,
            classification_id: null,
          },
          {
            id: 2,
            price: 1500,
            discount_id: null,
            discount_amount: null,
            quantity_ordered: 1,
            added_item: undefined,
            classification_id: null,
          },
        ],
        is_ebt: true,
        is_taxed: true,
        discount_amount: 0,
      }
    );
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "This order is not eligible for EBT. Please try cash or card.",
      { type: "error" }
    );

    actions.calculateOrderTotals.mockClear();
    wrapper.vm.calculateOrderTotals();
    await wrapper.vm.$nextTick();
    await new Promise((r) => setTimeout(r, 410));

    expect(actions.calculateOrderTotals).toHaveBeenCalledWith(
      expect.any(Object),
      expect.objectContaining({ is_ebt: false })
    );
    expect(wrapper.vm.orderForm.amount_remaining).toEqual(mockResolve.total);
    expect(wrapper.vm.orderForm.sub_total).toEqual(mockResolve.sub_total);
    expect(wrapper.vm.orderForm.prior_sub_total).toEqual(
      mockResolve.prior_sub_total
    );
    expect(wrapper.vm.orderForm.taxable_sub_total).toEqual(
      mockResolve.taxable_sub_total
    );
    expect(wrapper.vm.orderForm.tax).toEqual(mockResolve.tax);
    expect(wrapper.vm.orderForm.total).toEqual(mockResolve.total);
    expect(wrapper.vm.items[0].total).toEqual(3000);
    expect(wrapper.vm.items[0].is_taxed).toEqual(true);
    expect(wrapper.vm.items[1].total).toEqual(1500);
    expect(wrapper.vm.items[1].is_taxed).toEqual(true);
  });
});
