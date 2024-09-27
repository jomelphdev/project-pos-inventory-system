const { shallowMount } = require("@vue/test-utils");
import ConfirmationModal from "../ConfirmationModal";
import Modal from "../Modal";

describe("ConfirmationModal", () => {
  it("emits response", async () => {
    const wrapper = shallowMount(ConfirmationModal, {
      stubs: { modal: Modal },
    });
    const closeMock = jest.spyOn(wrapper.vm, "closeModal");

    wrapper.vm.openModal();
    await wrapper.vm.$nextTick();

    await wrapper.find("[data-test=confirmation-yes-button]").trigger("click");

    expect(closeMock).toHaveBeenCalledTimes(1);

    wrapper.vm.openModal();
    await wrapper.vm.$nextTick();

    await wrapper.find("[data-test=confirmation-no-button]").trigger("click");

    expect(closeMock).toHaveBeenCalledTimes(2);
    expect(wrapper.emitted("response")).toHaveLength(2);
    expect(wrapper.emitted("response")[0]).toEqual([true]);
    expect(wrapper.emitted("response")[1]).toEqual([false]);
  });
});
