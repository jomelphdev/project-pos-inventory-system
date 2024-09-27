const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import OrderSearch from "../OrderSearch";
import Vuex from "vuex";

describe("OrderSearch", () => {
  let order;
  let actions;
  let store;
  const OLD_ENV = process.env;

  beforeEach(() => {
    jest.resetModules();
    process.env = { ...OLD_ENV };
    process.env.NODE_ENV = "test";
    order = {
      id: 1,
      created_by: 1,
      cash: 500,
      card: 0,
      ebt: 0,
      sub_total: 500,
      tax: 25,
      total: 525
    };
    actions = {
      getOrderForReturn: jest.fn().mockResolvedValue(order),
      getOrder: jest.fn().mockResolvedValue(order)
    };
    store = new Vuex.Store({ actions });
  });

  afterAll(() => {
    process.env = OLD_ENV;
  });

  it("can queryOrder and emit order-found", async () => {
    const wrapper = shallowMount(OrderSearch, {
      store,
      mocks: {
        $route: {
          query: {
            orderId: null
          }
        }
      }
    });

    wrapper.find("[data-test=order-search-input]").setValue("1");
    wrapper.vm.queryOrder();
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.getOrder).toHaveBeenCalledTimes(1);
    expect(actions.getOrder).toHaveBeenCalledWith(expect.any(Object), "1");
    expect(wrapper.emitted("order-found")).toHaveLength(1);
    expect(wrapper.emitted("order-found")[0]).toEqual([order]);
    expect(wrapper.vm.query).toEqual("");

    wrapper.vm.query = "1";
    await wrapper.setProps({ getDataForReturn: true });
    wrapper.vm.queryOrder();
    await wrapper.vm.$nextTick();

    expect(actions.getOrderForReturn).toHaveBeenCalledTimes(1);
    expect(actions.getOrderForReturn).toHaveBeenCalledWith(
      expect.any(Object),
      "1"
    );
    expect(wrapper.emitted("order-found")).toHaveLength(2);
    expect(wrapper.emitted("order-found")[1]).toEqual([order]);
  });

  it("can query with route parameter", async () => {
    const spy = jest.spyOn(OrderSearch.methods, "queryOrder");
    shallowMount(OrderSearch, {
      store,
      mocks: {
        $route: {
          query: {
            orderId: "1"
          }
        }
      }
    });

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it("handles search-pos-orders event", async () => {
    const wrapper = shallowMount(OrderSearch, {
      store,
      mocks: {
        $route: {
          query: {
            orderId: null
          }
        }
      }
    });
    wrapper.vm.queryOrder = jest.fn();

    wrapper.vm.$root.$emit("search-pos-orders");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.queryOrder).toHaveBeenCalledTimes(1);
  });
});
