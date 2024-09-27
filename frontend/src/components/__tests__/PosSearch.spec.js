const { shallowMount, mount, createLocalVue } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import Axios from "axios";
import PosSearch from "../PosSearch";
import store from "@/store";

describe("PosSearch", () => {
  it("shows toasted notif on invalid query", () => {
    const spy = jest.fn();
    const wrapper = shallowMount(PosSearch, {
      mocks: {
        $toasted: {
          show: spy
        }
      }
    });

    const input = wrapper.find("[data-test=pos-search-input]");

    input.setValue("1");
    input.trigger("keyup.enter");

    expect(spy).toBeCalledTimes(1);
    expect(spy).toBeCalledWith("Invalid format.", { type: "error" });
  });

  it("can retrieve item by SKU", async () => {
    jest.spyOn(Axios, "post").mockClear();
    jest
      .spyOn(Axios, "post")
      .mockResolvedValue({ data: { data: { item: { title: "test" } } } });
    const wrapper = shallowMount(PosSearch, { store });

    const input = wrapper.find("[data-test=pos-search-input]");
    input.setValue("1234567890");
    await new Promise(r => setTimeout(r, 600));

    expect(Axios.post).toBeCalledTimes(1);
    expect(Axios.post).toBeCalledWith("/items/query/sku", {
      sku: "1234567890"
    });
  });

  it("can emit item-found by SKU", async () => {
    const wrapper = shallowMount(PosSearch, {
      mocks: {
        $store: {
          dispatch: jest
            .fn()
            .mockImplementation(() => Promise.resolve({ title: "test" }))
        }
      }
    });

    const input = wrapper.find("[data-test=pos-search-input]");
    input.setValue("1234567890");
    await new Promise(r => setTimeout(r, 600));

    expect(wrapper.vm.$store.dispatch).toBeCalledTimes(1);
    expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
      "getItemBySku",
      "1234567890"
    );
    expect(wrapper.emitted("item-found")).toHaveLength(1);
    expect(wrapper.emitted("item-found")[0]).toEqual([{ title: "test" }]);
  });

  it("can retrieve item by UPC", async () => {
    jest.spyOn(Axios, "post").mockClear();
    jest
      .spyOn(Axios, "post")
      .mockResolvedValue({ data: { data: { items: [{ title: "test" }] } } });
    const wrapper = shallowMount(PosSearch, {
      store,
      computed: {
        posStore: () => {
          return { id: 1 };
        }
      }
    });

    const input = wrapper.find("[data-test=pos-search-input]");
    input.setValue("123456789012");
    await new Promise(r => setTimeout(r, 600));

    expect(Axios.post).toBeCalledTimes(1);
    expect(Axios.post).toBeCalledWith("/items/query/upc", {
      upc: "123456789012",
      options: {
        with_quantities: true,
        only_for_store_id: 1
      }
    });
  });

  it("can emit item-found by UPC", async () => {
    const wrapper = shallowMount(PosSearch, {
      mocks: {
        $store: {
          dispatch: jest
            .fn()
            .mockImplementation(() => Promise.resolve([{ title: "test" }]))
        }
      },
      computed: {
        posStore: () => {
          return { id: 1 };
        }
      }
    });

    const input = wrapper.find("[data-test=pos-search-input]");
    input.setValue("123456789012");
    await new Promise(r => setTimeout(r, 600));

    expect(wrapper.vm.$store.dispatch).toBeCalledTimes(1);
    expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("getItemsByUpc", {
      options: { only_for_store_id: 1, with_quantities: true },
      upc: "123456789012"
    });
    expect(wrapper.emitted("item-found")).toHaveLength(1);
    expect(wrapper.emitted("item-found")[0]).toEqual([{ title: "test" }]);
  });

  it("can openModal when multiple items return by UPC", async () => {
    const localVue = createLocalVue();
    localVue.filter("truncate", (value, length) => {
      length = length || 15;
      if (!value || typeof value !== "string") return "";
      if (value.length <= length) return value;
      return value.substring(0, length) + "...";
    });
    const wrapper = mount(PosSearch, {
      mocks: {
        $store: {
          dispatch: jest.fn().mockImplementation(() =>
            Promise.resolve([
              { title: "test", images: [] },
              { title: "test2", images: [] }
            ])
          )
        }
      },
      computed: {
        posStore: () => {
          return { id: 1 };
        }
      },
      localVue
    });

    const input = wrapper.find("[data-test=pos-search-input]");
    input.setValue("123456789012");
    await new Promise(r => setTimeout(r, 600));

    expect(wrapper.vm.upcResults).toHaveLength(2);
  });

  it("can emit item-found from upcResultsModal", async () => {
    const localVue = createLocalVue();
    localVue.filter("truncate", (value, length) => {
      length = length || 15;
      if (!value || typeof value !== "string") return "";
      if (value.length <= length) return value;
      return value.substring(0, length) + "...";
    });
    const wrapper = mount(PosSearch, {
      mocks: {
        $store: {
          dispatch: jest.fn().mockImplementation(() =>
            Promise.resolve([
              { title: "test", images: [] },
              { title: "test2", images: [] }
            ])
          )
        }
      },
      computed: {
        posStore: () => {
          return { id: 1 };
        }
      },
      localVue
    });

    const input = wrapper.find("[data-test=pos-search-input]");
    input.setValue("123456789012");
    await new Promise(r => setTimeout(r, 600));

    wrapper.find("[data-test=upc-results-modal-item-1]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted("item-found")).toHaveLength(1);
    expect(wrapper.emitted("item-found")[0]).toEqual([
      { title: "test2", images: [] }
    ]);
  });

  it("shows toasted notif on no UPC items found", async () => {
    const spy = jest.fn();
    const wrapper = shallowMount(PosSearch, {
      mocks: {
        $toasted: {
          show: spy
        },
        $store: {
          dispatch: jest.fn().mockImplementation(() => Promise.resolve([]))
        }
      },
      computed: {
        posStore: () => {
          return { id: 1 };
        }
      }
    });

    const input = wrapper.find("[data-test=pos-search-input]");

    input.setValue("123456789012");
    await new Promise(r => setTimeout(r, 600));

    expect(spy).toBeCalledTimes(1);
    expect(spy).toBeCalledWith("No items with this UPC in this store.", {
      type: "error"
    });
  });

  it("can fireQuery", async () => {
    const mockMethod = jest
      .spyOn(PosSearch.methods, "queryItems")
      .mockReturnValue();
    const wrapper = shallowMount(PosSearch, { store });

    wrapper.setProps({ fireQuery: true });
    await wrapper.vm.$nextTick();

    expect(mockMethod).toHaveBeenCalledTimes(1);
    expect(wrapper.emitted("update:fireQuery")).toHaveLength(1);
    expect(wrapper.emitted("update:fireQuery")[0]).toEqual([false]);
  });
});
