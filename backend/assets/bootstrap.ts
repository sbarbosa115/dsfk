import {startStimulusApp} from '@symfony/stimulus-bridge';

// Registers the Stimulus controllers from controllers.json (Symfony UX React's) and any in controllers/.
export const app = startStimulusApp(
  require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/,
  ),
);
