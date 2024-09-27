import { shallowMount } from "@vue/test-utils";
import { jest } from "@jest/globals";
import PosItemTable from "../PosItemTable";

describe("PosItemTable", () => {
  it("shows loading when no items", async () => {
    const propsData = {
      items: []
    };
    const wrapper = shallowMount(PosItemTable, { propsData });

    expect(
      wrapper.find("[data-test=pos-item-table-loading]").isVisible()
    ).toBeTruthy();
    expect(wrapper.find("[data-test=pos-item-table]").exists()).toBeFalsy();

    await wrapper.setProps({
      items: [{ id: 1 }]
    });

    expect(
      wrapper.find("[data-test=pos-item-table-loading]").exists()
    ).toBeFalsy();
    expect(wrapper.find("[data-test=pos-item-table]").isVisible()).toBeTruthy();
  });

  it("can select an item", () => {
    const propsData = {
      items: [{ id: 1 }]
    };
    const wrapper = shallowMount(PosItemTable, { propsData });

    wrapper.find("[data-test=pos-item-table-item-0]").trigger("click");

    expect(wrapper.emitted()).toHaveProperty("update:selectedItem");
  });

  it("ignores selection when item has been returned", () => {
    const propsData = {
      items: [
        {
          id: 1,
          quantity_left_to_return: 0
        }
      ]
    };
    const wrapper = shallowMount(PosItemTable, { propsData });

    jest.spyOn(console, "error").mockImplementation(jest.fn());
    wrapper.find("[data-test=pos-item-table-item-0]").trigger("click");
    jest.spyOn(console, "error").mockRestore();

    expect(wrapper.emitted("update:selectedItem")).toBeUndefined();
  });
});
