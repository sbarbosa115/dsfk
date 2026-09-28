// The app's single entry: styles, the Stimulus bridge that Symfony UX React uses, and the React components that
// Twig may mount with react_component() (only App, from the app layer).
import {registerReactControllerComponents} from '@symfony/ux-react';
import './bootstrap';
import './styles/app.css';

registerReactControllerComponents(
  require.context('./react/app/controllers', true, /\.tsx$/),
);
