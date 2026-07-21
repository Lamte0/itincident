<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { userService } from "@/services/api";
import type { User, UserRole } from "@/types";
import {
  MagnifyingGlassIcon,
  PlusIcon,
  PencilSquareIcon,
  TrashIcon,
  KeyIcon,
  UserCircleIcon,
  CheckCircleIcon,
  XCircleIcon,
  FunnelIcon,
} from "@heroicons/vue/24/outline";

// State
const users = ref<User[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const searchQuery = ref("");
const selectedRole = ref("");
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
});

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showResetPasswordModal = ref(false);
const selectedUser = ref<User | null>(null);
const actionLoading = ref(false);

// Formulaires
const createForm = ref({
  name: "",
  email: "",
  password: "",
  role: "UTILISATEUR" as UserRole,
  service: "",
  telephone: "",
  matricule: "",
});

const editForm = ref({
  name: "",
  email: "",
  role: "UTILISATEUR" as UserRole,
  service: "",
  telephone: "",
  matricule: "",
  is_active: true,
});

const resetPasswordForm = ref({
  password: "",
  password_confirmation: "",
});

// Roles disponibles
const roles: { value: UserRole; label: string }[] = [
  { value: "UTILISATEUR", label: "Utilisateur" },
  { value: "MAINTENANCIER", label: "Maintenancier" },
  { value: "CHEF_SERVICE", label: "Chef de Service" },
  { value: "ADMIN", label: "Administrateur" },
];

// Computed
const filteredUsers = computed(() => {
  return users.value;
});

// Méthodes
async function fetchUsers(page = 1) {
  loading.value = true;
  error.value = null;

  try {
    const params: Record<string, string | number> = { page };
    if (selectedRole.value) params.role = selectedRole.value;
    if (searchQuery.value) params.search = searchQuery.value;

    const response = await userService.getAll(params);
    users.value = response.data.data;
    pagination.value = {
      currentPage: response.data.current_page,
      lastPage: response.data.last_page,
      total: response.data.total,
    };
  } catch (err: any) {
    error.value = err.response?.data?.message || "Erreur lors du chargement";
  } finally {
    loading.value = false;
  }
}

async function createUser() {
  actionLoading.value = true;
  try {
    await userService.create(createForm.value);
    showCreateModal.value = false;
    resetCreateForm();
    await fetchUsers();
  } catch (err: any) {
    error.value = err.response?.data?.message || "Erreur lors de la création";
  } finally {
    actionLoading.value = false;
  }
}

async function updateUser() {
  if (!selectedUser.value) return;

  actionLoading.value = true;
  try {
    await userService.update(selectedUser.value.id, editForm.value);
    showEditModal.value = false;
    await fetchUsers();
  } catch (err: any) {
    error.value =
      err.response?.data?.message || "Erreur lors de la mise à jour";
  } finally {
    actionLoading.value = false;
  }
}

async function deleteUser() {
  if (!selectedUser.value) return;

  actionLoading.value = true;
  try {
    await userService.delete(selectedUser.value.id);
    showDeleteModal.value = false;
    selectedUser.value = null;
    await fetchUsers();
  } catch (err: any) {
    error.value =
      err.response?.data?.message || "Erreur lors de la suppression";
  } finally {
    actionLoading.value = false;
  }
}

async function resetPassword() {
  if (!selectedUser.value) return;
  if (
    resetPasswordForm.value.password !==
    resetPasswordForm.value.password_confirmation
  ) {
    error.value = "Les mots de passe ne correspondent pas";
    return;
  }

  actionLoading.value = true;
  try {
    await userService.resetPassword(selectedUser.value.id, {
      password: resetPasswordForm.value.password,
    });
    showResetPasswordModal.value = false;
    resetPasswordForm.value = { password: "", password_confirmation: "" };
  } catch (err: any) {
    error.value =
      err.response?.data?.message ||
      "Erreur lors de la réinitialisation du mot de passe";
  } finally {
    actionLoading.value = false;
  }
}

async function toggleUserStatus(user: User) {
  try {
    await userService.update(user.id, { is_active: !user.is_active });
    await fetchUsers();
  } catch (err: any) {
    error.value = err.response?.data?.message || "Erreur";
  }
}

