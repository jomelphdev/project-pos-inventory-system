const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import AdjustQuantity from "../AdjustQuantity";

describe("AdjustQuantity", () => {
  it("can show/hide", async () => {
    const wrapper = shallowMount(AdjustQuantity);

    const main = wrapper.find("[data-test=adjust-quantity]");
    expect(main.isVisible()).toBeFalsy();

    wrapper.setData({
      show: true
    });
    await wrapper.vm.$nextTick();

    expect(main.isVisible()).toBeTruthy();
  });

  it("shows store name", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true
        };
      }
    });

    expect(wrapper.find("[data-test=store-name]").exists()).toBeFalsy();

    wrapper.setData({
      quantity: { store_id: 1 }
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=store-name]").isVisible()).toBeTruthy();
  });

  it("shows correct verb", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true
        };
      }
    });
    const verb = wrapper.find("[data-test=verb]");

    expect(verb.text().includes("Add")).toBeTruthy();

    wrapper.setData({ amount: 1 });
    await wrapper.vm.$nextTick();

    expect(verb.text().includes("Add")).toBeTruthy();

    wrapper.setData({ amount: -1 });
    await wrapper.vm.$nextTick();

    expect(verb.text().includes("Subtract")).toBeTruthy();
  });

  it("shows proper elements when amount is negative", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true
        };
      }
    });

    expect(wrapper.find("[data-test=negative-warning]").exists()).toBeFalsy();
    expect(
      wrapper.find("[data-test=reason-for-negative]").exists()
    ).toBeFalsy();

    wrapper.setData({ amount: -1 });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=negative-warning]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=reason-for-negative]").isVisible()
    ).toBeTruthy();
  });

  it("handles adjust-quantity properly", async () => {
    const wrapper = shallowMount(AdjustQuantity);
    wrapper.vm.$root.$emit("adjust-quantity", { store_id: 1 }, 1);

    await wrapper.vm.$nextTick();

    expect(wrapper.vm.show).toBeTruthy();
    expect(wrapper.vm.quantity).toEqual({ store_id: 1 });
    expect(wrapper.vm.amount).toEqual(1);
  });

  it("can increase amount and emit quantity-adjustment", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true,
          quantity: { store_id: 1 }
        };
      }
    });
    const spy = jest.fn();

    wrapper.vm.$root.$on("quantity-adjustment", spy);
    wrapper.find("[data-test=increaseAmount-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.amount).toEqual(1);
    expect(spy).toHaveBeenCalledTimes(1);
    expect(spy).toHaveBeenCalledWith({ amount: 1, id: 1 });
  });

  it("can decrease amount and emit quantity-adjustment", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true,
          quantity: { store_id: 1 }
        };
      }
    });
    const spy = jest.fn();

    wrapper.vm.$root.$on("quantity-adjustment", spy);
    wrapper.find("[data-test=decreaseAmount-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.amount).toEqual(-1);
    expect(spy).toHaveBeenCalledTimes(1);
    expect(spy).toHaveBeenCalledWith({ amount: -1, id: 1 });
  });

  it("doesnt allow negative quantity", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true,
          quantity: { store_id: 1, quantity: 1 }
        };
      },
      mocks: {
        $toasted: {
          show: jest.fn()
        }
      }
    });

    wrapper.find("[data-test=decreaseAmount-button]").trigger("click");
    await wrapper.vm.$nextTick();
    wrapper.find("[data-test=decreaseAmount-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(
      wrapper.vm.$toasted.show
    ).toHaveBeenCalledWith(
      "We do not allow for the creation of negative quantities.",
      { type: "error" }
    );
  });

  it("shows message when !allowNegatives", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      propsData: {
        allowNegatives: false
      },
      data() {
        return {
          show: true
        };
      },
      mocks: {
        $toasted: {
          show: jest.fn()
        }
      }
    });

    wrapper.find("[data-test=decreaseAmount-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "Negative quantities not allowed on this page.",
      {
        type: "info"
      }
    );
  });

  it("requires message when negative", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true,
          amount: -1
        };
      },
      mocks: {
        $toasted: {
          show: jest.fn()
        }
      }
    });

    wrapper.vm.closeModal();
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      "Please enter a reason for removal to continue.",
      {
        type: "error"
      }
    );
  });

  it("emits save-quantity", async () => {
    const wrapper = shallowMount(AdjustQuantity, {
      data() {
        return {
          show: true
        };
      }
    });
    const spy = jest.fn();

    wrapper.vm.$root.$on("save-quantity", spy);
    wrapper.vm.closeModal();
    await wrapper.vm.$nextTick();

    expect(spy).toHaveBeenCalledTimes(1);
  });
});
