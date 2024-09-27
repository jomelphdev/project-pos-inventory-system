const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import PosBottom from "../PosBottom";
import store from "@/store";
import Vuex from "vuex";

describe("PosBottom", () => {
  it("can send start over event", () => {
    const propsData = {
      order: {}
    };
    const wrapper = shallowMount(PosBottom, { propsData, store });
    const spy = jest.fn();

    wrapper.vm.$root.$on("reload-pos", spy);
    wrapper.find("[data-test=pos-start-over-button]").trigger("click");

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it("can properly emit payment method type", async () => {
    const propsData = {
      order: {},
      paymentMethod: null
    };
    const wrapper = shallowMount(PosBottom, { propsData, store });

    wrapper.find("[data-test=pos-bottom-cash-button]").trigger("click");
    wrapper.find("[data-test=pos-bottom-card-button]").trigger("click");
    wrapper.find("[data-test=pos-bottom-ebt-button]").trigger("click");

    jest.spyOn(console, "error").mockImplementation(jest.fn());
    await wrapper.setProps({ paymentMethod: "cash" });
    jest.spyOn(console, "error").mockRestore();

    wrapper.find("[data-test=pos-bottom-goBack-button]").trigger("click");

    const pmsEmitted = wrapper.emitted("update:paymentMethod");

    expect(pmsEmitted[0]).toEqual(["cash"]);
    expect(pmsEmitted[1]).toEqual(["card"]);
    expect(pmsEmitted[2]).toEqual(["ebt"]);
    expect(pmsEmitted[3]).toEqual([null]);
  });

  it("can show proper elements when payment method is present", async () => {
    const propsData = {
      order: {},
      paymentMethod: null
    };
    const wrapper = shallowMount(PosBottom, { propsData, store });

    expect(wrapper.find("[data-test=pos-bottom-totals]").exists()).toBeTruthy();

    jest.spyOn(console, "error").mockImplementation(jest.fn());
    await wrapper.setProps({ paymentMethod: "cash" });
    jest.spyOn(console, "error").mockRestore();

    expect(
      wrapper.find("[data-test=pos-bottom-summary]").exists()
    ).toBeTruthy();
  });

  it("can show proper elements and values when a discount_amount is preset", async () => {
    const propsData = {
      order: {
        prior_sub_total: 1000,
        sub_total: 900,
        discount_amount: 100
      }
    };
    const wrapper = shallowMount(PosBottom, { propsData, store });

    expect(
      wrapper.find("[data-test=pos-bottom-subTotal-display]").text()
    ).toEqual("$10.00");
    expect(
      wrapper.find("[data-test=pos-bottom-discount-display]").text()
    ).toEqual("$1.00");

    await wrapper.setProps({
      order: {
        sub_total: 900,
        discount_amount: 0
      }
    });

    expect(
      wrapper.find("[data-test=pos-bottom-subTotal-display]").text()
    ).toEqual("$9.00");
    expect(
      wrapper.find("[data-test=pos-bottom-discount-display]").exists()
    ).toBeFalsy();
  });

  it("can display correct order conditional UI elements", async () => {
    const propsData = {
      order: {
        amount_remaining: 100,
        change: 0
      },
      paymentMethod: "cash"
    };
    const wrapper = shallowMount(PosBottom, { propsData });

    const amountRemaining = wrapper.find(
      "[data-test=pos-bottom-amountRemaining]"
    );
    expect(amountRemaining.exists()).toBeTruthy();
    expect(amountRemaining.classes().includes("text-red-700")).toBeTruthy();
    expect(wrapper.find("[data-test=pos-bottom-ebtPaid]").exists()).toBeFalsy();
    expect(
      wrapper.find("[data-test=pos-bottom-change-note]").exists()
    ).toBeFalsy();

    await wrapper.setProps({
      order: {
        amount_remaining: 0,
        change: 0,
        ebt: 250
      }
    });

    expect(
      wrapper.find("[data-test=pos-bottom-ebtPaid]").exists()
    ).toBeTruthy();
    expect(amountRemaining.classes().includes("text-green-700")).toBeTruthy();

    await wrapper.setProps({
      order: {
        amount_remaining: 0,
        change: 250
      }
    });

    expect(
      wrapper.find("[data-test=pos-bottom-change-note]").exists()
    ).toBeTruthy();
  });

  it("hides EBT if classifications are disabled", () => {
    const propsData = {
      order: {},
      paymentMethod: null
    };
    let wrapper = shallowMount(PosBottom, { propsData, store });

    expect(
      wrapper.find("[data-test=pos-bottom-ebt-button]").exists()
    ).toBeTruthy();

    wrapper = shallowMount(PosBottom, {
      propsData,
      store: new Vuex.Store({
        getters: {
          classifications_disabled: () => true
        }
      })
    });

    expect(
      wrapper.find("[data-test=pos-bottom-ebt-button]").exists()
    ).toBeFalsy();
  });

  it("can send checkout event", async () => {
    const propsData = {
      order: {
        amount_remaining: 100
      },
      paymentMethod: "cash"
    };
    const wrapper = shallowMount(PosBottom, { propsData, store });
    const checkoutButton = wrapper.find(
      "[data-test=pos-bottom-checkout-button]"
    );
    expect(checkoutButton.attributes().disabled).toBeTruthy();

    await wrapper.setProps({
      order: {
        amount_remaining: 0
      }
    });

    expect(checkoutButton.attributes().disabled).toBeFalsy();

    checkoutButton.trigger("click");

    expect(wrapper.emitted()).toHaveProperty("update:checkingOut");
    expect(wrapper.emitted()).toHaveProperty("checkout");
  });
});