function openEditModal(user: User) {
  selectedUser.value = user;
  editForm.value = {
    name: user.name,
    email: user.email,
    role: user.role,
    service: user.service || "",
    telephone: user.telephone || "",
    matricule: user.matricule || "",
    is_active: user.is_active,
  };
  showEditModal.value = true;
}

function openDeleteModal(user: User) {
  selectedUser.value = user;
  showDeleteModal.value = true;
}

function openResetPasswordModal(user: User) {
  selectedUser.value = user;
  resetPasswordForm.value = { password: "", password_confirmation: "" };
  showResetPasswordModal.value = true;
}

function resetCreateForm() {
  createForm.value = {
    name: "",
    email: "",
    password: "",
    role: "UTILISATEUR",
    service: "",
    telephone: "",
    matricule: "",
  };
}

function getRoleBadge(role: UserRole): string {
  const badges: Record<UserRole, string> = {
    ADMIN: "bg-purple-50 text-purple-700 border border-purple-100",
    CHEF_SERVICE: "bg-blue-50 text-blue-700 border border-blue-100",
    MAINTENANCIER: "bg-green-50 text-green-700 border border-green-100",
    UTILISATEUR: "bg-slate-100 text-slate-700 border border-slate-200",
  };
  return badges[role] || "bg-slate-100 text-slate-700 border border-slate-200";
}

function getRoleLabel(role: UserRole): string {
  const labels: Record<UserRole, string> = {
    ADMIN: "Admin",
    CHEF_SERVICE: "Chef Service",
    MAINTENANCIER: "Maintenancier",
    UTILISATEUR: "Utilisateur",
  };
  return labels[role] || role;
}

function getInitials(name: string): string {
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
}

// Watchers
watch([searchQuery, selectedRole], () => {
  fetchUsers(1);
});

