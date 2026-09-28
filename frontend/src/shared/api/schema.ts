import type {components} from './schema.d';

/**
 * A response or request shape from the API contract (generated from the backend's OpenAPI schema by
 * `npm run api:types`). Never write these types by hand.
 */
export type Schema<Name extends keyof components['schemas']> =
  components['schemas'][Name];
