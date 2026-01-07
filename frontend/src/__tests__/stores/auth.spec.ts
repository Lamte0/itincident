import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useAuthStore } from "@/stores/auth";
import { authService } from "@/services/api";

// Mock du service API
vi.mock("@/services/api", () => ({
  authService: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}));

describe("Auth Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    localStorage.clear();
  });

  describe("État initial", () => {
    it("doit avoir un utilisateur null par défaut", () => {
      const store = useAuthStore();
      expect(store.user).toBeNull();
    });

    it("doit avoir isAuthenticated à false sans token", () => {
      const store = useAuthStore();
      expect(store.isAuthenticated).toBe(false);
    });

    it("doit avoir loading à false par défaut", () => {
      const store = useAuthStore();
      expect(store.loading).toBe(false);
    });
  });

  describe("login", () => {
    it("doit connecter un utilisateur avec succès", async () => {
      const mockUser = {
        id: 1,
        name: "Test User",
        email: "test@example.com",
        role: "UTILISATEUR" as const,
        is_active: true,
      };
      const mockResponse = {
        data: {
          token: "test-token",
          user: mockUser,
        },
      };

      vi.mocked(authService.login).mockResolvedValue(mockResponse);

      const store = useAuthStore();
      const result = await store.login("test@example.com", "password");

      expect(result).toBe(true);
      expect(store.user).toEqual(mockUser);
      expect(store.token).toBe("test-token");
      expect(store.isAuthenticated).toBe(true);
      expect(localStorage.getItem("token")).toBe("test-token");
    });

    it("doit gérer les erreurs de connexion", async () => {
      const mockError = {
        response: {
          data: {
            message: "Identifiants incorrects",
          },
        },
      };

      vi.mocked(authService.login).mockRejectedValue(mockError);

      const store = useAuthStore();
      const result = await store.login("test@example.com", "wrong-password");

      expect(result).toBe(false);
      expect(store.error).toBe("Identifiants incorrects");
      expect(store.isAuthenticated).toBe(false);
    });

    it("doit mettre loading à true pendant la connexion", async () => {
      vi.mocked(authService.login).mockImplementation(
        () =>
          new Promise((resolve) =>
            setTimeout(
              () =>
                resolve({
                  data: { token: "test", user: { id: 1, role: "UTILISATEUR" } },
                }),
              100
            )
          )
      );

      const store = useAuthStore();
      const loginPromise = store.login("test@example.com", "password");

      expect(store.loading).toBe(true);
      await loginPromise;
      expect(store.loading).toBe(false);
    });
  });

  describe("register", () => {
    it("doit inscrire un utilisateur avec succès", async () => {
      const mockUser = {
        id: 1,
        name: "New User",
        email: "new@example.com",
        role: "UTILISATEUR" as const,
        is_active: true,
      };
      const mockResponse = {
        data: {
          token: "new-token",
          user: mockUser,
        },
      };

      vi.mocked(authService.register).mockResolvedValue(mockResponse);

      const store = useAuthStore();
      const result = await store.register({
        name: "New User",
        email: "new@example.com",
        password: "password123",
        password_confirmation: "password123",
      });

      expect(result).toBe(true);
      expect(store.user).toEqual(mockUser);
      expect(store.token).toBe("new-token");
    });

    it("doit gérer les erreurs d'inscription", async () => {
      const mockError = {
        response: {
          data: {
            message: "Email déjà utilisé",
          },
        },
      };

      vi.mocked(authService.register).mockRejectedValue(mockError);

      const store = useAuthStore();
      const result = await store.register({
        name: "New User",
        email: "existing@example.com",
        password: "password123",
        password_confirmation: "password123",
      });

      expect(result).toBe(false);
      expect(store.error).toBe("Email déjà utilisé");
    });
  });

  describe("logout", () => {
    it("doit déconnecter l'utilisateur", async () => {
      vi.mocked(authService.logout).mockResolvedValue({});

      const store = useAuthStore();
      store.user = { id: 1, name: "Test", role: "UTILISATEUR" } as any;
      store.token = "test-token";

      await store.logout();

      expect(store.user).toBeNull();
      expect(store.token).toBeNull();
      expect(localStorage.getItem("token")).toBeNull();
    });

    it("doit déconnecter même si l'API échoue", async () => {
      vi.mocked(authService.logout).mockRejectedValue(
        new Error("Network error")
      );

      const store = useAuthStore();
      store.user = { id: 1, name: "Test", role: "UTILISATEUR" } as any;
      store.token = "test-token";

      await store.logout();

      expect(store.user).toBeNull();
      expect(store.token).toBeNull();
    });
  });

  describe("Computed properties", () => {
    it("isAdmin doit retourner true pour un ADMIN", () => {
      const store = useAuthStore();
      store.user = { id: 1, role: "ADMIN" } as any;
      store.token = "test-token";

      expect(store.isAdmin).toBe(true);
      expect(store.isChefService).toBe(true);
    });

    it("isChefService doit retourner true pour un CHEF_SERVICE", () => {
      const store = useAuthStore();
      store.user = { id: 1, role: "CHEF_SERVICE" } as any;

      expect(store.isChefService).toBe(true);
      expect(store.isAdmin).toBe(false);
    });

    it("isMaintenancier doit retourner true pour un MAINTENANCIER", () => {
      const store = useAuthStore();
      store.user = { id: 1, role: "MAINTENANCIER" } as any;

      expect(store.isMaintenancier).toBe(true);
    });

    it("isUtilisateur doit retourner true pour un UTILISATEUR", () => {
      const store = useAuthStore();
      store.user = { id: 1, role: "UTILISATEUR" } as any;

      expect(store.isUtilisateur).toBe(true);
    });
  });

  describe("hasRole", () => {
    it("doit retourner true si l'utilisateur a le rôle demandé", () => {
      const store = useAuthStore();
      store.user = { id: 1, role: "ADMIN" } as any;

      expect(store.hasRole("ADMIN")).toBe(true);
      expect(store.hasRole("UTILISATEUR")).toBe(false);
    });

    it("doit accepter un tableau de rôles", () => {
      const store = useAuthStore();
      store.user = { id: 1, role: "CHEF_SERVICE" } as any;

      expect(store.hasRole(["ADMIN", "CHEF_SERVICE"])).toBe(true);
      expect(store.hasRole(["ADMIN", "MAINTENANCIER"])).toBe(false);
    });

    it("doit retourner false si pas d'utilisateur", () => {
      const store = useAuthStore();
      expect(store.hasRole("ADMIN")).toBe(false);
    });
  });
});
