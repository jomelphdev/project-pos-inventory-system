const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import Create from "../Create";
import Vuex from "vuex";
import { CurrencyInput } from "vue-currency-input";
import Modal from "@/components/Modal";

describe("ItemCreate", () => {
  let actions;
  let getters;
  let store;
  let mocks = {
    $route: {
      params: {
        item: null
      }
    },
    $toasted: {
      show: jest.fn()
    }
  };
  let stubs = { "currency-input": CurrencyInput };

  beforeEach(() => {
    actions = {
      getPreferences: jest.fn()
    };
    getters = {
      conditionsVisible: () => [
        {
          id: 1
        },
        {
          id: 2
        }
      ],
      conditions_disabled: () => false,
      classificationsVisible: () => [],
      classifications_disabled: () => false,
      consignorsVisible: () => [],
      qzReadyToPrint: () => true,
      qzLabelPrinter: () => true
    };
    store = new Vuex.Store({ actions, getters });
  });

  it("can queryUpc", async () => {
    const propsData = { upc: "123456789012" };
    actions.getUpcData = jest.fn().mockResolvedValue({
      upc_item: {
        title: "Test Item",
        offers: [
          {
            price: 1000,
            merchant: "Walmart",
            link: 1
          }
        ],
        images: []
      },
      listed_upc_items: []
    });
    store.hotUpdate({ actions });
    const wrapper = shallowMount(Create, { store, mocks, stubs, propsData });
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.getUpcData).toHaveBeenCalledTimes(1);
    expect(actions.getUpcData).toHaveBeenCalledWith(
      expect.any(Object),
      propsData.upc
    );
  });

  it("shows listed before modal", async () => {
    const propsData = { upc: "123456789012" };
    actions.getUpcData = jest.fn().mockResolvedValue({
      upc_item: {
        title: "Test Item",
        offers: [
          {
            price: 1000,
            merchant: "Walmart",
            link: 1
          }
        ],
        images: []
      },
      listed_upc_items: [
        {
          id: 1,
          title: "Test Item"
        }
      ]
    });
    store.hotUpdate({ actions });
    stubs.modal = Modal;
    const wrapper = shallowMount(Create, { store, mocks, stubs, propsData });
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=listed-before-modal]").isVisible()
    ).toBeTruthy();
    expect(
      wrapper.find("[data-test=listed-before-modal-item-0]").isVisible
    ).toBeTruthy();
  });
});
