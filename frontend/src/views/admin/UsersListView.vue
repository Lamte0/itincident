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
  role: "AGENT" as UserRole,
  service: "",
  telephone: "",
  matricule: "",
});

const editForm = ref({
  name: "",
  email: "",
  role: "AGENT" as UserRole,
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
  { value: "AGENT", label: "Agent" },
  { value: "MAINTENANCIER", label: "Maintenancier" },
  { value: "SUPERVISEUR", label: "Superviseur" },
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
    role: "AGENT",
    service: "",
    telephone: "",
    matricule: "",
  };
}

function getRoleBadge(role: UserRole): string {
  const badges: Record<UserRole, string> = {
    ADMIN: "bg-purple-100 text-purple-800",
    SUPERVISEUR: "bg-blue-100 text-blue-800",
    MAINTENANCIER: "bg-green-100 text-green-800",
    AGENT: "bg-gray-100 text-gray-800",
  };
  return badges[role] || "bg-gray-100 text-gray-800";
}

function getRoleLabel(role: UserRole): string {
  const labels: Record<UserRole, string> = {
    ADMIN: "Admin",
    SUPERVISEUR: "Superviseur",
    MAINTENANCIER: "Maintenancier",
    AGENT: "Agent",
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
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Gestion des utilisateurs
        </h1>
        <p class="text-gray-500 mt-1">
          {{ pagination.total }} utilisateur(s) au total
        </p>
      </div>
      <button
        @click="showCreateModal = true"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
      >
        <PlusIcon class="h-5 w-5 mr-2" />
        Nouvel utilisateur
      </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Recherche -->
        <div class="flex-1 relative">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"
          />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher par nom, email, matricule..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <!-- Filtre par rôle -->
        <div class="flex items-center gap-2">
          <FunnelIcon class="h-5 w-5 text-gray-400" />
          <select
            v-model="selectedRole"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
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
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
    >
      {{ error }}
      <button @click="error = null" class="float-right font-bold">
        &times;
      </button>
    </div>

    <!-- Liste des utilisateurs -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div
          class="animate-spin h-8 w-8 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto"
        ></div>
        <p class="mt-2 text-gray-500">Chargement...</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Utilisateur
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Rôle
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Service
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Statut
            </th>
            <th
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="user in filteredUsers"
            :key="user.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div
                  class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center"
                >
                  <span class="text-indigo-600 font-medium text-sm">
                    {{ getInitials(user.name) }}
                  </span>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">
                    {{ user.name }}
                  </div>
                  <div class="text-sm text-gray-500">{{ user.email }}</div>
                  <div v-if="user.matricule" class="text-xs text-gray-400">
                    {{ user.matricule }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getRoleBadge(user.role)"
                class="px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ getRoleLabel(user.role) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ user.service || "-" }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <button
                @click="toggleUserStatus(user)"
                :class="
                  user.is_active
                    ? 'text-green-600 hover:text-green-800'
                    : 'text-red-600 hover:text-red-800'
                "
                class="inline-flex items-center text-sm"
              >
                <CheckCircleIcon v-if="user.is_active" class="h-5 w-5 mr-1" />
                <XCircleIcon v-else class="h-5 w-5 mr-1" />
                {{ user.is_active ? "Actif" : "Inactif" }}
              </button>
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
            >
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openEditModal(user)"
                  class="text-indigo-600 hover:text-indigo-900"
                  title="Modifier"
                >
                  <PencilSquareIcon class="h-5 w-5" />
                </button>
                <button
                  @click="openResetPasswordModal(user)"
                  class="text-yellow-600 hover:text-yellow-900"
                  title="Réinitialiser mot de passe"
                >
                  <KeyIcon class="h-5 w-5" />
                </button>
                <button
                  @click="openDeleteModal(user)"
                  class="text-red-600 hover:text-red-900"
                  title="Supprimer"
                >
                  <TrashIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredUsers.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
              Aucun utilisateur trouvé
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.lastPage > 1"
        class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200"
      >
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="fetchUsers(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Précédent
          </button>
          <button
            @click="fetchUsers(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Suivant
          </button>
        </div>
        <div
          class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
        >
          <div>
            <p class="text-sm text-gray-700">
              Page
              <span class="font-medium">{{ pagination.currentPage }}</span>
              sur
              <span class="font-medium">{{ pagination.lastPage }}</span>
            </p>
          </div>
          <div class="flex gap-2">
            <button
              v-for="page in pagination.lastPage"
              :key="page"
              @click="fetchUsers(page)"
              :class="
                page === pagination.currentPage
                  ? 'bg-indigo-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              "
              class="px-3 py-1 border border-gray-300 text-sm font-medium rounded-md"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Créer utilisateur -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto"
      >
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Nouvel utilisateur
            </h3>
            <button
              @click="showCreateModal = false"
              class="text-gray-400 hover:text-gray-500"
            >
              &times;
            </button>
          </div>

          <form @submit.prevent="createUser" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Nom complet *</label
              >
              <input
                v-model="createForm.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Email *</label
              >
              <input
                v-model="createForm.email"
                type="email"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Mot de passe *</label
              >
              <input
                v-model="createForm.password"
                type="password"
                required
                minlength="8"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Rôle *</label
              >
              <select
                v-model="createForm.role"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
              <label class="block text-sm font-medium text-gray-700"
                >Service</label
              >
              <input
                v-model="createForm.service"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Téléphone</label
              >
              <input
                v-model="createForm.telephone"
                type="tel"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Matricule</label
              >
              <input
                v-model="createForm.matricule"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="actionLoading"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
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
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto"
      >
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Modifier {{ selectedUser.name }}
            </h3>
            <button
              @click="showEditModal = false"
              class="text-gray-400 hover:text-gray-500"
            >
              &times;
            </button>
          </div>

          <form @submit.prevent="updateUser" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Nom complet *</label
              >
              <input
                v-model="editForm.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Email *</label
              >
              <input
                v-model="editForm.email"
                type="email"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Rôle *</label
              >
              <select
                v-model="editForm.role"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
              <label class="block text-sm font-medium text-gray-700"
                >Service</label
              >
              <input
                v-model="editForm.service"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Téléphone</label
              >
              <input
                v-model="editForm.telephone"
                type="tel"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Matricule</label
              >
              <input
                v-model="editForm.matricule"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div class="flex items-center">
              <input
                v-model="editForm.is_active"
                type="checkbox"
                id="is_active"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="is_active" class="ml-2 block text-sm text-gray-900">
                Compte actif
              </label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="showEditModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="actionLoading"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
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
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-center mb-4">
            <div
              class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center"
            >
              <TrashIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
          <h3 class="text-lg font-medium text-gray-900 text-center mb-2">
            Supprimer l'utilisateur
          </h3>
          <p class="text-gray-500 text-center mb-6">
            Êtes-vous sûr de vouloir supprimer
            <strong>{{ selectedUser.name }}</strong> ? Cette action est
            irréversible.
          </p>

          <div class="flex justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              Annuler
            </button>
            <button
              @click="deleteUser"
              :disabled="actionLoading"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
            >
              {{ actionLoading ? "Suppression..." : "Supprimer" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Réinitialiser mot de passe -->
    <div
      v-if="showResetPasswordModal && selectedUser"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Réinitialiser le mot de passe
            </h3>
            <button
              @click="showResetPasswordModal = false"
              class="text-gray-400 hover:text-gray-500"
            >
              &times;
            </button>
          </div>
          <p class="text-gray-500 mb-4">
            Définir un nouveau mot de passe pour
            <strong>{{ selectedUser.name }}</strong>
          </p>

          <form @submit.prevent="resetPassword" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Nouveau mot de passe *</label
              >
              <input
                v-model="resetPasswordForm.password"
                type="password"
                required
                minlength="8"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Confirmer le mot de passe *</label
              >
              <input
                v-model="resetPasswordForm.password_confirmation"
                type="password"
                required
                minlength="8"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="showResetPasswordModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="actionLoading"
                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 disabled:opacity-50"
              >
                {{ actionLoading ? "Réinitialisation..." : "Réinitialiser" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
