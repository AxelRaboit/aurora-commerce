const globals = require('globals');
const pluginVue = require('eslint-plugin-vue');
const prettierPlugin = require('eslint-plugin-prettier');

/** @type {import('eslint').Linter.FlatConfig[]} */
module.exports = [
    {
        ignores: ['node_modules/**', 'vendor/**', 'public/**', 'assets/vendor/**', 'tools/**', 'vite.config.js'],
    },

    // JS files — Prettier formatting
    {
        files: ['**/*.js'],
        plugins: { prettier: prettierPlugin },
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.browser,
        },
        rules: {
            semi: 'error',
            'prefer-const': 'error',
            'prettier/prettier': 'error',
        },
    },

    // Vue files — Vue rules
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['**/*.vue'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.browser,
        },
        rules: {
            semi: 'error',
            'prefer-const': 'error',
            'vue/multi-word-component-names': 'off',
            'vue/v-on-style': ['error', 'longform'],
            'vue/v-bind-style': ['error', 'shorthand'],
            'vue/html-indent': ['warn', 4],
            'vue/script-indent': ['warn', 4],
            'vue/max-attributes-per-line': ['warn', { singleline: 4, multiline: 1 }],
            'vue/singleline-html-element-content-newline': 'off',
            'vue/component-definition-name-casing': ['error', 'PascalCase'],
            'vue/require-prop-types': 'warn',
            'vue/require-default-prop': 'off',
            'vue/no-v-html': 'off',
            'vue/attributes-order': 'warn',
        },
    },
];
