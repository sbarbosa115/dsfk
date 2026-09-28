// Webpack's require.context, used by assets/app.ts.
interface RequireContext {
  keys(): string[];
  <T>(id: string): T;
}
declare const require: {
  context(dir: string, deep: boolean, filter: RegExp): RequireContext;
};

declare module '*.css';
