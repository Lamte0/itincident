<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
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
    OUVERT: "bg-red-50 text-red-700 border border-red-100",
    AFFECTE: "bg-blue-50 text-blue-700 border border-blue-100",
    EN_COURS: "bg-amber-50 text-amber-700 border border-amber-100",
    RESOLU: "bg-emerald-50 text-emerald-700 border border-emerald-100",
    EN_ATTENTE_VALIDATION: "bg-purple-50 text-purple-700 border border-purple-100",
    CLOTURE: "bg-slate-100 text-slate-700 border border-slate-200",
  };
  return badges[statut] || "bg-slate-100 text-slate-700 border border-slate-200";
}

function getPrioriteBadge(priorite: string) {
  const badges: Record<string, string> = {
    BASSE: "bg-green-50 text-green-700 border border-green-100",
    MOYENNE: "bg-yellow-50 text-yellow-700 border border-yellow-100",
    HAUTE: "bg-orange-50 text-orange-700 border border-orange-100",
    CRITIQUE: "bg-red-50 text-red-700 border border-red-100",
  };
  return badges[priorite] || "bg-slate-100 text-slate-700 border border-slate-200";
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

let pollingInterval: ReturnType<typeof setInterval> | null = null;

async function refreshData(isBackground = false) {
  await loadMaintenanciers();
  if (!isBackground) {
    incidentStore.clearFilters();
  }
  await incidentStore.fetchIncidents(1, 500, isBackground);
}

onMounted(() => {
  refreshData(false);
  pollingInterval = setInterval(() => refreshData(true), 10000);
});

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});
</script>

