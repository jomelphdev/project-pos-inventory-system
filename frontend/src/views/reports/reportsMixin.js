export default {
  data() {
    return {
      reader: new FileReader(),
      loadingReport: false,
    };
  },

  mounted() {
    this.reader.addEventListener("loadend", () => {
      const data = JSON.parse(this.reader.result.toString());
      this.$toasted.show(data.message, { type: "error" });
    });
  },

  methods: {
    notifyError(data) {
      this.reader.readAsText(data);
    },
    downloadExcel(data, filename) {
      const blob = new Blob([data], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      });
      let a = document.createElement("a");
      let url = window.URL.createObjectURL(blob);
      document.body.appendChild(a);
      a.setAttribute("style", "display: none");
      a.href = url;
      a.download = filename;
      a.click();
      window.URL.revokeObjectURL(url);
    },
    async handleReportResponse(response, filename) {
      const data = response.data;
      if (response.headers["content-type"].includes("json")) {
        this.notifyError(data);
        return;
      }
      this.downloadExcel(data, filename);
    },
  },
};
