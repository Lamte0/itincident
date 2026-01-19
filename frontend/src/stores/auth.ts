import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { authService } from "@/services/api";
import type { User } from "@/types";

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem("token"));
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const isAdmin = computed(() => user.value?.role === "ADMIN");
  const isChefService = computed(
    () => user.value?.role === "SUPERVISEUR" || user.value?.role === "ADMIN"
  );
  const isMaintenancier = computed(() => user.value?.role === "MAINTENANCIER");
  const isUtilisateur = computed(() => user.value?.role === "AGENT");

  // Actions
  async function login(email: string, password: string) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.login({ email, password });
      token.value = response.data.token;
      user.value = response.data.user;
      localStorage.setItem("token", response.data.token);
      localStorage.setItem("user", JSON.stringify(response.data.user));
      return true;
    } catch (err: any) {
      error.value = err.response?.data?.message || "Erreur de connexion";
      return false;
    } finally {
      loading.value = false;
    }
  }

  async function register(data: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    service?: string;
    telephone?: string;
    matricule?: string;
  }) {
    loading.value = true;
    error.value = null;

    try {
      const response = await authService.register(data);
      token.value = response.data.token;
      user.value = response.data.user;
      localStorage.setItem("token", response.data.token);
      localStorage.setItem("user", JSON.stringify(response.data.user));
      return true;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || "Erreur lors de l'inscription";
      return false;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    try {
      await authService.logout();
    } catch (err) {
      // Ignorer les erreurs de déconnexion
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    }
  }

  async function fetchUser() {
    if (!token.value) return;

    loading.value = true;
    try {
      const response = await authService.me();
      user.value = response.data;
      localStorage.setItem("user", JSON.stringify(response.data));
    } catch (err) {
      // Token invalide, déconnexion
      await logout();
    } finally {
      loading.value = false;
    }
  }

  function initAuth() {
    if (token.value) {
      fetchUser();
    }
  }

  function hasRole(role: string | string[]): boolean {
    if (!user.value) return false;
    if (Array.isArray(role)) {
      return role.includes(user.value.role);
    }
    return user.value.role === role;
  }

  return {
    // State
    user,
    token,
    loading,
    error,
    // Computed
    isAuthenticated,
    isAdmin,
    isChefService,
    isMaintenancier,
    isUtilisateur,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    initAuth,
    hasRole,
  };
});
