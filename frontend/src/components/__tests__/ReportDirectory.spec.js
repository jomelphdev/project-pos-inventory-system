const { shallowMount } = require("@vue/test-utils");
import { jest } from "@jest/globals";
import ReportDirectory from "../ReportDirectory";
import Vuex from "vuex";
import DataTable from "../table/DataTable";
import DataTableHeader from "../table/DataTableHeader";
import DataTableHeaderCell from "../table/DataTableHeaderCell";
import DataTableBody from "../table/DataTableBody";
import DataTableRow from "../table/DataTableRow";
import DataTableCell from "../table/DataTableCell";
import ConfirmationModal from "../ConfirmationModal";

describe("ReportDirectory", () => {
  let store;
  let actions;
  let stubs = {
    "data-table": DataTable,
    "data-table-header": DataTableHeader,
    "data-table-header-cell": DataTableHeaderCell,
    "data-table-body": DataTableBody,
    "data-table-row": DataTableRow,
    "data-table-cell": DataTableCell,
    ConfirmationModal,
  };
  let propsData = {
    report_type: "sales",
  };
  let files = [
    {
      id: 1,
      file_download_name: "Test",
      file_path: "reports/1/test.xlsx",
      from_date: "2023-05-24T07:00:00.000000Z",
      to_date: "2023-05-25T06:59:59.000000Z",
    },
  ];

  beforeEach(() => {
    actions = {
      getReportDirectories: jest
        .fn()
        .mockResolvedValue({ directories: [1, 2, 3], files: [] }),
      downloadReport: jest.fn().mockResolvedValue(),
      regenerateReport: jest.fn().mockResolvedValue(),
      deleteReport: jest.fn().mockResolvedValue(),
    };
    store = new Vuex.Store({ actions });
  });

  it("shows and hides", async () => {
    const wrapper = shallowMount(ReportDirectory, { store, propsData, stubs });

    expect(wrapper.find("[data-test=directory-table]").exists()).toBeFalsy();

    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(actions.getReportDirectories).toHaveBeenCalledTimes(1);
    expect(actions.getReportDirectories).toHaveBeenCalledWith(
      expect.any(Object),
      {
        report_type: "sales",
        store_id: null,
      }
    );
    expect(
      wrapper.find("[data-test=directory-table]").isVisible()
    ).toBeTruthy();

    wrapper.setData({
      directories: [],
      files: files,
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=directory-table]").exists()).toBeFalsy();

    actions.getReportDirectories.mockClear();
    wrapper.setData({
      dir_store_id: 1,
    });
    await wrapper.vm.$nextTick();

    expect(actions.getReportDirectories).toHaveBeenCalledTimes(1);
    expect(actions.getReportDirectories).toHaveBeenCalledWith(
      expect.any(Object),
      {
        report_type: "sales",
        store_id: 1,
      }
    );
    expect(
      wrapper.find("[data-test=directory-table]").isVisible()
    ).toBeTruthy();

    wrapper.setData({
      directories: [],
      files: [],
      loading: true,
    });
    await wrapper.vm.$nextTick();

    expect(
      wrapper.find("[data-test=directory-table]").isVisible()
    ).toBeTruthy();
    expect(wrapper.find("[data-test=no-reports]").exists()).toBeFalsy();

    wrapper.setData({
      directories: [],
      loading: false,
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=no-reports]").isVisible()).toBeTruthy();

    actions.getReportDirectories.mockClear();
    wrapper.setData({
      dir_store_id: null,
    });
    await wrapper.vm.$nextTick();

    expect(actions.getReportDirectories).toHaveBeenCalledTimes(1);
    expect(actions.getReportDirectories).toHaveBeenCalledWith(
      expect.any(Object),
      {
        report_type: "sales",
        store_id: null,
      }
    );
    expect(wrapper.find("[data-test=directory-table]").exists()).toBeFalsy();
  });

  it("can go back from file directory", async () => {
    const wrapper = shallowMount(ReportDirectory, { store, propsData, stubs });
    wrapper.setData({ dir_store_id: 1 });
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    wrapper.find("[data-test=back-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.dir_store_id).toBeNull();
    expect(wrapper.find("[data-test=back-button]").exists()).toBeFalsy();
  });

  it("shows correct cells", async () => {
    const wrapper = shallowMount(ReportDirectory, { store, propsData, stubs });
    wrapper.setData({
      dir_store_id: 1,
    });
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=fromDate-header]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=toDate-header]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=toDate-cell]").exists()).toBeFalsy();

    wrapper.setData({
      files,
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=fromDate-header]").exists()).toBeTruthy();
    expect(wrapper.find("[data-test=toDate-header]").exists()).toBeTruthy();
    expect(wrapper.find("[data-test=toDate-cell]").exists()).toBeTruthy();

    wrapper.setProps({
      report_type: "daily_sales",
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=fromDate-header]").exists()).toBeFalsy();
    expect(wrapper.find("[data-test=toDate-header]").exists()).toBeTruthy();
    expect(wrapper.find("[data-test=toDate-cell]").exists()).toBeFalsy();
  });

  it("hover actions work", async () => {
    const wrapper = shallowMount(ReportDirectory, { store, propsData, stubs });
    wrapper.setData({
      dir_store_id: 1,
    });
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    wrapper.setData({
      files,
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find("[data-test=files-row-1]").isVisible()).toBeTruthy();

    await wrapper.find("[data-test=files-row-1]").trigger("mouseenter");

    expect(wrapper.vm.hover).toEqual(1);
    expect(wrapper.find("[data-test=file-hover]").isVisible()).toBeTruthy();

    await wrapper.find("[data-test=download-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(actions.downloadReport).toHaveBeenCalledTimes(1);
    expect(actions.downloadReport).toHaveBeenCalledWith(expect.any(Object), {
      path: files[0].file_path,
      filename: files[0].file_download_name,
    });

    await wrapper.find("[data-test=regenerate-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(actions.regenerateReport).toHaveBeenCalledTimes(1);
    expect(actions.regenerateReport).toHaveBeenCalledWith(expect.any(Object), {
      id: 1,
    });

    wrapper.vm.$refs.confirmationModal.openModal = jest
      .fn()
      .mockImplementation();
    await wrapper.find("[data-test=delete-button]").trigger("click");
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.file_id).toEqual(1);
    expect(wrapper.vm.$refs.confirmationModal.openModal).toHaveBeenCalledTimes(
      1
    );

    wrapper.vm.handleResponse(false);
    await wrapper.vm.$nextTick();

    expect(actions.deleteReport).toHaveBeenCalledTimes(0);

    actions.getReportDirectories.mockClear();
    wrapper.vm.handleResponse(true);
    await wrapper.vm.$nextTick();
    await Promise.resolve();

    expect(actions.deleteReport).toHaveBeenCalledTimes(1);
    expect(actions.deleteReport).toHaveBeenCalledWith(expect.any(Object), {
      id: 1,
    });
    expect(actions.getReportDirectories).toHaveBeenCalledTimes(1);
    expect(actions.getReportDirectories).toHaveBeenCalledWith(
      expect.any(Object),
      {
        report_type: "sales",
        store_id: 1,
      }
    );

    await wrapper.find("[data-test=files-row-1]").trigger("mouseleave");

    expect(wrapper.vm.hover).toBeNull();
  });
});
