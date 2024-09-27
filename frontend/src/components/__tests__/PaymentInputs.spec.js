const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import { CurrencyInput } from "vue-currency-input";
import PaymentInputs from "../PaymentInputs";
import store from "@/store";

describe("PaymentInputs", () => {
  it("can add remaining amount", async () => {
    const propsData = {
      amountRemaining: 1000,
      ebt: 0,
      ebtSubTotal: 1000
    };
    const wrapper = shallowMount(PaymentInputs, {
      store,
      propsData,
      stubs: { "currency-input": CurrencyInput }
    });

    expect(
      wrapper
        .find("[data-test=payment-input-remainingToCash-button]")
        .isVisible()
    ).toBeTruthy();
    expect(
      wrapper
        .find("[data-test=payment-input-remainingToCard-button]")
        .isVisible()
    ).toBeTruthy();
    expect(
      wrapper
        .find("[data-test=payment-input-remainingToEbt-button]")
        .isVisible()
    ).toBeTruthy();

    wrapper
      .find("[data-test=payment-input-remainingToCash-button]")
      .trigger("click");
    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 260));

    expect(wrapper.vm.cashLocal).toEqual(1000);
    expect(wrapper.emitted("update:cash")).toHaveLength(1);
    expect(wrapper.emitted("update:cash")[0]).toEqual([1000]);

    wrapper
      .find("[data-test=payment-input-remainingToCard-button]")
      .trigger("click");
    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 260));

    expect(wrapper.vm.cardLocal).toEqual(1000);
    expect(wrapper.emitted("update:card")).toHaveLength(1);
    expect(wrapper.emitted("update:card")[0]).toEqual([1000]);

    wrapper
      .find("[data-test=payment-input-remainingToEbt-button]")
      .trigger("click");
    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 260));

    expect(wrapper.vm.ebtLocal).toEqual(1000);
    expect(wrapper.emitted("update:ebt")).toHaveLength(1);
    expect(wrapper.emitted("update:ebt")[0]).toEqual([1000]);
  });

  it("cardMax returns card_left when required", async () => {
    const propsData = {
      order: {
        processor_reference: "176543",
        card_left: 2000
      },
      forReturns: true
    };
    jest.spyOn(console, "error").mockImplementation(jest.fn());
    store.hotUpdate({ getters: { paymentPartner: () => true } });
    jest.spyOn(console, "error").mockRestore();
    const wrapper = shallowMount(PaymentInputs, {
      store,
      propsData,
      stubs: { "currency-input": CurrencyInput }
    });

    expect(wrapper.vm.cardMax).toEqual(2000);
  });

  it("hides card input when required", async () => {
    const wrapper = shallowMount(PaymentInputs, {
      store,
      stubs: { "currency-input": CurrencyInput }
    });

    expect(
      wrapper.find("[data-test=payment-input-card]").isVisible()
    ).toBeTruthy();

    wrapper.setProps({
      order: {
        processor_reference: "176543",
        card_left: 0
      },
      forReturns: true
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=payment-input-card]").exists()).toBeFalsy();
  });

  it("hides ebt input when required", async () => {
    const propsData = {
      ebtSubTotal: 1000
    };
    const wrapper = shallowMount(PaymentInputs, {
      store,
      propsData,
      stubs: { "currency-input": CurrencyInput }
    });

    expect(
      wrapper.find("[data-test=payment-input-ebt]").isVisible()
    ).toBeTruthy();

    wrapper.setProps({ ebtSubTotal: 0 });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=payment-input-ebt]").exists()).toBeFalsy();
  });

  it("can set card to cardMax when required", async () => {
    const propsData = {
      order: {
        processor_reference: "176543",
        card_left: 1000
      },
      amountRemaining: 1500,
      forReturns: true
    };
    jest.spyOn(console, "error").mockImplementation(jest.fn());
    store.hotUpdate({ getters: { paymentPartner: () => true } });
    jest.spyOn(console, "error").mockRestore();
    const wrapper = shallowMount(PaymentInputs, {
      store,
      propsData,
      stubs: { "currency-input": CurrencyInput },
      mocks: {
        $toasted: {
          show: jest.fn()
        }
      }
    });

    wrapper.vm.cardLocal = 1500;
    await wrapper.vm.$nextTick();
    await new Promise(r => setTimeout(r, 260));

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(
      wrapper.vm.$toasted.show
    ).toHaveBeenCalledWith(
      "Set to the maximum amount allowed to be charged on card.",
      { type: "info" }
    );
    expect(wrapper.vm.cardLocal).toEqual(1000);

    wrapper.vm.cardLocal = 0;
    wrapper.vm.remainingToCard();
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.cardLocal).toEqual(1000);
  });
});