<template>
  <div class="space-y-6">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
        Affectation des incidents
      </h1>
      <p class="text-sm text-slate-500">
        Gérer l'affectation des incidents déclarés aux techniciens de maintenance.
      </p>
    </div>

    <!-- Filtres -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm space-y-4">
      <div class="flex items-center justify-between gap-4">
        <div class="flex-1 max-w-lg">
          <div class="relative">
            <MagnifyingGlassIcon
              class="absolute left-3.5 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par référence, titre..."
              class="block w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
            />
          </div>
        </div>
        <button
          @click="showFilters = !showFilters"
          class="inline-flex items-center justify-center px-4 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 font-semibold rounded-xl text-sm text-slate-700 transition-colors shadow-sm gap-2"
        >
          <FunnelIcon class="h-4.5 w-4.5" />
          Filtres
        </button>
      </div>

      <div
        v-if="showFilters"
        class="pt-4 border-t border-slate-100 grid grid-cols-1 gap-4 sm:grid-cols-3 animate-fade-in-up"
      >
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Type</label>
          <select
            v-model="incidentStore.filters.type"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500"
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
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Priorité</label>
          <select
            v-model="incidentStore.filters.priorite"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500"
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
        <div class="flex items-end gap-2 pb-0.5">
          <button
            @click="applyFilters"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors flex-1"
          >
            Appliquer
          </button>
          <button
            @click="clearFilters"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-colors flex-1"
          >
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Erreur -->
    <div
      v-if="incidentStore.error"
      class="bg-red-50 border border-red-100 text-red-650 p-4 rounded-2xl text-sm font-semibold animate-fade-in-up"
    >
      {{ incidentStore.error }}
    </div>

    <!-- Loading -->
    <div v-if="incidentStore.incidentsLoading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
    </div>

    <template v-else>
      <!-- Incidents à affecter -->
      <div class="space-y-4">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
          <span class="inline-flex items-center gap-2">
            Incidents à affecter
            <span
              class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-red-700 bg-red-50 border border-red-100 rounded-md"
            >
              {{ incidentsAAffecter.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="incidentsAAffecter.length === 0"
          class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-slate-500 text-sm font-medium shadow-sm"
        >
          Aucun incident en attente d'affectation.
        </div>

        <div v-else class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50/75">
                <tr>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Référence
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Titre
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Type
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Priorité
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Auteur
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Date
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
                  v-for="incident in incidentsAAffecter"
                  :key="incident.id"
                  class="hover:bg-slate-50/50 transition-colors"
                >
                  <td
                    class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-650"
                  >
                    {{ incident.reference }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                    {{ incident.titre }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                    {{ incident.type }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getPrioriteBadge(incident.priorite)]">
                      {{ incident.priorite }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                    {{ incident.auteur?.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                    {{ formatDate(incident.created_at) }}
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                  >
                    <RouterLink
                      :to="`/incidents/${incident.id}`"
                      class="inline-flex text-slate-400 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
                    >
                      <EyeIcon class="h-4.5 w-4.5" />
                    </RouterLink>
                    <button
                      @click="openAffectationModal(incident)"
                      class="inline-flex text-slate-400 hover:text-emerald-600 bg-slate-50 hover:bg-emerald-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
                      title="Affecter"
                    >
                      <PaperAirplaneIcon class="h-4.5 w-4.5" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Incidents déjà affectés -->
      <div class="space-y-4 pt-4">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
          <span class="inline-flex items-center gap-2">
            Incidents en cours de traitement
            <span
              class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-blue-700 bg-blue-50 border border-blue-100 rounded-md"
            >
              {{ incidentsAffectes.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="incidentsAffectes.length === 0"
          class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-slate-500 text-sm font-medium shadow-sm"
        >
          Aucun incident en cours de traitement.
        </div>

        <div v-else class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50/75">
                <tr>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Référence
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Titre
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Statut
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Priorité
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Maintenancier
                  </th>
                  <th
                    class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                  >
                    Date affectation
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
                  v-for="incident in incidentsAffectes"
                  :key="incident.id"
                  class="hover:bg-slate-50/50 transition-colors"
                >
                  <td
                    class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-650"
                  >
                    {{ incident.reference }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                    {{ incident.titre }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getStatutBadge(incident.statut)]">
                      {{ incident.statut.replace(/_/g, " ") }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getPrioriteBadge(incident.priorite)]">
                      {{ incident.priorite }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                    {{ incident.affectation_active?.maintenancier?.name || "-" }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
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
                      class="inline-flex text-slate-400 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
                    >
                      <EyeIcon class="h-4.5 w-4.5" />
                    </RouterLink>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="incidentStore.pagination.lastPage > 1"
        class="mt-6 bg-white border border-slate-200/80 px-4 py-3 rounded-2xl sm:px-6 shadow-sm flex items-center justify-between"
      >
        <div class="text-xs font-semibold text-slate-500">
          Page {{ incidentStore.pagination.currentPage }} sur
          {{ incidentStore.pagination.lastPage }} ({{
            incidentStore.pagination.total
          }}
          résultats)
        </div>
        <div class="flex gap-1.5">
          <button
            v-for="page in incidentStore.pagination.lastPage"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1.5 text-xs font-bold rounded-lg transition-all',
              page === incidentStore.pagination.currentPage
                ? 'bg-indigo-600 text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </template>

    <!-- Modal Affectation -->
    <div
      v-if="showAffectationModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full p-6 animate-fade-in-up space-y-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-800">
            Affecter l'incident
          </h3>
          <button
            @click="showAffectationModal = false"
            class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div v-if="selectedIncident" class="p-4 bg-slate-50/80 border border-slate-200/50 rounded-2xl space-y-1">
          <p class="text-xs font-bold text-indigo-650 tracking-wider">
            {{ selectedIncident.reference }}
          </p>
          <p class="text-sm font-semibold text-slate-700">{{ selectedIncident.titre }}</p>
        </div>

        <form @submit.prevent="affecter" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex justify-between">
              <span>Maintenancier *</span>
            </label>
            <select
              v-model="affectationForm.maintenancier_id"
              required
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
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
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Instructions
            </label>
            <textarea
              v-model="affectationForm.instructions"
              rows="3"
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              placeholder="Instructions pour le maintenancier..."
            ></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showAffectationModal = false"
              class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="
                !affectationForm.maintenancier_id || incidentStore.loading
              "
              class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-indigo-600/10"
            >
              <span v-if="incidentStore.loading">Affectation...</span>
              <span v-else>Affecter</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
