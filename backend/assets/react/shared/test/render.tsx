import {QueryClient, QueryClientProvider} from '@tanstack/react-query';
import {render} from '@testing-library/react';
import type {ReactNode} from 'react';
import {
  createMemoryRouter,
  RouterProvider,
  type RouteObject,
} from 'react-router';
import {vi} from 'vitest';

type Handler = (body: unknown, url: string) => unknown;

/** A JSON response for fetch mocks. */
export function json(body: unknown, status = 200): Response {
  return new Response(body === undefined ? null : JSON.stringify(body), {
    status,
    headers: {'Content-Type': 'application/json'},
  });
}

/**
 * Stubs fetch with routes like {'GET /api/me': user, 'POST /api/users': (body) => …}. A handler may return a
 * Response (for errors); anything else is sent as 200 JSON. Unknown routes answer 404.
 */
export function mockApi(routes: Record<string, unknown | Handler>) {
  const fetchMock = vi.fn(async (url: string, init?: RequestInit) => {
    const method = init?.method ?? 'GET';
    const path = url.split('?')[0]!;
    const route = routes[`${method} ${url}`] ?? routes[`${method} ${path}`];
    if (route === undefined) {
      return json({error: 'not_found'}, 404);
    }
    const body =
      typeof init?.body === 'string'
        ? (JSON.parse(init.body) as unknown)
        : init?.body;
    const result =
      typeof route === 'function' ? (route as Handler)(body, url) : route;

    return result instanceof Response ? result : json(result);
  });
  vi.stubGlobal('fetch', fetchMock);

  return fetchMock;
}

/** The JSON body of the first call to `method url`. */
export function sentBody(
  fetchMock: ReturnType<typeof mockApi>,
  method: string,
  url: string,
): unknown {
  const call = fetchMock.mock.calls.find(
    ([u, init]) => u === url && (init?.method ?? 'GET') === method,
  );
  if (!call) {
    throw new Error(`No ${method} ${url} was sent.`);
  }

  return JSON.parse(call[1]!.body as string);
}

/** Renders with a fresh query client and a memory router (extra routes let a test see where it navigated). */
export function renderWithProviders(
  ui: ReactNode,
  {
    path = '/',
    routes = [],
    wrapper,
  }: {
    path?: string;
    routes?: RouteObject[];
    wrapper?: (props: {children: ReactNode}) => ReactNode;
  } = {},
) {
  const queryClient = new QueryClient({
    defaultOptions: {queries: {retry: false}, mutations: {retry: false}},
  });
  const router = createMemoryRouter([{path, element: ui}, ...routes], {
    initialEntries: [path],
  });
  const tree = <RouterProvider router={router} />;
  const Wrapper = wrapper;

  return render(
    <QueryClientProvider client={queryClient}>
      {Wrapper ? <Wrapper>{tree}</Wrapper> : tree}
    </QueryClientProvider>,
  );
}