onMounted(() => {
  fetchUsers();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
    >
      <div class="space-y-1">
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
          Gestion des utilisateurs
        </h1>
        <p class="text-sm text-slate-500 font-medium">
          {{ pagination.total }} utilisateur(s) au total
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-indigo-600/10 gap-2"
      >
        <PlusIcon class="h-4.5 w-4.5" />
        Nouvel utilisateur
      </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Recherche -->
        <div class="flex-1 relative">
          <MagnifyingGlassIcon
            class="absolute left-3.5 top-1/2 transform -translate-y-1/2 h-4.5 w-4.5 text-slate-400"
          />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher par nom, email, matricule..."
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          />
        </div>

        <!-- Filtre par rôle -->
        <div class="flex items-center gap-2">
          <FunnelIcon class="h-4.5 w-4.5 text-slate-400" />
          <select
            v-model="selectedRole"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all min-w-[160px]"
          >
            <option value="">Tous les rôles</option>
            <option v-for="role in roles" :key="role.value" :value="role.value">
              {{ role.label }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Message d'erreur -->
    <div
      v-if="error"
      class="bg-red-50 border border-red-100 text-red-650 p-4 rounded-2xl text-sm font-semibold animate-fade-in-up flex items-center justify-between"
    >
      <span>{{ error }}</span>
      <button @click="error = null" class="text-slate-400 hover:text-slate-650 transition-colors font-bold text-lg leading-none">
        &times;
      </button>
    </div>

    <!-- Liste des utilisateurs -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
      <div v-if="loading" class="flex flex-col items-center justify-center py-16 gap-2">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <p class="text-xs text-slate-450 font-semibold">Chargement...</p>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/75">
              <tr>
                <th
                  class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Utilisateur
                </th>
                <th
                  class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Rôle
                </th>
                <th
                  class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Service
                </th>
                <th
                  class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Statut
                </th>
                <th
                  class="px-6 py-3.5 text-right text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
              <tr
                v-for="user in filteredUsers"
                :key="user.id"
                class="hover:bg-slate-50/50 transition-colors"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div
                      class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center font-bold text-sm"
                    >
                      <span>
                        {{ getInitials(user.name) }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-bold text-slate-700">
                        {{ user.name }}
                      </div>
                      <div class="text-xs font-semibold text-slate-450">{{ user.email }}</div>
                      <div v-if="user.matricule" class="text-[11px] font-bold text-indigo-650 mt-0.5 tracking-wider">
                        Matricule : {{ user.matricule }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getRoleBadge(user.role)]"
                  >
                    {{ getRoleLabel(user.role) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                  {{ user.service || "-" }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <button
                    @click="toggleUserStatus(user)"
                    :class="
                      user.is_active
                        ? 'text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100/50 border border-emerald-100'
                        : 'text-red-650 hover:text-red-700 bg-red-50 hover:bg-red-100/55 border border-red-100'
                    "
                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold transition-colors"
                  >
                    <CheckCircleIcon v-if="user.is_active" class="h-4 w-4 mr-1" />
                    <XCircleIcon v-else class="h-4 w-4 mr-1" />
                    {{ user.is_active ? "Actif" : "Inactif" }}
                  </button>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                >
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openEditModal(user)"
                      class="inline-flex text-slate-400 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
                      title="Modifier"
                    >
                      <PencilSquareIcon class="h-4.5 w-4.5" />
                    </button>
                    <button
                      @click="openResetPasswordModal(user)"
                      class="inline-flex text-slate-400 hover:text-amber-600 bg-slate-50 hover:bg-amber-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
                      title="Réinitialiser mot de passe"
                    >
                      <KeyIcon class="h-4.5 w-4.5" />
                    </button>
                    <button
                      @click="openDeleteModal(user)"
                      class="inline-flex text-slate-400 hover:text-red-650 bg-slate-50 hover:bg-red-55 border border-slate-150 rounded-lg p-1.5 transition-colors"
                      title="Supprimer"
                    >
                      <TrashIcon class="h-4.5 w-4.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500 font-medium">
                  Aucun utilisateur trouvé.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="pagination.lastPage > 1"
          class="bg-white px-6 py-4 flex items-center justify-between border-t border-slate-100"
        >
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="fetchUsers(pagination.currentPage - 1)"
              :disabled="pagination.currentPage === 1"
              class="px-4 py-2 border border-slate-200 text-sm font-semibold rounded-xl text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors"
            >
              Précédent
            </button>
            <button
              @click="fetchUsers(pagination.currentPage + 1)"
              :disabled="pagination.currentPage === pagination.lastPage"
              class="px-4 py-2 border border-slate-200 text-sm font-semibold rounded-xl text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 transition-colors"
            >
              Suivant
            </button>
          </div>
          <div
            class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
          >
            <div>
              <p class="text-sm text-slate-500 font-medium">
                Page
                <span class="font-bold text-slate-800">{{ pagination.currentPage }}</span>
                sur
                <span class="font-bold text-slate-800">{{ pagination.lastPage }}</span>
              </p>
            </div>
            <div class="flex gap-1.5">
              <button
                v-for="page in pagination.lastPage"
                :key="page"
                @click="fetchUsers(page)"
                :class="
                  page === pagination.currentPage
                    ? 'bg-indigo-600 text-white border-transparent'
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                "
                class="px-3 py-1.5 border text-sm font-semibold rounded-lg transition-colors"
              >
                {{ page }}
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal Créer utilisateur -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div
        class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-fade-in-up"
      >
        <div class="p-6 space-y-6">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">
              Nouvel utilisateur
            </h3>
            <button
              @click="showCreateModal = false"
              class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <span class="text-xl font-bold leading-none">&times;</span>
            </button>
          </div>

          <form @submit.prevent="createUser" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Nom complet *</label
              >
              <input
                v-model="createForm.name"
                type="text"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Email *</label
              >
              <input
                v-model="createForm.email"
                type="email"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Mot de passe *</label
              >
              <input
                v-model="createForm.password"
                type="password"
                required
                minlength="8"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Rôle *</label
              >
              <select
                v-model="createForm.role"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              >
                <option
                  v-for="role in roles"
                  :key="role.value"
                  :value="role.value"
                >
                  {{ role.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Service</label
              >
              <input
                v-model="createForm.service"
                type="text"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Téléphone</label
              >
              <input
                v-model="createForm.telephone"
                type="tel"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Matricule</label
              >
              <input
                v-model="createForm.matricule"
                type="text"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2.5 text-sm font-semibold text-slate-750 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="actionLoading"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-indigo-600/10 disabled:opacity-50"
              >
                {{ actionLoading ? "Création..." : "Créer" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Modifier utilisateur -->
    <div
      v-if="showEditModal && selectedUser"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div
        class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-fade-in-up"
      >
        <div class="p-6 space-y-6">
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">
              Modifier {{ selectedUser.name }}
            </h3>
            <button
              @click="showEditModal = false"
              class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <span class="text-xl font-bold leading-none">&times;</span>
            </button>
          </div>

          <form @submit.prevent="updateUser" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Nom complet *</label
              >
              <input
                v-model="editForm.name"
                type="text"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Email *</label
              >
              <input
                v-model="editForm.email"
                type="email"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Rôle *</label
              >
              <select
                v-model="editForm.role"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              >
                <option
                  v-for="role in roles"
                  :key="role.value"
                  :value="role.value"
                >
                  {{ role.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Service</label
              >
              <input
                v-model="editForm.service"
                type="text"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Téléphone</label
              >
              <input
                v-model="editForm.telephone"
                type="tel"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
                >Matricule</label
              >
              <input
                v-model="editForm.matricule"
                type="text"
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              />
            </div>

            <div class="flex items-center py-2">
              <input
                v-model="editForm.is_active"
                type="checkbox"
                id="is_active"
                class="h-4 w-4 text-indigo-650 focus:ring-indigo-500/25 border-slate-200 rounded transition-all"
              />
              <label for="is_active" class="ml-2.5 block text-sm font-semibold text-slate-700 select-none">
                Compte actif
              </label>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-4 py-2.5 text-sm font-semibold text-slate-750 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="actionLoading"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-indigo-600/10 disabled:opacity-50"
              >
                {{ actionLoading ? "Mise à jour..." : "Enregistrer" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Supprimer utilisateur -->
    <div
      v-if="showDeleteModal && selectedUser"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-sm w-full p-6 animate-fade-in-up space-y-6">
        <div class="flex flex-col items-center text-center">
          <div
            class="h-12 w-12 rounded-full bg-red-50 text-red-600 border border-red-100 flex items-center justify-center mb-4"
          >
            <TrashIcon class="h-6 w-6" />
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-1">
            Supprimer l'utilisateur
          </h3>
          <p class="text-sm text-slate-500 font-medium leading-relaxed">
            Êtes-vous sûr de vouloir supprimer
            <strong class="text-slate-800">{{ selectedUser.name }}</strong> ? Cette action est irréversible.
          </p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
          >
            Annuler
          </button>
          <button
            @click="deleteUser"
            :disabled="actionLoading"
            class="px-5 py-2.5 bg-red-650 hover:bg-red-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-red-600/10 disabled:opacity-50"
          >
            {{ actionLoading ? "Suppression..." : "Supprimer" }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Réinitialiser mot de passe -->
    <div
      v-if="showResetPasswordModal && selectedUser"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full p-6 animate-fade-in-up space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <h3 class="text-lg font-bold text-slate-800">
            Réinitialiser le mot de passe
          </h3>
          <button
            @click="showResetPasswordModal = false"
            class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
          >
            <span class="text-xl font-bold leading-none">&times;</span>
          </button>
        </div>

        <p class="text-sm text-slate-500 font-medium leading-relaxed">
          Définir un nouveau mot de passe sécurisé pour
          <strong class="text-slate-800">{{ selectedUser.name }}</strong>
        </p>

        <form @submit.prevent="resetPassword" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
              >Nouveau mot de passe *</label
            >
            <input
              v-model="resetPasswordForm.password"
              type="password"
              required
              minlength="8"
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5"
              >Confirmer le mot de passe *</label
            >
            <input
              v-model="resetPasswordForm.password_confirmation"
              type="password"
              required
              minlength="8"
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-750 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
            />
          </div>

          <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showResetPasswordModal = false"
              class="px-4 py-2.5 text-sm font-semibold text-slate-750 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="actionLoading"
              class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-indigo-600/10 disabled:opacity-50"
            >
              {{ actionLoading ? "Réinitialisation..." : "Réinitialiser" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
