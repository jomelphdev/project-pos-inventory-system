module.exports = {
  extends: "stylelint-config-twbs-bootstrap/scss",

  rules: {
    "at-rule-no-unknown": [
      true,
      {
        ignoreAtRules: ["tailwind", "apply", "variants", "responsive", "screen"]
      }
    ],

    "scss/at-rule-no-unknown": [
      true,
      {
        ignoreAtRules: ["tailwind", "apply", "variants", "responsive", "screen"]
      }
    ],

    "scss/dollar-variable-empty-line-before": [
      "always",
      {
        except: ["first-nested", "after-comment", "after-dollar-variable"]
      }
    ],

    "declaration-empty-line-before": [
      "always",
      {
        except: ["first-nested", "after-declaration"],
        ignore: ["after-comment"]
      }
    ],

    "rule-empty-line-before": [
      "always",
      {
        except: ["first-nested"],
        ignore: ["after-comment"]
      }
    ],

    "at-rule-empty-line-before": [
      "always",
      {
        except: ["after-same-name", "first-nested"],
        ignore: ["after-comment"]
      }
    ],

    "selector-class-pattern": "^[a-z][a-z0-9_-]+$",
    "selector-id-pattern": "^[a-z][a-z0-9_-]+$"
  }
};
