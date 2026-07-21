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

async function affecterIncident() {
  if (!selectedIncident.value || !affectationForm.value.maintenancier_id) {
    affectationError.value = "Veuillez sélectionner un maintenancier";
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
    authStore.hasRole(["CHEF_SERVICE", "ADMIN"]) && incident.statut === "OUVERT"
  );
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

onMounted(() => {
  incidentStore.fetchIncidents();
  // Charger les maintenanciers si l'utilisateur peut affecter
  if (authStore.hasRole(["CHEF_SERVICE", "ADMIN"])) {
    loadMaintenanciers();
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Message de succès d'affectation -->
    <div v-if="affectationSuccess" class="rounded-2xl bg-green-50 border border-green-100 p-4 shadow-sm animate-fade-in-up">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 text-green-500">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <p class="text-sm font-semibold text-green-800 flex-grow">
          {{ affectationSuccess }}
        </p>
        <button
          @click="affectationSuccess = null"
          class="text-green-500 hover:text-green-700 transition-colors"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>
    </div>

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tous les Incidents</h1>
        <p class="text-sm text-slate-500">
          Consultez et gérez la liste complète des déclarations d'incidents informatiques.
        </p>
      </div>
      <div class="flex-shrink-0">
        <RouterLink
          to="/incidents/nouveau"
          class="inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-semibold rounded-2xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 hover:from-indigo-700 hover:to-violet-700 transition-all duration-200 text-sm hover:-translate-y-0.5"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Déclarer un incident
        </RouterLink>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-4">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
        <div class="flex-grow relative">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher par référence, titre..."
            class="block w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all text-sm"
          />
        </div>
        <button
          @click="showFilters = !showFilters"
          class="inline-flex items-center justify-center px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all gap-2"
        >
          <FunnelIcon class="h-4 w-4" />
          Filtres
        </button>
      </div>

      <div
        v-if="showFilters"
        class="pt-4 border-t border-slate-100 grid grid-cols-1 gap-4 sm:grid-cols-4"
      >
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Type</label>
          <select
            v-model="incidentStore.filters.type"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all"
          >
            <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Priorité</label>
          <select
            v-model="incidentStore.filters.priorite"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all"
          >
            <option v-for="opt in prioriteOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Statut</label>
          <select
            v-model="incidentStore.filters.statut"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all"
          >
            <option v-for="opt in statutOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button
            @click="applyFilters"
            class="flex-grow px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors"
          >
            Appliquer
          </button>
          <button
            @click="clearFilters"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-colors"
          >
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="incidentStore.loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Data Table Container -->
    <div v-else class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Référence</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Titre</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Priorité</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Statut</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Auteur</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-if="incidentStore.incidents.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-sm">
                Aucun incident trouvé
              </td>
            </tr>
            <tr
              v-for="incident in incidentStore.incidents"
              :key="incident.id"
              class="hover:bg-slate-50/40 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-indigo-600">
                {{ incident.reference }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 font-medium">
                {{ incident.titre }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">
                {{ incident.type }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg', getPrioriteBadge(incident.priorite)]">
                  {{ incident.priorite }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg', getStatutBadge(incident.statut)]">
                  {{ incident.statut.replace("_", " ") }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">
                {{ incident.auteur?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-medium">
                {{ new Date(incident.created_at).toLocaleDateString("fr-FR") }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <div class="flex items-center justify-end gap-2.5">
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 rounded-lg p-1.5 transition-colors"
                    title="Voir détails"
                  >
                    <EyeIcon class="h-4.5 w-4.5" />
                  </RouterLink>
                  <button
                    v-if="canAffecter(incident)"
                    @click="openAffectationModal(incident)"
                    class="text-emerald-600 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 rounded-lg p-1.5 transition-colors"
                    title="Affecter"
                  >
                    <PaperAirplaneIcon class="h-4.5 w-4.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="incidentStore.pagination.lastPage > 1"
        class="bg-slate-50/50 px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-4 flex-wrap"
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
              'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
              page === incidentStore.pagination.currentPage
                ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/25'
                : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal d'affectation -->
    <div
      v-if="showAffectationModal"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fade-in"
    >
      <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-fade-in-up">
        <div class="p-6 space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">
              Affecter l'incident
            </h3>
            <button
              @click="closeAffectationModal"
              class="text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <div v-if="selectedIncident" class="p-4 bg-slate-50 border border-slate-150 rounded-2xl space-y-2">
            <p class="text-xs font-bold text-indigo-600">{{ selectedIncident.reference }}</p>
            <p class="text-sm font-semibold text-slate-800">{{ selectedIncident.titre }}</p>
            <div class="flex items-center gap-2">
              <span :class="['px-2.5 py-0.5 text-[10px] font-bold rounded-md', getPrioriteBadge(selectedIncident.priorite)]">
                {{ selectedIncident.priorite }}
              </span>
              <span class="text-[10px] font-bold text-slate-400 uppercase">{{ selectedIncident.type }}</span>
            </div>
          </div>

          <!-- Erreur -->
          <div
            v-if="affectationError"
            class="p-3 bg-red-50 border border-red-100 text-red-600 rounded-xl text-xs font-semibold"
          >
            {{ affectationError }}
          </div>

          <form @submit.prevent="affecterIncident" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                Maintenancier *
              </label>
              <select
                v-model="affectationForm.maintenancier_id"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              >
                <option :value="0" disabled>
                  -- Sélectionner un maintenancier --
                </option>
                <option v-for="m in maintenanciers" :key="m.id" :value="m.id">
                  {{ m.name }} <span v-if="m.service">({{ m.service }})</span>
                </option>
              </select>
              <p
                v-if="maintenanciers.length === 0"
                class="mt-1 text-xs text-amber-600 font-semibold"
              >
                Aucun maintenancier disponible
              </p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                Instructions (optionnel)
              </label>
              <textarea
                v-model="affectationForm.instructions"
                rows="3"
                placeholder="Instructions spéciales pour le technicien..."
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <button
                type="button"
                @click="closeAffectationModal"
                class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="affectationLoading || !affectationForm.maintenancier_id"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-indigo-600/10"
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
