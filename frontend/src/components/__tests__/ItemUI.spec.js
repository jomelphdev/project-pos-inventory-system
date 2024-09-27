const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import ItemUI from "../ItemUI";
import Vuex from "vuex";
import { CurrencyInput } from "vue-currency-input";
import Modal from "@/components/Modal";

describe("ItemUI", () => {
  let actions;
  let getters;
  let store;
  let mocks = {
    $route: {
      params: {
        item: null
      },
      query: {}
    },
    $toasted: {
      show: jest.fn()
    },
    $router: {
      push: jest.fn()
    }
  };
  let stubs = {
    "currency-input": CurrencyInput,
    Modal
  };

  beforeEach(() => {
    mocks.$toasted.show.mockClear();
    actions = {
      calculatePrice: jest.fn()
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
      classificationsVisible: () => [
        {
          id: 1
        },
        {
          id: 2
        }
      ],
      conditions: () => [],
      classifications: () => [],
      consignorsVisible: () => [],
      conditions_disabled: () => false,
      classifications_disabled: () => false,
      qzReadyToPrint: () => true,
      qzLabelPrinter: () => true
    };
    store = new Vuex.Store({ actions, getters });
  });

  it("shows correct tab data", async () => {
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=item-details]").isVisible()).toBeTruthy();
    expect(wrapper.find("[data-test=item-shipping]").exists()).toBeFalsy();

    wrapper.vm.itemTab = "shipping";
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=item-details]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=item-shipping]").isVisible()).toBeTruthy();
  });

  it("can createItem", async () => {
    let item = {
      title: "Test Item",
      price: 1000,
      original_price: 1500,
      classification_id: 1,
      condition_id: 1
    };
    let createdItem = Object.assign({ id: 1 }, item);
    actions.createItem = jest.fn().mockResolvedValue(createdItem);
    store.hotUpdate(actions);
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    wrapper.vm.itemForm.fill(item);
    wrapper.vm.handleSavedItem = jest.fn();
    await wrapper.vm.$nextTick();

    wrapper.vm.createItem();
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.createItem).toHaveBeenCalledTimes(1);
    expect(actions.createItem).toHaveBeenCalledWith(
      expect.any(Object),
      wrapper.vm.itemForm.data
    );
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledWith(
      `Item Created: "${item.title}".`,
      {
        type: "success"
      }
    );
    expect(wrapper.vm.handleSavedItem).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.handleSavedItem).toHaveBeenCalledWith(true, "scan");
  });

  it("can queryForTitleItems", async () => {
    const items = [{ title: "Test Item" }, { title: "Test Item 2" }];
    actions.queryItems = jest.fn().mockResolvedValue(items);
    store.hotUpdate({ actions });
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    wrapper.vm.itemForm.title = "Test";
    wrapper.vm.queryForTitleItems();
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.queryItems).toHaveBeenCalledTimes(1);
    expect(actions.queryItems).toHaveBeenCalledWith(expect.any(Object), {
      query: "Test"
    });
    expect(wrapper.vm.listedTitleItems).toEqual(items);
  });

  it("can getItemsCountForTitle", async () => {
    actions.getItemsCountForQuery = jest.fn().mockResolvedValue(2);
    actions.getUsedConditionsFromTitle = jest.fn().mockResolvedValue([2]);
    actions.queryItems = jest.fn().mockResolvedValue([]);
    store.hotUpdate({ actions });
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });

    wrapper.vm.removeDupeConditions = true;
    wrapper.vm.itemForm.title = "Test";
    await wrapper.vm.$nextTick();

    wrapper.find("[data-test=searchViaTitle-input]").trigger("click");
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.getItemsCountForQuery).toHaveBeenCalledTimes(1);
    expect(actions.getItemsCountForQuery).toHaveBeenCalledWith(
      expect.any(Object),
      "Test"
    );
    expect(actions.getUsedConditionsFromTitle).toHaveBeenCalledTimes(1);
    expect(actions.getUsedConditionsFromTitle).toHaveBeenCalledWith(
      expect.any(Object),
      "Test"
    );
    expect(wrapper.vm.itemsTitleQueryCount).toEqual(2);
    expect(wrapper.vm.conditionsNotHidden).toEqual([{ id: 1 }]);
    expect(wrapper.find("[data-test=similar-titles]").isVisible()).toBeTruthy();

    wrapper.find("[data-test=similar-titles]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(actions.queryItems).toHaveBeenCalledTimes(1);
  });

  it("can calculatePrice", async () => {
    actions.calculatePrice = jest.fn().mockResolvedValue(1000);
    store.hotUpdate({ actions });
    const methodSpy = jest.spyOn(ItemUI.methods, "calculatePrice");
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    wrapper.vm.priceManuallyInput = true;
    await wrapper.vm.$nextTick();

    wrapper.vm.itemForm.original_price = 1500;
    await wrapper.vm.$nextTick();
    wrapper.vm.itemForm.classification_id = 1;
    await wrapper.vm.$nextTick();
    wrapper.vm.itemForm.condition_id = 1;
    await wrapper.vm.$nextTick();
    wrapper.vm.priceManuallyInput = false;
    wrapper.vm.calculatePrice();
    await wrapper.vm.$nextTick();
    await Promise.resolve();

    expect(methodSpy).toHaveBeenCalledTimes(4);
    expect(actions.calculatePrice).toHaveBeenCalledTimes(1);
    expect(actions.calculatePrice).toHaveBeenCalledWith(expect.any(Object), {
      price: 1500,
      classification_id: 1,
      condition_id: 1
    });
    expect(wrapper.vm.itemForm.price).toEqual(1000);
    methodSpy.mockClear();
  });

  it("can calculatePrice with disabled classifications & conditions", async () => {
    actions.calculatePrice = jest.fn().mockResolvedValue(1000);
    getters.classifications_disabled = () => true;
    store.hotUpdate({ actions, getters });
    const methodSpy = jest.spyOn(ItemUI.methods, "calculatePrice");
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    await wrapper.vm.$nextTick();

    wrapper.vm.itemForm.original_price = 1500;
    await wrapper.vm.$nextTick();
    wrapper.vm.itemForm.condition_id = 1;
    await wrapper.vm.$nextTick();
    await Promise.resolve();

    expect(methodSpy).toHaveBeenCalledTimes(2);
    expect(actions.calculatePrice).toHaveBeenCalledTimes(1);
    expect(actions.calculatePrice).toHaveBeenCalledWith(expect.any(Object), {
      price: 1500,
      classification_id: null,
      condition_id: 1
    });
    expect(wrapper.vm.itemForm.price).toEqual(1000);
    methodSpy.mockClear();
    actions.calculatePrice.mockClear();

    getters.conditions_disabled = () => true;
    store.hotUpdate({ getters });
    wrapper.vm.itemForm.condition_id = null;
    await wrapper.vm.$nextTick();

    expect(methodSpy).toHaveBeenCalledTimes(1);
    expect(actions.calculatePrice).toHaveBeenCalledTimes(1);
    expect(actions.calculatePrice).toHaveBeenCalledWith(expect.any(Object), {
      price: 1500,
      classification_id: null,
      condition_id: null
    });
    expect(wrapper.vm.itemForm.price).toEqual(1000);
    methodSpy.mockClear();
  });

  it("handles manual price change", async () => {
    const methodSpy = jest.spyOn(ItemUI.methods, "manualPriceChange");
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=itemFormPrice-input]").exists()
    ).toBeFalsy();

    wrapper.vm.itemForm.price = 2000;
    wrapper.vm.itemForm.original_price = 2500;
    await wrapper.vm.$nextTick();
    expect(
      wrapper.find("[data-test=itemFormPrice-input]").isVisible()
    ).toBeTruthy();
    await wrapper.find("[data-test=itemFormPrice-input]").setValue(1000);
    await wrapper
      .find("[data-test=itemFormPrice-input]")
      .trigger("change.native");
    await wrapper.vm.$nextTick();

    expect(methodSpy).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.lastOriginalPrice).toEqual(2500);
    expect(wrapper.vm.priceManuallyInput).toEqual(true);
    expect(wrapper.vm.itemForm.original_price).toEqual(1000);
    methodSpy.mockClear();
  });

  it("can turn automated pricing back on", async () => {
    const wrapper = shallowMount(ItemUI, { store, mocks, stubs });
    wrapper.vm.calculatePrice = jest.fn();
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=automatedPricing-button]").exists()
    ).toBeFalsy();

    wrapper.vm.itemForm.price = 1000;
    wrapper.vm.itemForm.original_price = 1000;
    wrapper.vm.lastOriginalPrice = 2500;
    wrapper.vm.priceManuallyInput = true;
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=automatedPricing-button]").isVisible()
    ).toBeTruthy();

    wrapper.vm.calculatePrice.mockClear();
    wrapper.find("[data-test=automatedPricing-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.priceManuallyInput).toBeFalsy();
    expect(wrapper.vm.itemForm.original_price).toEqual(2500);
    expect(wrapper.vm.lastOriginalPrice).toBeNull();
    expect(wrapper.vm.calculatePrice).toHaveBeenCalledTimes(1);
  });

  it("originalPriceChanged works as expected", async () => {
    const methodSpy = jest.spyOn(ItemUI.methods, "originalPriceChanged");
    const wrapper = shallowMount(ItemUI, {
      store,
      mocks,
      stubs
    });
    wrapper.vm.itemForm.price = 1500;
    await wrapper.vm.$nextTick();

    await wrapper
      .find("[data-test=itemFormOriginalPrice-input]")
      .setValue(1000);
    wrapper.find("[data-test=itemFormOriginalPrice-input]").trigger("change");
    await wrapper.vm.$nextTick();

    expect(methodSpy).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(
      wrapper.vm.$toasted.show
    ).toHaveBeenCalledWith(
      "Cannot have a lower original price than sale price.",
      { type: "info" }
    );
    expect(wrapper.vm.itemForm.original_price).toEqual(1500);
    methodSpy.mockClear();
  });

  it("can deleteItem", async () => {
    const propsData = {
      isEditMode: true,
      existingItem: { id: 1 }
    };
    actions.deleteItem = jest.fn().mockResolvedValue(true);
    store.hotUpdate({ actions });
    const wrapper = shallowMount(ItemUI, {
      store,
      mocks,
      stubs,
      propsData
    });

    expect(
      wrapper.find("[data-test=confirmDelete-button]").exists()
    ).toBeFalsy();

    wrapper.find("[data-test=itemDelete-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=confirmDelete-button]").isVisible()
    ).toBeTruthy();

    wrapper.find("[data-test=confirmDelete-button]").trigger("click");
    await wrapper.vm.$nextTick();
    await Promise.resolve();

    expect(actions.deleteItem).toHaveBeenCalledTimes(1);
    expect(actions.deleteItem).toHaveBeenCalledWith(expect.any(Object), 1);
    expect(wrapper.vm.$toasted.show).toHaveBeenCalledTimes(1);
    expect(
      wrapper.vm.$toasted.show
    ).toHaveBeenCalledWith("Item Successfully Deleted.", { type: "success" });
    expect(wrapper.vm.$router.push).toHaveBeenCalledTimes(1);
    expect(wrapper.vm.$router.push).toHaveBeenCalledWith({
      name: "items.index"
    });
  });
});
