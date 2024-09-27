const { shallowMount, createLocalVue } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import Returns from "../Returns";
import BlankState from "@/components/BlankState";
import Vuex from "vuex";

describe("PosReturns", () => {
  let getters;
  let actions;
  let store;
  let _store;
  let mocks = {
    $route: {
      params: {
        order: null
      }
    }
  };
  let localVue = createLocalVue();

  beforeEach(() => {
    _store = {
      id: 1,
      name: "Test Store",
      receipt_option: {},
      state: {}
    };
    getters = {
      currentUser: () => {
        return {
          id: 1
        };
      },
      posStore: () => {
        return _store;
      },
      posStation: () => {
        return {
          id: 1
        };
      }
    };
    actions = {
      getPreferences: jest.fn()
    };
    store = new Vuex.Store({ actions, getters });

    localVue.filter("truncate", (value, length) => {
      length = length || 15;
      if (!value || typeof value !== "string") return "";
      if (value.length <= length) return value;
      return value.substring(0, length) + "...";
    });
  });

  it("can getPreferences", async () => {
    shallowMount(Returns, { store, mocks });

    expect(actions.getPreferences).toHaveBeenCalledTimes(1);
  });

  it("can show stand by view", async () => {
    const wrapper = shallowMount(Returns, { store, mocks });

    expect(wrapper.find("[data-test=returns-home]").isVisible()).toBeTruthy();
    expect(wrapper.find("[data-test=returns-main]").exists()).toBeFalsy();
  });

  it("can show main view", async () => {
    const data = () => {
      return {
        order: {},
        selectedItem: {}
      };
    };
    const wrapper = shallowMount(Returns, { store, mocks, data, localVue });

    expect(wrapper.find("[data-test=returns-home]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=returns-main]").isVisible()).toBeTruthy();
  });

  it("can go to POS", async () => {
    mocks.$router = { push: jest.fn() };
    const wrapper = shallowMount(Returns, { store, mocks });

    wrapper.vm.goToPos();
    await wrapper.vm.$nextTick();

    expect(mocks.$router.push).toHaveBeenCalledTimes(1);
    expect(mocks.$router.push).toHaveBeenCalledWith({ name: "pos.index" });
  });

  it("can go to orders", async () => {
    mocks.$router = { push: jest.fn() };
    const wrapper = shallowMount(Returns, {
      store,
      mocks,
      stubs: { "blank-state": BlankState }
    });

    wrapper.find("[data-test=returns-goToOrders-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(mocks.$router.push).toHaveBeenCalledTimes(1);
    expect(mocks.$router.push).toHaveBeenCalledWith({ name: "pos.orders" });
  });

  it("can trigger search-pos-orders", async () => {
    const wrapper = shallowMount(Returns, { store, mocks });
    const spy = jest.fn();
    wrapper.vm.$root.$on("search-pos-orders", spy);

    wrapper.vm.searchPosOrders();
    await wrapper.vm.$nextTick();

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it("requires action on selectedItem", async () => {
    const data = () => {
      return {
        order: {},
        selectedItem: {
          id: 1,
          title: "Test Item",
          action: 0
        }
      };
    };
    const wrapper = shallowMount(Returns, { store, mocks, data, localVue });

    expect(
      wrapper.find("[data-test=returns-right-action-indicator]").exists()
    ).toBeFalsy();

    wrapper.vm.selectedItem = {};
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=returns-right-action-indicator]").isVisible()
    ).toBeTruthy();
  });

  it("requires quantity_returned on selectedItem", async () => {
    const data = () => {
      return {
        order: {},
        selectedItem: {
          id: 1,
          title: "Test Item",
          action: 0,
          quantity_returned: null
        }
      };
    };
    const wrapper = shallowMount(Returns, { store, mocks, data, localVue });

    expect(
      wrapper.find("[data-test=returns-right-quantity-indicator]").isVisible()
    ).toBeTruthy();

    wrapper
      .find("[data-test=returns-right-quantityReturned-input]")
      .setValue("1");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.selectedItem.quantity_returned).toEqual(1);
    expect(
      wrapper.find("[data-test=returns-right-quantity-indicator]").exists()
    ).toBeFalsy();
  });

  it("can calculatePayment", async () => {
    actions.calculatePosPayment = jest.fn().mockResolvedValue({
      amount_remaining: 1000,
      amount_paid: 1000,
      change: 0
    });
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Returns, { store, mocks });

    wrapper.vm.returnForm.total = 2000;
    wrapper.vm.returnForm.cash = 500;
    await wrapper.vm.$nextTick();

    expect(actions.calculatePosPayment).toHaveBeenCalledTimes(1);
    actions.calculatePosPayment.mockClear();

    wrapper.vm.returnForm.card = 250;
    await wrapper.vm.$nextTick();

    expect(actions.calculatePosPayment).toHaveBeenCalledTimes(1);
    actions.calculatePosPayment.mockClear();

    wrapper.vm.returnForm.ebt = 250;
    await wrapper.vm.$nextTick();

    expect(actions.calculatePosPayment).toHaveBeenCalledTimes(1);
    expect(actions.calculatePosPayment).toHaveBeenCalledWith(
      expect.any(Object),
      { total: 2000, cash: 500, card: 250, ebt: 250 }
    );
    expect(wrapper.vm.returnForm.amount_remaining).toEqual(1000);
    expect(wrapper.vm.returnForm.amount_paid).toEqual(1000);
  });

  it("can calculateRefund", async () => {
    actions.calculatePosPayment = jest.fn();
    const refundMath = {
      sub_total: 1500,
      taxable_sub_total: 1500,
      ebt_sub_total: 0,
      non_taxed_sub_total: 0,
      all_non_taxed_sub_total: 0,
      tax: 250,
      total: 1750
    };
    actions.calculateRefund = jest.fn().mockResolvedValue(refundMath);
    store.hotUpdate({ actions });
    mocks.$toasted = { show: jest.fn() };
    const wrapper = shallowMount(Returns, { store, mocks, localVue });
    wrapper.vm.order = {
      id: 1,
      cash: 1000,
      card: 750,
      sub_total: 1500,
      tax: 250,
      total: 1750,
      pos_order_items: [
        {
          id: 1,
          title: "Test Item",
          price: 750,
          action: null,
          quantity_left_to_return: 1
        },
        {
          id: 2,
          title: "Test Item 2",
          price: 750,
          action: 1,
          quantity_returned: 1,
          quantity_left_to_return: 1
        }
      ]
    };
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.validItems).toHaveLength(1);

    wrapper
      .find("[data-test=returns-right-quantityReturned-input]")
      .setValue(2);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "Cannot return more items than is left to return.",
      {
        type: "info"
      }
    );
    expect(wrapper.vm.selectedItem.quantity_returned).toEqual(1);
    expect(wrapper.vm.itemReadyForCalculation).toEqual(false);

    wrapper.vm.selectedItem.action = 1;
    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 260));

    expect(wrapper.vm.validItems).toHaveLength(2);
    expect(wrapper.vm.itemReadyForCalculation).toEqual(true);
    expect(actions.calculateRefund).toHaveBeenCalledTimes(1);
    expect(actions.calculateRefund).toHaveBeenCalledWith(expect.any(Object), {
      orderId: 1,
      items: wrapper.vm.validItems
    });
    expect(wrapper.vm.returnForm).toMatchObject(refundMath);
    expect(wrapper.vm.returnForm.amount_remaining).toEqual(refundMath.total);

    actions.calculateRefund.mockClear();
    wrapper.vm.$options.watch["selectedItem.quantity_returned"].call(
      wrapper.vm,
      1
    );
    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 260));

    expect(actions.calculateRefund).toHaveBeenCalledTimes(1);
  });

  it("can createReturn", async () => {
    actions.calculatePosPayment = jest.fn();
    actions.calculateRefund = jest.fn();
    actions.createReturn = jest.fn();
    actions.updateQzPanel = jest.fn();
    getters.qzReceiptPrinter = () => true;
    getters.qzReadyToPrint = () => true;
    store.hotUpdate({ actions });
    mocks.$toasted = { show: jest.fn() };
    const wrapper = shallowMount(Returns, { store, mocks, localVue });
    wrapper.vm.order = {
      id: 1,
      cash: 1000,
      card: 750,
      sub_total: 1500,
      tax: 250,
      total: 1750,
      pos_order_items: [
        {
          id: 1,
          title: "Test Item",
          price: 750,
          action: 1,
          quantity_returned: 1,
          quantity_left_to_return: 1
        },
        {
          id: 2,
          title: "Test Item 2",
          price: 750,
          action: 1,
          quantity_returned: 1,
          quantity_left_to_return: 1
        }
      ]
    };
    await wrapper.vm.$nextTick();

    const spy = jest.fn();
    const receiptSpy = jest.fn();
    wrapper.vm.$root.$on("reload-pos", spy);
    wrapper.vm.$root.$on("print-return-receipt", receiptSpy);
    wrapper.vm.clearRouteQuery = jest.fn();
    wrapper.vm.createReturn();
    await wrapper.vm.$nextTick();

    wrapper.vm.$root.$emit("printed");
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(2);
    expect(wrapper.vm.$toasted.show).toHaveBeenNthCalledWith(
      1,
      "Creating return...",
      { type: "success" }
    );
    expect(wrapper.vm.$toasted.show).toHaveBeenNthCalledWith(
      2,
      "Return Created!",
      { type: "success" }
    );
    expect(actions.createReturn).toHaveBeenCalledTimes(1);
    expect(spy).toHaveBeenCalledTimes(1);
    expect(receiptSpy).toHaveBeenCalledTimes(1);
    expect(receiptSpy).toHaveBeenCalledWith(
      Object.assign(wrapper.vm.returnForm, wrapper.vm.receiptData)
    );
    expect(wrapper.vm.clearRouteQuery).toHaveBeenCalledTimes(1);
  });

  it("can reload-pos", async () => {
    actions.calculatePosPayment = jest.fn();
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Returns, { store, mocks });
    const originalForm = { ...wrapper.vm.returnForm };
    wrapper.vm.selectItem = jest.fn();
    wrapper.vm.order = {
      id: 1,
      cash: 500,
      sub_total: 500,
      total: 500,
      pos_order_items: [{ id: 1, title: "Test" }]
    };
    wrapper.vm.finalize = true;
    wrapper.vm.checkingOut = true;
    wrapper.vm.returnForm.store_id = 1;
    wrapper.vm.returnForm.pos_order_id = 1;
    wrapper.vm.returnForm.cash = 1000;
    wrapper.vm.returnForm.card = 1000;
    wrapper.vm.returnForm.sub_total = 2000;
    wrapper.vm.returnForm.tax = 250;
    wrapper.vm.returnForm.total = 2250;
    await wrapper.vm.$nextTick();

    wrapper.vm.$root.$emit("reload-pos");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.order).toEqual(null);
    expect(wrapper.vm.orderItems).toEqual([]);
    expect(wrapper.vm.finalize).toEqual(false);
    expect(wrapper.vm.checkingOut).toEqual(false);
    expect(wrapper.vm.returnForm).toEqual(originalForm);
  });
});
