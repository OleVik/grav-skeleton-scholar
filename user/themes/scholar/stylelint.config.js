const { resolve } = require("path");

module.exports = {
  ignoreDisables: true,
  plugins: [
    "stylelint-declaration-block-no-ignored-properties",
    "stylelint-declaration-strict-value",
    // resolve(
    //   __dirname,
    //   "local_modules/node_modules/stylelint-no-indistinguishable-colors"
    // ),
    "stylelint-selector-no-empty",
    "stylelint-prettier",
  ],
  extends: ["stylelint-prettier/recommended", "stylelint-config-prettier"],
  rules: {
    "plugin/declaration-block-no-ignored-properties": true,
    "scale-unlimited/declaration-strict-value": [
      ["/color/", "fill", "stroke"],
      {
        ignoreKeywords: ["currentColor", "transparent", "inherit"],
      },
    ],
    "plugin/stylelint-no-indistinguishable-colors": [
      true,
      {
        threshold: 1,
        allowEquivalentNotation: true,
      },
    ],
    "plugin/stylelint-selector-no-empty": true,
    "prettier/prettier": true,
    "string-quotes": "double",
    "no-duplicate-selectors": true,
    "color-hex-case": "lower",
    "color-hex-length": "long",
    "color-named": "never",
    "selector-combinator-space-after": "always",
    "selector-attribute-quotes": "always",
    "selector-attribute-operator-space-before": "never",
    "selector-attribute-operator-space-after": "never",
    "selector-attribute-brackets-space-inside": "never",
    "declaration-block-trailing-semicolon": "always",
    "declaration-no-important": true,
    "declaration-colon-space-before": "never",
    "declaration-colon-space-after": "always",
    "number-leading-zero": "always",
    "function-url-quotes": "always",
    "font-family-name-quotes": "always-where-recommended",
    "comment-whitespace-inside": "always",
    "selector-pseudo-class-parentheses-space-inside": "never",
    "media-feature-range-operator-space-before": "always",
    "media-feature-range-operator-space-after": "always",
    "media-feature-parentheses-space-inside": "never",
    "media-feature-colon-space-before": "never",
    "media-feature-colon-space-after": "always",
  },
};
