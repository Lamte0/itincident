<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useIncidentStore } from "@/stores/incidents";
import { useAuthStore } from "@/stores/auth";
import { userService } from "@/services/api";
import type { User, Incident } from "@/types";
import { RouterLink } from "vue-router";
import {
  FunnelIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  PlusIcon,
  PaperAirplaneIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const incidentStore = useIncidentStore();
const authStore = useAuthStore();
const searchQuery = ref("");
const showFilters = ref(false);

// Modal d'affectation
const showAffectationModal = ref(false);
const selectedIncident = ref<Incident | null>(null);
const maintenanciers = ref<User[]>([]);
const affectationForm = ref({
  maintenancier_id: 0,
  instructions: "",
});
const affectationLoading = ref(false);
const affectationError = ref<string | null>(null);
const affectationSuccess = ref<string | null>(null);

const typeOptions = [
  { value: "", label: "Tous les types" },
  { value: "RESEAU", label: "Réseau" },
  { value: "LOGICIEL", label: "Logiciel" },
  { value: "HARDWARE", label: "Hardware" },
];

const prioriteOptions = [
  { value: "", label: "Toutes les priorités" },
  { value: "BASSE", label: "Basse" },
  { value: "MOYENNE", label: "Moyenne" },
  { value: "HAUTE", label: "Haute" },
  { value: "CRITIQUE", label: "Critique" },
];

const statutOptions = [
  { value: "", label: "Tous les statuts" },
  { value: "OUVERT", label: "Ouvert" },
  { value: "AFFECTE", label: "Affecté" },
  { value: "EN_COURS", label: "En cours" },
  { value: "RESOLU", label: "Résolu" },
  { value: "EN_ATTENTE_VALIDATION", label: "En attente validation" },
  { value: "CLOTURE", label: "Clôturé" },
];

function getStatutBadge(statut: string) {
  const badges: Record<string, string> = {
    OUVERT: "bg-red-100 text-red-800",
    AFFECTE: "bg-blue-100 text-blue-800",
    EN_COURS: "bg-yellow-100 text-yellow-800",
    RESOLU: "bg-green-100 text-green-800",
    EN_ATTENTE_VALIDATION: "bg-purple-100 text-purple-800",
    CLOTURE: "bg-gray-100 text-gray-800",
  };
  return badges[statut] || "bg-gray-100 text-gray-800";
}

function getPrioriteBadge(priorite: string) {
  const badges: Record<string, string> = {
    BASSE: "bg-green-100 text-green-800",
    MOYENNE: "bg-yellow-100 text-yellow-800",
    HAUTE: "bg-orange-100 text-orange-800",
    CRITIQUE: "bg-red-100 text-red-800",
  };
  return badges[priorite] || "bg-gray-100 text-gray-800";
}

function applyFilters() {
  incidentStore.fetchIncidents(1);
}

function clearFilters() {
  incidentStore.clearFilters();
  incidentStore.fetchIncidents(1);
}

function changePage(page: number) {
  incidentStore.fetchIncidents(page);
}

// Fonctions d'affectation
async function loadMaintenanciers() {
  try {
    const response = await userService.getMaintenanciers();
    maintenanciers.value = response.data;
  } catch (err) {
    console.error("Erreur lors du chargement des maintenanciers", err);
  }
}

function openAffectationModal(incident: Incident) {
  selectedIncident.value = incident;
  affectationForm.value = { maintenancier_id: 0, instructions: "" };
  affectationError.value = null;
  showAffectationModal.value = true;
}

function closeAffectationModal() {
  showAffectationModal.value = false;
  selectedIncident.value = null;
  affectationForm.value = { maintenancier_id: 0, instructions: "" };
}

async function affecterIncident() {
  if (!selectedIncident.value || !affectationForm.value.maintenancier_id) {
    affectationError.value = "Veuillez sélectionner un technicien";
    return;
  }

  affectationLoading.value = true;
  affectationError.value = null;

  try {
    await incidentStore.affecterIncident(
      selectedIncident.value.id,
      affectationForm.value.maintenancier_id,
      affectationForm.value.instructions
    );

    // Trouver le nom du maintenancier sélectionné
    const maintenancier = maintenanciers.value.find(
      (m) => m.id === affectationForm.value.maintenancier_id
    );

    closeAffectationModal();

    // Afficher le message de succès
    affectationSuccess.value = `Incident affecté avec succès à ${
      maintenancier?.name || "maintenancier"
    }`;
    setTimeout(() => {
      affectationSuccess.value = null;
    }, 5000);

    // Recharger la liste des incidents
    await incidentStore.fetchIncidents(incidentStore.pagination.currentPage);
  } catch (err: any) {
    affectationError.value =
      err.response?.data?.message || "Erreur lors de l'affectation";
  } finally {
    affectationLoading.value = false;
  }
}

// Vérifier si l'utilisateur peut affecter
function canAffecter(incident: Incident): boolean {
  return (
    authStore.hasRole(["SUPERVISEUR", "ADMIN"]) && incident.statut === "OUVERT"
  );
}

onMounted(() => {
  incidentStore.fetchIncidents();
  // Charger les maintenanciers si l'utilisateur peut affecter
  if (authStore.hasRole(["SUPERVISEUR", "ADMIN"])) {
    loadMaintenanciers();
  }
});
</script>

<template>
  <div>
    <!-- Message de succès d'affectation -->
    <div v-if="affectationSuccess" class="mb-4 rounded-md bg-green-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-green-400"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">
            {{ affectationSuccess }}
          </p>
        </div>
        <div class="ml-auto pl-3">
          <button
            @click="affectationSuccess = null"
            class="inline-flex text-green-500 hover:text-green-600"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Tous les Incidents</h1>
        <p class="mt-1 text-sm text-gray-500">
          Liste complète des incidents déclarés
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <RouterLink
          to="/incidents/nouveau"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-blue bg-primary-600 hover:bg-primary-700"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Nouvel incident
        </RouterLink>
      </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white shadow rounded-lg mb-6 p-4">
      <div class="flex items-center justify-between">
        <div class="flex-1 max-w-lg">
          <div class="relative">
            <MagnifyingGlassIcon
              class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par référence, titre..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            />
          </div>
        </div>
        <button
          @click="showFilters = !showFilters"
          class="ml-4 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          <FunnelIcon class="h-5 w-5 mr-2" />
          Filtres
        </button>
      </div>

      <div
        v-if="showFilters"
        class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4"
      >
        <div>
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select
            v-model="incidentStore.filters.type"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
          >
            <option
              v-for="opt in typeOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700"
            >Priorité</label
          >
          <select
            v-model="incidentStore.filters.priorite"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
          >
            <option
              v-for="opt in prioriteOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Statut</label>
          <select
            v-model="incidentStore.filters.statut"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
          >
            <option
              v-for="opt in statutOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div class="flex items-end space-x-2">
          <button
            @click="applyFilters"
            class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700"
          >
            Appliquer
          </button>
          <button
            @click="clearFilters"
            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300"
          >
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="incidentStore.loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Liste des incidents -->
    <div v-else class="bg-white shadow overflow-hidden rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Référence
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Titre
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Type
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Priorité
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Statut
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Auteur
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Date
            </th>
            <th
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="incidentStore.incidents.length === 0">
            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
              Aucun incident trouvé
            </td>
          </tr>
          <tr
            v-for="incident in incidentStore.incidents"
            :key="incident.id"
            class="hover:bg-gray-50"
          >
            <td
              class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-600"
            >
              {{ incident.reference }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ incident.titre }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ incident.type }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  getPrioriteBadge(incident.priorite),
                ]"
              >
                {{ incident.priorite }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  getStatutBadge(incident.statut),
                ]"
              >
                {{ incident.statut.replace("_", " ") }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ incident.auteur?.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(incident.created_at).toLocaleDateString("fr-FR") }}
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
            >
              <div class="flex items-center justify-end space-x-2">
                <RouterLink
                  :to="`/incidents/${incident.id}`"
                  class="text-primary-600 hover:text-primary-900"
                  title="Voir détails"
                >
                  <EyeIcon class="h-5 w-5" />
                </RouterLink>
                <button
                  v-if="canAffecter(incident)"
                  @click="openAffectationModal(incident)"
                  class="text-green-600 hover:text-green-900"
                  title="Affecter à un maintenancier"
                >
                  <PaperAirplaneIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="incidentStore.pagination.lastPage > 1"
        class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Page {{ incidentStore.pagination.currentPage }} sur
            {{ incidentStore.pagination.lastPage }} ({{
              incidentStore.pagination.total
            }}
            résultats)
          </div>
          <div class="flex space-x-2">
            <button
              v-for="page in incidentStore.pagination.lastPage"
              :key="page"
              @click="changePage(page)"
              :class="[
                'px-3 py-1 text-sm rounded',
                page === incidentStore.pagination.currentPage
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
              ]"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'affectation -->
    <div
      v-if="showAffectationModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Affecter l'incident
            </h3>
            <button
              @click="closeAffectationModal"
              class="text-gray-400 hover:text-gray-500"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <div v-if="selectedIncident" class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-900">
              {{ selectedIncident.reference }}
            </p>
            <p class="text-sm text-gray-600">{{ selectedIncident.titre }}</p>
            <div class="mt-1 flex items-center space-x-2">
              <span
                :class="[
                  'px-2 py-0.5 text-xs font-medium rounded-full',
                  getPrioriteBadge(selectedIncident.priorite),
                ]"
              >
                {{ selectedIncident.priorite }}
              </span>
              <span class="text-xs text-gray-500">
                {{ selectedIncident.type }}
              </span>
            </div>
          </div>

          <!-- Erreur -->
          <div
            v-if="affectationError"
            class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"
          >
            {{ affectationError }}
          </div>

          <form @submit.prevent="affecterIncident" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Maintenancier *
              </label>
              <select
                v-model="affectationForm.maintenancier_id"
                required
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option :value="0" disabled>
                  -- Sélectionner un technicien --
                </option>
                <option v-for="m in maintenanciers" :key="m.id" :value="m.id">
                  {{ m.name }}
                  <span v-if="m.service"> - {{ m.service }}</span>
                </option>
              </select>
              <p
                v-if="maintenanciers.length === 0"
                class="mt-1 text-sm text-yellow-600"
              >
                Aucun maintenancier disponible
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Instructions (optionnel)
              </label>
              <textarea
                v-model="affectationForm.instructions"
                rows="3"
                placeholder="Instructions spéciales pour le maintenancier..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              ></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeAffectationModal"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="
                  affectationLoading || !affectationForm.maintenancier_id
                "
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="affectationLoading">Affectation...</span>
                <span v-else>Affecter</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
