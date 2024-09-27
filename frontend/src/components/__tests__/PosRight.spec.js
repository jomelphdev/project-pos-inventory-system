import { createLocalVue, mount, shallowMount } from "@vue/test-utils";
import { jest } from "@jest/globals";
import PosRight from "../PosRight";
import store from "@/store";
import Vuex from "vuex";

describe("PosRight", () => {
  const localVue = createLocalVue();

  beforeEach(() => {
    localVue.filter("truncate", (value, length) => {
      length = length || 15;
      if (!value || typeof value !== "string") return "";
      if (value.length <= length) return value;
      return value.substring(0, length) + "...";
    });
  });

  it("shows correct elements during and after editMode", async () => {
    const propsData = {
      editMode: true,
      order: {},
      selectedItem: {},
    };
    const wrapper = shallowMount(PosRight, { propsData, store, localVue });

    expect(
      wrapper.find("[data-test=pos-right-editMode]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper
        .find("[data-test=pos-right-orderDiscountAmount-input]")
        .isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-right-done-button]").isVisible()
    ).toBeTruthy();
    expect(wrapper.find("[data-test=pos-right-main]").exists()).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-right-itemDiscountAmount-input]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      editMode: false,
      order: {},
      selectedItem: {},
    });

    expect(wrapper.find("[data-test=pos-right-editMode]").exists()).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-right-orderDiscountAmount-input]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-right-done-button]").exists()
    ).toBeFalsy();
    expect(wrapper.find("[data-test=pos-right-main]").isVisible()).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-right-itemDiscountAmount-input]").isVisible()
    ).toBeTruthy();
  });

  it("shows all proper elements when !editMode", async () => {
    const propsData = {
      editMode: false,
      order: {},
      selectedItem: {
        added_item: true,
        price: 0,
      },
    };
    const wrapper = shallowMount(PosRight, { propsData, store, localVue });

    expect(
      wrapper.find("[data-test=pos-right-title-indicator]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-right-price-indicator]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-right-itemTitle-input]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-right-applyOriginalPrice-button]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      selectedItem: {
        added_item: true,
        title: "Test",
        price: 0,
        discount_id: 1,
      },
    });

    expect(
      wrapper.find("[data-test=pos-right-title-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-right-price-indicator]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      selectedItem: {
        price: 0,
        original_price: 500,
        temp_price: 0,
        discount_amount: 1,
      },
    });

    expect(
      wrapper.find("[data-test=pos-right-price-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper
        .find("[data-test=pos-right-applyOriginalPrice-button]")
        .isVisible()
    ).toBeTruthy();
  });

  it("can emit disable-edit-mode", async () => {
    const propsData = {
      editMode: true,
      order: {},
      selectedItem: {},
    };
    const wrapper = shallowMount(PosRight, { propsData, store, localVue });

    wrapper.find("[data-test=pos-right-done-button]").trigger("click");

    expect(wrapper.emitted()).toHaveProperty("disable-edit-mode");
    expect(wrapper.emitted("disable-edit-mode").length).toEqual(1);
  });

  it("can toggle tax on order", async () => {
    const propsData = {
      editMode: true,
      order: {},
      selectedItem: {},
    };
    const wrapper = shallowMount(PosRight, { propsData, store, localVue });

    wrapper.find("[data-test=pos-right-orderNoTax-input]").trigger("click");
    wrapper.find("[data-test=pos-right-orderApplyTax-input]").trigger("click");

    expect(wrapper.emitted("apply-no-tax")).toHaveLength(2);
    expect(wrapper.emitted("apply-no-tax")[0]).toEqual([true]);
    expect(wrapper.emitted("apply-no-tax")[1]).toEqual([false]);
  });

  it("can emit update-quantity-ordered", () => {
    const propsData = {
      editMode: false,
      order: {},
      selectedItem: {
        quantity_ordered: 1,
      },
    };
    const wrapper = shallowMount(PosRight, {
      propsData,
      store,
      localVue,
    });

    wrapper.find("[data-test=pos-right-itemQuantityOrdered-input]").setValue(2);

    expect(wrapper.emitted()).toHaveProperty("update-quantity-ordered");
    expect(wrapper.emitted("update-quantity-ordered")).toHaveLength(1);
    expect(wrapper.emitted("update-quantity-ordered")[0]).toEqual([2]);
  });

  it("can emit update-price", async () => {
    const propsData = {
      editMode: false,
      order: {},
      selectedItem: {
        price: 100,
      },
    };
    // Mount b/c test is dependent on CurrencyInput.vue from vue-currency-input
    const wrapper = mount(PosRight, {
      propsData,
      store,
      localVue,
    });

    wrapper.find("[data-test=pos-right-itemPrice-input]").setValue(200);
    await new Promise((r) => setTimeout(r, 260));

    expect(wrapper.emitted()).toHaveProperty("update-price");
    expect(wrapper.emitted("update-price")).toHaveLength(1);
    expect(wrapper.emitted("update-price")[0]).toEqual([200]);
  });

  it("can emit update-title", () => {
    const propsData = {
      editMode: false,
      order: {},
      selectedItem: {
        added_item: true,
        title: "Test",
      },
    };
    const wrapper = shallowMount(PosRight, {
      propsData,
      store,
      localVue,
    });

    wrapper.find("[data-test=pos-right-itemTitle-input]").setValue("Testing");

    expect(wrapper.emitted()).toHaveProperty("update-title");
    expect(wrapper.emitted("update-title")).toHaveLength(1);
    expect(wrapper.emitted("update-title")[0]).toEqual(["Testing"]);
  });

  it("can emit update-discount-amount based on itemDiscountAmount", async () => {
    const propsData = {
      editMode: false,
      order: {},
      selectedItem: {
        discount_amount: 0,
        price: 250,
        temp_price: 500,
      },
    };
    const spy = jest.fn();
    // Mount b/c test is dependent on CurrencyInput.vue from vue-currency-input
    const wrapper = mount(PosRight, {
      propsData,
      store,
      localVue,
      mocks: {
        $toasted: {
          show: spy,
        },
      },
    });

    wrapper
      .find("[data-test=pos-right-itemDiscountAmount-input]")
      .setValue(100);
    await new Promise((r) => setTimeout(r, 260));
    wrapper
      .find("[data-test=pos-right-itemDiscountAmount-input]")
      .setValue(501);
    await new Promise((r) => setTimeout(r, 260));

    expect(spy).toBeCalledTimes(1);
    expect(wrapper.emitted()).toHaveProperty("update-discount-amount");
    expect(wrapper.emitted("update-discount-amount")).toHaveLength(2);
    expect(wrapper.emitted("update-discount-amount")[0]).toEqual([100]);
    expect(wrapper.emitted("update-discount-amount")[1]).toEqual([null]);

    spy.mockClear();
    await wrapper.setProps({
      selectedItem: {
        added_item: true,
        discount_amount: 0,
        price: 250,
      },
    });

    wrapper
      .find("[data-test=pos-right-itemDiscountAmount-input]")
      .setValue(100);
    await new Promise((r) => setTimeout(r, 260));

    expect(spy).toBeCalledTimes(1);
    expect(wrapper.emitted("update-discount-amount")).toHaveLength(3);
    expect(wrapper.emitted("update-discount-amount")[2]).toEqual([null]);

    spy.mockClear();
    await wrapper.setProps({
      selectedItem: {
        added_item: true,
        discount_amount: 0,
        price: 250,
        temp_price: 250,
      },
    });

    wrapper
      .find("[data-test=pos-right-itemDiscountAmount-input]")
      .setValue(251);
    await new Promise((r) => setTimeout(r, 260));

    expect(spy).toBeCalledTimes(1);
    expect(wrapper.emitted("update-discount-amount")).toHaveLength(4);
    expect(wrapper.emitted("update-discount-amount")[3]).toEqual([null]);
  });

  it("can emit update-discount-amount based on orderDiscountAmount", async () => {
    const propsData = {
      editMode: true,
      order: {
        discount_amount: 0,
        sub_total: 500,
      },
      selectedItem: {},
    };
    const spy = jest.fn();
    // Mount b/c test is dependent on CurrencyInput.vue from vue-currency-input
    const wrapper = mount(PosRight, {
      propsData,
      store,
      localVue,
      mocks: {
        $toasted: {
          show: spy,
        },
      },
    });

    wrapper
      .find("[data-test=pos-right-orderDiscountAmount-input]")
      .setValue(100);
    await new Promise((r) => setTimeout(r, 260));
    wrapper
      .find("[data-test=pos-right-orderDiscountAmount-input]")
      .setValue(501);
    await new Promise((r) => setTimeout(r, 260));

    expect(spy).toBeCalledTimes(1);
    expect(wrapper.emitted()).toHaveProperty("update-discount-amount");
    expect(wrapper.emitted("update-discount-amount")).toHaveLength(2);
    expect(wrapper.emitted("update-discount-amount")[0]).toEqual([100]);
    expect(wrapper.emitted("update-discount-amount")[1]).toEqual([null]);
  });

  it("can emit order-wide-discount", async () => {
    const propsData = {
      editMode: true,
      order: {},
      selectedItem: {},
    };
    // Mount b/c test is dependent on CustomSelect.vue
    const wrapper = mount(PosRight, {
      propsData,
      store: new Vuex.Store({
        getters: {
          discountsVisible: () => [
            {
              id: 1,
              name: "10%",
            },
          ],
        },
      }),
      localVue,
    });
    let val = null;
    const spy = jest.fn((value) => (val = value));

    wrapper.vm.$root.$on("order-wide-discount", spy);
    wrapper
      .find("[data-test=Discount-custom-select-option-1]")
      .trigger("click");

    expect(spy).toBeCalledTimes(1);
    expect(val).toEqual(1);
    spy.mockClear();

    wrapper
      .find("[data-test=Discount-custom-select-option-1]")
      .trigger("click");

    expect(spy).toBeCalledTimes(1);
    expect(val).toEqual(null);
  });

  it("can emit update-discount", async () => {
    const propsData = {
      editMode: false,
      order: {},
      selectedItem: {
        discount_id: null,
      },
    };
    // Mount b/c test is dependent on CustomSelect.vue
    const wrapper = mount(PosRight, {
      propsData,
      store: new Vuex.Store({
        getters: {
          discountsVisible: () => [
            {
              id: 1,
              name: "10%",
            },
          ],
          classificationsVisible: () => [
            {
              id: 1,
              name: "Test",
            },
          ],
        },
      }),
      localVue,
    });

    wrapper.vm.$on("update-discount", (id) =>
      wrapper.setProps({
        selectedItem: {
          discount_id: id,
        },
      })
    );
    wrapper
      .find("[data-test=Discount-custom-select-option-1]")
      .trigger("click");
    await wrapper.vm.$nextTick();
    wrapper
      .find("[data-test=Discount-custom-select-option-1]")
      .trigger("click");

    expect(wrapper.emitted("update-discount")).toHaveLength(2);
    expect(wrapper.emitted("update-discount")[0]).toEqual([1]);
    expect(wrapper.emitted("update-discount")[1]).toEqual([null]);
  });
});
