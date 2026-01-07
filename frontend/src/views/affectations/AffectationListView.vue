<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useIncidentStore } from "@/stores/incidents";
import { userService } from "@/services/api";
import type { User, Incident } from "@/types";
import { RouterLink } from "vue-router";
import {
  FunnelIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  PaperAirplaneIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const incidentStore = useIncidentStore();

const maintenanciers = ref<User[]>([]);
const showAffectationModal = ref(false);
const selectedIncident = ref<Incident | null>(null);
const affectationForm = ref({
  maintenancier_id: 0,
  instructions: "",
});
const showFilters = ref(false);
const searchQuery = ref("");

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

// Filtre pour afficher seulement les incidents OUVERT (à affecter)
const incidentsAAffecter = computed(() => {
  return incidentStore.incidents.filter(
    (incident) => incident.statut === "OUVERT"
  );
});

const incidentsAffectes = computed(() => {
  return incidentStore.incidents.filter(
    (incident) =>
      incident.statut === "AFFECTE" ||
      incident.statut === "EN_COURS" ||
      incident.statut === "RESOLU" ||
      incident.statut === "EN_ATTENTE_VALIDATION"
  );
});

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

function formatDate(date: string) {
  return new Date(date).toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

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
  showAffectationModal.value = true;
}

async function affecter() {
  if (!selectedIncident.value || !affectationForm.value.maintenancier_id)
    return;
  try {
    await incidentStore.affecterIncident(
      selectedIncident.value.id,
      affectationForm.value.maintenancier_id,
      affectationForm.value.instructions
    );
    showAffectationModal.value = false;
    selectedIncident.value = null;
    // Recharger la liste
    await incidentStore.fetchIncidents();
  } catch (err) {
    console.error("Erreur lors de l'affectation", err);
  }
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

onMounted(async () => {
  await loadMaintenanciers();
  // Charger tous les incidents (avec une limite plus élevée pour voir toutes les affectations)
  await incidentStore.fetchIncidents(1, 100);
});
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        Affectation des incidents
      </h1>
      <p class="mt-1 text-sm text-gray-500">
        Gérer l'affectation des incidents aux maintenanciers
      </p>
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
        class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3"
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

    <!-- Erreur -->
    <div
      v-if="incidentStore.error"
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6"
    >
      {{ incidentStore.error }}
    </div>

    <!-- Loading -->
    <div v-if="incidentStore.loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      ></div>
    </div>

    <template v-else>
      <!-- Incidents à affecter -->
      <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          <span class="inline-flex items-center">
            Incidents à affecter
            <span
              class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"
            >
              {{ incidentsAAffecter.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="incidentsAAffecter.length === 0"
          class="bg-white shadow rounded-lg p-6 text-center text-gray-500"
        >
          Aucun incident en attente d'affectation
        </div>

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
              <tr
                v-for="incident in incidentsAAffecter"
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ incident.auteur?.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(incident.created_at) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                >
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="text-primary-600 hover:text-primary-900"
                  >
                    <EyeIcon class="h-5 w-5 inline" />
                  </RouterLink>
                  <button
                    @click="openAffectationModal(incident)"
                    class="text-green-600 hover:text-green-900 ml-2"
                    title="Affecter"
                  >
                    <PaperAirplaneIcon class="h-5 w-5 inline" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Incidents déjà affectés -->
      <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          <span class="inline-flex items-center">
            Incidents en cours de traitement
            <span
              class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-100 bg-blue-600 rounded-full"
            >
              {{ incidentsAffectes.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="incidentsAffectes.length === 0"
          class="bg-white shadow rounded-lg p-6 text-center text-gray-500"
        >
          Aucun incident en cours de traitement
        </div>

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
                  Statut
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Priorité
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Maintenancier
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Date affectation
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
                v-for="incident in incidentsAffectes"
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
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                      getStatutBadge(incident.statut),
                    ]"
                  >
                    {{ incident.statut.replace(/_/g, " ") }}
                  </span>
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ incident.affectation_active?.maintenancier?.name || "-" }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{
                    incident.affectation_active?.date_affectation
                      ? formatDate(incident.affectation_active.date_affectation)
                      : "-"
                  }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                >
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="text-primary-600 hover:text-primary-900"
                  >
                    <EyeIcon class="h-5 w-5 inline" />
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="incidentStore.pagination.lastPage > 1"
        class="mt-6 bg-white px-4 py-3 border border-gray-200 rounded-lg sm:px-6"
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
    </template>

    <!-- Modal Affectation -->
    <div v-if="showAffectationModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75"
          @click="showAffectationModal = false"
        ></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Affecter l'incident
            </h3>
            <button
              @click="showAffectationModal = false"
              class="text-gray-400 hover:text-gray-500"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <div v-if="selectedIncident" class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-900">
              {{ selectedIncident.reference }}
            </p>
            <p class="text-sm text-gray-500">{{ selectedIncident.titre }}</p>
          </div>

          <form @submit.prevent="affecter" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Maintenancier *
              </label>
              <select
                v-model="affectationForm.maintenancier_id"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="0" disabled>
                  Sélectionner un maintenancier
                </option>
                <option v-for="m in maintenanciers" :key="m.id" :value="m.id">
                  {{ m.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Instructions
              </label>
              <textarea
                v-model="affectationForm.instructions"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Instructions pour le maintenancier..."
              ></textarea>
            </div>
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="showAffectationModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="
                  !affectationForm.maintenancier_id || incidentStore.loading
                "
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
              >
                <span v-if="incidentStore.loading">Affectation...</span>
                <span v-else>Affecter</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
