import Encore from '@symfony/webpack-encore';
import {fileURLToPath} from 'node:url';

// Configure the runtime environment when a tool reads this file without the `encore` command (Vitest, ESLint).
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  // Built into public/build and served by Symfony (Apache on cPanel): no Node on the server at runtime.
  .setOutputPath('public/build/')
  .setPublicPath('/build')
  // The whole signed-in app: one React root mounted by Symfony UX React (templates/spa.html.twig).
  .addEntry('app', './assets/app.ts')
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  // Babel 8's React preset otherwise picks dev mode from BABEL_ENV/NODE_ENV (unset during `encore production`)
  // and emits jsxDEV calls, which React's production build does not have.
  .enableReactPreset((options) => {
    options.development = !Encore.isProduction();
  })
  // .ts/.tsx compile through Babel; types are checked apart, by `npm run typecheck`.
  .enableBabelTypeScriptPreset()
  // Symfony UX React mounts components through a Stimulus controller.
  .enableStimulusBridge('./assets/controllers.json')
  .addAliases({'@': fileURLToPath(new URL('./assets/react', import.meta.url))})
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .configureBabel((config) => {
    config.plugins.push([
      'polyfill-corejs3',
      {method: 'usage-global', version: '3.49'},
    ]);
  });

export default await Encore.getWebpackConfig();
