const { shallowMount } = require("@vue/test-utils");
import ItemNavigation from "../ItemNavigation";

describe("ItemNavigation", () => {
  it("can change tab", async () => {
    const wrapper = shallowMount(ItemNavigation);

    wrapper.find("[data-test=shipping-tab]").trigger("click");
    await wrapper.vm.$nextTick();
    wrapper.find("[data-test=details-tab]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted("change")).toHaveLength(2);
    expect(wrapper.emitted("change")[0]).toEqual(["shipping"]);
    expect(wrapper.emitted("change")[1]).toEqual(["details"]);
  });
});
