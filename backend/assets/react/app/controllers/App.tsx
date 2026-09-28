import {App} from '../App';

/**
 * The React root Symfony mounts: templates/spa.html.twig renders react_component('App') for every non-API path.
 * Symfony UX React registers every file in this folder under its name.
 */
export default function AppController() {
  return <App />;
}
