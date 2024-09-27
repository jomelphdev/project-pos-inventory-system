export default {
  // QzTray settings
  base: {
    // Styling
    // When true, adds a class to the qz-tray root element
    // All styling within the package needs this class, otherwise everything is unstyled.
    styling: false,
  },

  // QzTrayConnect settings
  connect: {
    // Retries
    // The connection will be retried x times
    retries: 5,

    // Delay
    // Wait x secs between tries
    delay: 1,
  },

  // QzTrayPrinters settings
  printers: {
    // The default printer that should be selected directly after retrieving the printers from QZ if it is present.
    // This value is only for illustration as any printers you can connect to will most likely have other names.
    defaultPrinter: "Zebra",
  },

  // QzTrayOptions settings
  // All these options are send to the printer when printing, unless overwritten.
  /** {@link qz.configs.setDefaults} */
  "print-options": {
    copies: {
      label: "Copies",
      value: 1,
      type: "number",
      hidden: false,
    }
  },

  // QzTrayInput settings
  input: {
    // The default type of input data
    // This tells qz-tray what print method to use.
    // TODO list types
    type: "raw",

    // The format to print the type in, allowed values depend op type
    // TODO list formats
    // format: 'plain',

    // Other options
    // TODO implement these options
    // options: {}

    // The default data to be printed
    data:
      "Test data for printing. If you do not expect to see this, you should add a 'qz-tray-input' element to override this value",
  },
};
