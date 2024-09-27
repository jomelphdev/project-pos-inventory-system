import { createLocalVue, shallowMount } from "@vue/test-utils";
import { jest } from "@jest/globals";
import PosItemRow from "../PosItemRow";
import store from "@/store";
import Vuex from "vuex";

describe("PosItemRow", () => {
  const localVue = createLocalVue();

  beforeEach(() => {
    localVue.filter("truncate", (value, length) => {
      length = length || 15;
      if (!value || typeof value !== "string") return "";
      if (value.length <= length) return value;
      return value.substring(0, length) + "...";
    });
  });

  it("remove option hides correctly", async () => {
    const propsData = {
      item: {}
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });

    expect(
      wrapper.find("[data-test=pos-item-row-removeItem-button]").exists()
    ).toBeTruthy();

    await wrapper.setProps({
      showRemoveOption: false
    });

    expect(
      wrapper.find("[data-test=pos-item-row-removeItem-button]").exists()
    ).toBeFalsy();
  });

  it("can send remove event", () => {
    const propsData = {
      item: {}
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });
    const spy = jest.fn();

    wrapper.vm.$root.$on("remove-item", spy);
    wrapper.find("[data-test=pos-item-row-removeItem-button]").trigger("click");

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it("can show image", async () => {
    const propsData = {
      item: {}
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });

    expect(wrapper.find("[data-test=pos-item-row-image]").exists()).toBeFalsy();

    await wrapper.setProps({
      item: {
        // Not real image literally just a string.
        images: ["testing.png"]
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-image]").exists()
    ).toBeTruthy();
  });

  it("shows requirement tags when they should be shown", async () => {
    const propsData = {
      item: {
        price: 100
      }
    };
    let wrapper = shallowMount(PosItemRow, { propsData, localVue, store });

    expect(
      wrapper.find("[data-test=pos-item-row-classification]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-consignment]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-title-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-classification-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-price-indicator]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        added_item: true,
        price: 0,
        consignor_id: 1
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-consignment]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-item-row-title-indicator]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper
        .find("[data-test=pos-item-row-classification-indicator]")
        .isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-item-row-price-indicator]").isVisible()
    ).toBeTruthy();

    await wrapper.setProps({
      item: {
        added_item: true,
        title: "Test",
        price: 0,
        discount_id: 1,
        classification_id: 1
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-classification]").exists()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-item-row-title-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-classification-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-price-indicator]").exists()
    ).toBeFalsy();

    wrapper = shallowMount(PosItemRow, {
      propsData: {
        item: {
          added_item: true,
          price: 0,
          discount_amount: 100
        }
      },
      store: new Vuex.Store({
        getters: {
          classifications_disabled: () => true
        }
      }),
      localVue
    });

    expect(
      wrapper.find("[data-test=pos-item-row-classification-indicator]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-price-indicator]").exists()
    ).toBeFalsy();
  });

  it("shows proper quantity element", async () => {
    const propsData = {
      item: {
        quantity_ordered: 1
      }
    };
    let wrapper = shallowMount(PosItemRow, { propsData, localVue, store });

    expect(
      wrapper.find("[data-test=pos-item-row-quantity]").exists()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-item-row-quantity-left]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        quantity_ordered: 1,
        quantity_left_to_return: 1
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-quantity]").exists()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=pos-item-row-quantity-left]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        quantity_ordered: 1,
        quantity_left_to_return: 2
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-quantity]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-item-row-quantity-left]").exists()
    ).toBeTruthy();
  });

  it("shows original price when required", async () => {
    const propsData = {
      item: {}
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });

    expect(
      wrapper.find("[data-test=pos-item-row-original-price]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        temp_price: 500,
        original_price: 1000
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-original-price]").isVisible()
    ).toBeTruthy();

    await wrapper.setProps({
      item: {
        discount_id: 1,
        temp_price: 500,
        original_price: 600
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-original-price]").isVisible()
    ).toBeTruthy();

    await wrapper.setProps({
      item: {
        discount_amount: 100,
        temp_price: 500,
        original_price: 600
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-original-price]").isVisible()
    ).toBeTruthy();

    await wrapper.setProps({
      item: {
        discount_id: 1,
        temp_price: 500,
        original_price: 500
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-original-price]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        discount_amount: 1,
        temp_price: 500,
        original_price: 500
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-original-price]").exists()
    ).toBeFalsy();
  });

  it("shows temp_price when required", async () => {
    const propsData = {
      item: {}
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });

    expect(
      wrapper.find("[data-test=pos-item-row-temp-price]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        temp_price: 500,
        original_price: 500,
        discount_id: 1
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-temp-price]").exists()
    ).toBeTruthy();

    await wrapper.setProps({
      item: {
        temp_price: 500,
        original_price: 500,
        discount_amount: 100
      }
    });

    expect(
      wrapper.find("[data-test=pos-item-row-temp-price]").exists()
    ).toBeTruthy();
  });

  it("shows discount percentage", async () => {
    const propsData = {
      item: {}
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });

    expect(
      wrapper
        .find("[data-test=pos-item-row-price-discount-percentage]")
        .exists()
    ).toBeFalsy();

    await wrapper.setProps({
      item: {
        discount_id: 1,
        temp_price: 500,
        original_price: 600
      }
    });

    expect(
      wrapper
        .find("[data-test=pos-item-row-price-discount-percentage]")
        .exists()
    ).toBeTruthy();
  });

  it("correct row class shows when required", async () => {
    const propsData = {
      item: {
        id: 1
      },
      selectedId: 2
    };
    const wrapper = shallowMount(PosItemRow, { propsData, localVue });
    let itemRowClasses = wrapper.find("[data-test=pos-item-row]").classes();

    expect(itemRowClasses.includes("rr-table__tr--selected")).toBeFalsy();
    expect(itemRowClasses.includes("rr-table__tr--hidden")).toBeFalsy();

    await wrapper.setProps({
      item: {
        id: 1
      },
      selectedId: 1
    });

    itemRowClasses = wrapper.find("[data-test=pos-item-row]").classes();

    expect(itemRowClasses.includes("rr-table__tr--selected")).toBeTruthy();
    expect(itemRowClasses.includes("rr-table__tr--hidden")).toBeFalsy();

    await wrapper.setProps({
      item: {
        quantity_left_to_return: 0
      }
    });

    itemRowClasses = wrapper.find("[data-test=pos-item-row]").classes();

    expect(itemRowClasses.includes("rr-table__tr--selected")).toBeFalsy();
    expect(itemRowClasses.includes("rr-table__tr--hidden")).toBeTruthy();
  });
});
