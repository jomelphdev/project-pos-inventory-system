<template>
  <div class="mt-8">
    <h3 class="rr-title-h3">Need Help?</h3>
    <p>
      See the
      <a
        href="https://help.retailright.app/#/printer-setup"
        target="_blank"
        class="rr-link-blue"
        >Printer Setup Guide</a
      >
      for information on installing drivers, and setting up qz-tray.
    </p>

    <ul class="text-sm list-disc pl-4 mt-4">
      <li>
        <a
          href="https://help.retailright.app/#/printer-setup"
          target="_blank"
          class="rr-link-gray"
        >
          Printer Setup Guide
        </a>
      </li>
      <li>
        <a :href="download.windows" target="_blank" class="rr-link-gray">
          QZ Tray 2.2.2
        </a>
        <span class="rr-field__label-optional"> Required </span>
      </li>
      <li>
        <a :href="epson.t88" target="_blank" class="rr-link-gray">
          Epson TM-T88V
        </a>
        <span class="rr-field__label-optional">
          Advanced Printer Driver 5.11
        </span>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: "QzTrayHelp",

  data() {
    return {
      download: {
        windows:
          "https://retail-right.s3-us-west-1.amazonaws.com/downloads/qz-tray-retailright-2.2.2.exe",
        mac: "https://tryretailright.com/wp-content/downloads/qz-tray-retailright-2.1.2.pkg",
      },
      addRawPrinter: {
        windows: "https://qz.io/wiki/setting-up-a-raw-printer-in-windows",
        mac: "https://qz.io/wiki/setting-up-a-raw-printer-in-osx",
      },
      epson: {
        t88: "https://epson.com/Support/Point-of-Sale/Thermal-Printers/Epson-TM-T88V-Series/s/SPT_C31CA85011",
      },
    };
  },
};
</script>
