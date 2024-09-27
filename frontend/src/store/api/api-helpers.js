import store from "../index";

const reader = new window.FileReader();
reader.addEventListener("loadend", () => {
  const data = JSON.parse(reader.result.toString());
  const type = data.success ? "success" : "error";

  store.dispatch("pushNotifications", {
    text: data.message,
    type: type
  });
});

export function basicCatch(err, msg) {
  console.log(err);
  msg = err.data && err.data.message ? err.data.message : msg;

  store.dispatch("pushNotifications", {
    text: msg,
    type: "error"
  });
}

export function notifyError(data) {
  reader.readAsText(data);
}

export function downloadExcel(data, filename) {
  const blob = new Blob([data], {
    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
  });
  let a = document.createElement("a");
  let url = window.URL.createObjectURL(blob);

  document.body.appendChild(a);
  a.setAttribute("style", "display: none");
  a.href = url;
  a.download = filename;
  a.click();
  window.URL.revokeObjectURL(url);

  store.dispatch("setLoadingReport", false);
  store.dispatch("pushNotifications", {
    text: `Report: ${filename} downloaded.`,
    type: "success"
  });
}

export async function handleReportResponse(response, filename) {
  const data = response.data;

  if (response.headers["content-type"].includes("json")) {
    notifyError(data);
    return;
  }

  downloadExcel(data, filename);
}
