// ESLint: correctness, React, the non-layout Google rules and Feature-Sliced Design imports. Prettier owns
// layout, so eslint-config-prettier goes last.
import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import tseslint from 'typescript-eslint';
import fsd from './eslint-fsd-boundaries.mjs';
import google from './eslint-google-rules.mjs';

export default tseslint.config(
  {
    ignores: [
      'e2e/.results/**',
      'public/**',
      'node_modules/**',
      'vendor/**',
      'var/**',
      'assets/react/shared/api/schema.d.ts',
    ],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    files: ['assets/**/*.{ts,tsx}'],
    languageOptions: {globals: globals.browser},
    plugins: {'react': react, 'react-hooks': reactHooks},
    settings: {react: {version: 'detect'}},
    rules: {
      ...react.configs.recommended.rules,
      ...react.configs['jsx-runtime'].rules,
      ...reactHooks.configs.recommended.rules,
      // A missing import is a blank screen at runtime, not a build error.
      'react/jsx-no-undef': 'error',
      'react/prop-types': 'off',
    },
  },
  {
    // Build and tool configs run in Node.
    files: ['*.{js,mjs,mts}'],
    languageOptions: {globals: globals.node},
  },
  ...google,
  ...fsd,
  prettier,
);
