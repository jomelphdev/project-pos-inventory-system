const { shallowMount, createLocalVue } = require("@vue/test-utils");
import ReturnsBottom from "../ReturnsBottom";
import Vuex from "vuex";

describe("ReturnsBottom", () => {
  let getters;
  let store;
  let localVue = createLocalVue();
  localVue.filter("truncate", (value, length) => {
    length = length || 15;
    if (!value || typeof value !== "string") return "";
    if (value.length <= length) return value;
    return value.substring(0, length) + "...";
  });

  beforeEach(() => {
    getters = {
      paymentPartner: () => false
    };
    store = new Vuex.Store({ getters });
  });

  it("shows correct elements for finalize conditionals", async () => {
    const propsData = {
      items: [],
      order: {},
      returnForm: {}
    };
    const wrapper = shallowMount(ReturnsBottom, { propsData, store });

    expect(
      wrapper.find("[data-test=returns-bottom-startOver-button]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=returns-bottom-totals-display]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=returns-bottom-items-returning-table]").exists()
    ).toBeFalsy();
    expect(wrapper.find("[data-test=payments-remaining]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=payment-summary]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=payment-inputs]").exists()).toBeFalsy();

    await wrapper.setProps({ finalize: true });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=returns-bottom-startOver-button]").exists()
    ).toBeFalsy();
    expect(
      wrapper.find("[data-test=returns-bottom-totals-display]").exists()
    ).toBeFalsy();
    expect(
      wrapper
        .find("[data-test=returns-bottom-items-returning-table]")
        .isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=payments-remaining]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=payment-summary]").isVisible()
    ).toBeTruthy();
    expect(wrapper.find("[data-test=payment-inputs]").isVisible()).toBeTruthy();
  });

  it("can emit reload-pos", async () => {
    const propsData = {
      items: [],
      order: {},
      returnForm: {}
    };
    const wrapper = shallowMount(ReturnsBottom, { propsData });
    const spy = jest.fn();

    wrapper.vm.$root.$on("reload-pos", spy);
    wrapper
      .find("[data-test=returns-bottom-startOver-button]")
      .trigger("click");
    await wrapper.vm.$nextTick();

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it("can update finalize", async () => {
    const propsData = {
      items: [],
      order: {},
      returnForm: {}
    };
    const wrapper = shallowMount(ReturnsBottom, {
      store,
      propsData,
      mocks: {
        $toasted: {
          show: jest.fn()
        }
      },
      localVue
    });

    wrapper.find("[data-test=returns-bottom-continue-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(
      wrapper.vm.$toasted.show
    ).toHaveBeenCalledWith(
      "No items are ready for return, please double check you entered an action and a quantity.",
      { type: "error" }
    );

    await wrapper.setProps({ items: [{}] });
    wrapper.find("[data-test=returns-bottom-continue-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted("update:finalize")).toHaveLength(1);
    expect(wrapper.emitted("update:finalize")[0]).toEqual([true]);

    await wrapper.setProps({ finalize: true });
    wrapper.find("[data-test=returns-bottom-goBack-button]").trigger("click");

    expect(wrapper.emitted("update:finalize")).toHaveLength(2);
    expect(wrapper.emitted("update:finalize")[1]).toEqual([false]);
  });

  it("createReturn emits correct events", async () => {
    const propsData = {
      items: [],
      order: {},
      returnForm: {}
    };
    const wrapper = shallowMount(ReturnsBottom, { propsData });

    wrapper.vm.createReturn();
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted("update:checkingOut")).toHaveLength(1);
    expect(wrapper.emitted("create-return")).toHaveLength(1);
  });
});
