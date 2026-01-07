import { config } from "@vue/test-utils";
import { vi } from "vitest";

// Mock du router
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn(),
  back: vi.fn(),
  forward: vi.fn(),
  go: vi.fn(),
  currentRoute: {
    value: {
      params: {},
      query: {},
      path: "/",
      name: "home",
    },
  },
};

const mockRoute = {
  params: {},
  query: {},
  path: "/",
  name: "home",
};

// Configuration globale pour tous les tests
config.global.mocks = {
  $router: mockRouter,
  $route: mockRoute,
};

// Mock des méthodes globales
vi.mock("vue-router", () => ({
  useRouter: () => mockRouter,
  useRoute: () => mockRoute,
}));

// Mock de window.matchMedia pour les tests de responsive
Object.defineProperty(window, "matchMedia", {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock de ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}));

// Mock de IntersectionObserver
global.IntersectionObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}));

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(window, "localStorage", { value: localStorageMock });

// Cleanup après chaque test
afterEach(() => {
  vi.clearAllMocks();
});
