<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useIncidentStore } from "@/stores/incidents";
import { reportService, userService, incidentService } from "@/services/api";
import type { Statistiques, User, Incident } from "@/types";
import { RouterLink } from "vue-router";
import {
  ExclamationTriangleIcon,
  ClockIcon,
  CheckCircleIcon,
  WrenchScrewdriverIcon,
  UsersIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  ArrowRightIcon,
  EyeIcon,
  PaperAirplaneIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const authStore = useAuthStore();
const incidentStore = useIncidentStore();

const statistiques = ref<Statistiques | null>(null);
const unassignedIncidents = ref<Incident[]>([]);
const maintenanciers = ref<User[]>([]);
const loadingStats = ref(false);
let pollingInterval: ReturnType<typeof setInterval> | null = null;

// Modal affectation sur le Dashboard
const showAffectationModal = ref(false);
const selectedIncident = ref<Incident | null>(null);
const affectationForm = ref({
  maintenancier_id: 0,
  instructions: "",
});
const affectationLoading = ref(false);
const affectationError = ref<string | null>(null);

const statsList = computed(() => [
  {
    name: "Incidents ouverts",
    value: statistiques.value?.incidents_par_statut?.OUVERT ?? 0,
    icon: ExclamationTriangleIcon,
    bgColor: "bg-red-50",
    textColor: "text-red-600",
  },
  {
    name: "En cours de traitement",
    value:
      (statistiques.value?.incidents_par_statut?.AFFECTE ?? 0) +
      (statistiques.value?.incidents_par_statut?.EN_COURS ?? 0),
    icon: ClockIcon,
    bgColor: "bg-amber-50",
    textColor: "text-amber-600",
  },
  {
    name: "Résolus ce mois",
    value:
      (statistiques.value?.incidents_par_statut?.RESOLU ?? 0) +
      (statistiques.value?.incidents_par_statut?.CLOTURE ?? 0),
    icon: CheckCircleIcon,
    bgColor: "bg-emerald-50",
    textColor: "text-emerald-600",
  },
  {
    name: "Total incidents",
    value: statistiques.value?.total_incidents ?? 0,
    icon: WrenchScrewdriverIcon,
    bgColor: "bg-blue-50",
    textColor: "text-blue-600",
  },
]);

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
  if (authStore.isAdmin || authStore.isChefService) {
    try {
      const res = await userService.getMaintenanciers();
      maintenanciers.value = res.data;
    } catch (err) {
      console.error("Erreur lors du chargement des maintenanciers", err);
    }
  }
}

function openAffectationModal(incident: Incident) {
  selectedIncident.value = incident;
  affectationForm.value = { maintenancier_id: 0, instructions: "" };
  affectationError.value = null;
  showAffectationModal.value = true;
}

async function affecterIncidentSubmit() {
  if (!selectedIncident.value || !affectationForm.value.maintenancier_id) return;
  affectationLoading.value = true;
  affectationError.value = null;
  try {
    await incidentStore.affecterIncident(
      selectedIncident.value.id,
      affectationForm.value.maintenancier_id,
      affectationForm.value.instructions
    );
    showAffectationModal.value = false;
    selectedIncident.value = null;
    await loadData();
  } catch (err: any) {
    affectationError.value = err.response?.data?.message || "Erreur lors de l'affectation";
  } finally {
    affectationLoading.value = false;
  }
}

async function loadData(isBackground = false) {
  if (!isBackground && !statistiques.value) {
    loadingStats.value = true;
  }
  try {
    const today = new Date();
    const oneMonthAgo = new Date(
      today.getFullYear(),
      today.getMonth() - 1,
      today.getDate()
    );
    const date_debut = oneMonthAgo.toISOString().split("T")[0] || "";
    const date_fin = today.toISOString().split("T")[0] || "";

    const statsRes = await reportService.getStatistiques({ date_debut, date_fin });
    statistiques.value = statsRes.data;

    // Pour l'Admin et le Chef Service, récupérer tous les incidents non affectés (OUVERT)
    if (authStore.isAdmin || authStore.isChefService) {
      const unassignedRes = await incidentService.getAll({ statut: "OUVERT", per_page: 50 });
      unassignedIncidents.value = unassignedRes.data.data || [];
      await incidentStore.fetchIncidents(1, 10, isBackground);
    } else if (authStore.isMaintenancier) {
      await incidentStore.fetchMyInterventions(isBackground);
    } else {
      await incidentStore.fetchMyIncidents(1, isBackground);
    }
  } catch (err) {
    console.error("Erreur lors du chargement des données du dashboard", err);
  } finally {
    loadingStats.value = false;
  }
}

onMounted(async () => {
  await loadMaintenanciers();
  await loadData(false);
  // Auto-refresh toutes les 10 secondes silencieusement
  pollingInterval = setInterval(() => loadData(true), 10000);
});

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});
</script>

<template>
  <div class="space-y-8">
    <!-- Welcome Banner Card -->
    <div class="relative bg-gradient-to-r from-indigo-900 via-indigo-800 to-violet-800 rounded-3xl p-8 md:p-10 text-white overflow-hidden shadow-xl shadow-indigo-900/10">
      <div class="absolute -top-24 -left-24 w-80 h-80 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
      
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-3">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-semibold tracking-wider uppercase text-indigo-200 border border-white/10">
            <span class="h-1.5 w-1.5 rounded-full bg-green-400 animate-pulse"></span>
            En temps réel (synchro active)
          </span>
          <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">
            Bonjour, {{ authStore.user?.name }}
          </h1>
          <p class="text-indigo-200 text-sm md:text-base max-w-2xl leading-relaxed">
            Bienvenue sur le système de gestion des incidents de la Direction Générale du Trésor et de la Comptabilité Publique.
          </p>
        </div>
        <div class="flex-shrink-0 flex items-center gap-4">
          <RouterLink
            to="/incidents/nouveau"
            class="px-5 py-3 bg-white text-indigo-700 hover:bg-indigo-50 font-semibold rounded-2xl shadow-lg transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 text-sm flex items-center gap-2"
          >
            <span>Signaler un incident</span>
            <ArrowRightIcon class="h-4 w-4" />
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-800 tracking-tight">Statistiques générales</h2>
        <span v-if="loadingStats" class="text-xs text-indigo-600 font-semibold animate-pulse">Actualisation...</span>
      </div>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="stat in statsList"
          :key="stat.name"
          class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-100 transition-all duration-300 hover:-translate-y-0.5 card-glow flex items-center gap-5"
        >
          <div :class="[stat.bgColor, 'flex-shrink-0 rounded-2xl p-4 flex items-center justify-center']">
            <component :is="stat.icon" :class="['h-7 w-7', stat.textColor]" />
          </div>
          <div class="min-w-0 flex-1 space-y-1">
            <p class="text-xs font-semibold text-slate-500 tracking-wide uppercase truncate">
              {{ stat.name }}
            </p>
            <p class="text-3xl font-extrabold text-slate-800">
              {{ stat.value }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section ADMIN / CHEF SERVICE : Incidents à affecter -->
    <div v-if="authStore.isAdmin || authStore.isChefService" class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h2 class="text-lg font-bold text-slate-800 tracking-tight">
            Incidents non affectés en attente
          </h2>
          <span class="inline-flex items-center justify-center px-2.5 py-0.5 text-xs font-bold leading-none text-red-700 bg-red-50 border border-red-100 rounded-full">
            {{ unassignedIncidents.length }}
          </span>
        </div>
        <RouterLink
          to="/affectations"
          class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1"
        >
          <span>Page d'affectation complète</span>
          <ArrowRightIcon class="h-3.5 w-3.5" />
        </RouterLink>
      </div>

      <div v-if="unassignedIncidents.length === 0" class="bg-white border border-slate-200/80 rounded-2xl p-6 text-center text-slate-500 text-sm font-medium shadow-sm">
        Aucun incident non affecté. Tous les incidents ont été pris en charge.
      </div>

      <div v-else class="bg-white border border-red-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-red-50/50">
              <tr>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-red-800 uppercase tracking-wider">Référence</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-red-800 uppercase tracking-wider">Titre</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-red-800 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-red-800 uppercase tracking-wider">Priorité</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-red-800 uppercase tracking-wider">Auteur</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-red-800 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3.5 text-right text-xs font-bold text-red-800 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
              <tr v-for="incident in unassignedIncidents" :key="incident.id" class="hover:bg-red-50/20 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-650">
                  {{ incident.reference }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800">
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
                  {{ incident.auteur?.name || "N/A" }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                  {{ formatDate(incident.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    @click="openAffectationModal(incident)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-sm transition-all"
                  >
                    <PaperAirplaneIcon class="h-3.5 w-3.5" />
                    <span>Affecter</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tous les Incidents recents synchros -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-800 tracking-tight">
          <span v-if="authStore.isMaintenancier">Mes interventions en cours</span>
          <span v-else-if="authStore.isAdmin || authStore.isChefService">Derniers incidents du système</span>
          <span v-else>Mes incidents récents</span>
        </h2>
        <RouterLink
          v-if="authStore.isAdmin || authStore.isChefService"
          to="/incidents"
          class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1"
        >
          <span>Voir la base complète des incidents</span>
          <ArrowRightIcon class="h-3.5 w-3.5" />
        </RouterLink>
        <RouterLink
          v-else-if="authStore.isMaintenancier"
          to="/interventions"
          class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1"
        >
          <span>Voir toutes mes interventions</span>
          <ArrowRightIcon class="h-3.5 w-3.5" />
        </RouterLink>
        <RouterLink
          v-else
          to="/mes-incidents"
          class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1"
        >
          <span>Tous mes incidents</span>
          <ArrowRightIcon class="h-3.5 w-3.5" />
        </RouterLink>
      </div>

      <div v-if="incidentStore.incidents.length === 0" class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-slate-500 text-sm font-medium">
        Aucun incident récent.
      </div>

      <div v-else class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/75">
              <tr>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider">Référence</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider">Titre</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider">Statut</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider">Priorité</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider">Auteur / Assigné</th>
                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-450 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
              <tr v-for="incident in incidentStore.incidents.slice(0, 10)" :key="incident.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-650">
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
                  <span v-if="incident.affectation_active?.maintenancier">
                    {{ incident.affectation_active.maintenancier.name }}
                  </span>
                  <span v-else>
                    {{ incident.auteur?.name || "N/A" }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                  {{ formatDate(incident.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="inline-flex text-slate-400 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
                  >
                    <EyeIcon class="h-4.5 w-4.5" />
                  </RouterLink>
                  <button
                    v-if="(authStore.isAdmin || authStore.isChefService) && incident.statut === 'OUVERT'"
                    @click="openAffectationModal(incident)"
                    class="inline-flex ml-2 text-slate-400 hover:text-emerald-600 bg-slate-50 hover:bg-emerald-50 border border-slate-150 rounded-lg p-1.5 transition-colors"
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

    <!-- Quick Actions -->
    <div class="space-y-4">
      <h2 class="text-lg font-bold text-slate-800 tracking-tight">Actions rapides</h2>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Signaler un incident -->
        <RouterLink
          to="/incidents/nouveau"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-indigo-50 text-indigo-600 rounded-xl p-3 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
            <ExclamationTriangleIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Signaler un incident
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Déclarez rapidement une nouvelle panne ou demande d'assistance technique.
            </p>
          </div>
        </RouterLink>

        <!-- Mes Incidents -->
        <RouterLink
          to="/mes-incidents"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-amber-50 text-amber-600 rounded-xl p-3 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
            <ClockIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Mes incidents
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Consultez et suivez l'avancement de vos demandes d'incidents déclarées.
            </p>
          </div>
        </RouterLink>

        <!-- Affectations (Admin / Chef Service) -->
        <RouterLink
          v-if="authStore.isAdmin || authStore.isChefService"
          to="/affectations"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-red-50 text-red-600 rounded-xl p-3 group-hover:bg-red-600 group-hover:text-white transition-colors duration-300">
            <PaperAirplaneIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Affectation des incidents
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Assignez les incidents ouverts aux techniciens de maintenance disponibles.
            </p>
          </div>
        </RouterLink>

        <!-- Statistiques (Chef Service / Admin) -->
        <RouterLink
          v-if="authStore.isChefService || authStore.isAdmin"
          to="/statistiques"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-emerald-50 text-emerald-600 rounded-xl p-3 group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300">
            <ChartBarIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Statistiques & Rapports
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Consultez les tableaux de bord analytiques et les indicateurs clés.
            </p>
          </div>
        </RouterLink>

        <!-- Mes Interventions (Maintenancier) -->
        <RouterLink
          v-if="authStore.isMaintenancier"
          to="/interventions"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-blue-50 text-blue-600 rounded-xl p-3 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
            <WrenchScrewdriverIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Mes interventions
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Gérez vos interventions en cours et résolvez les pannes assignées.
            </p>
          </div>
        </RouterLink>

        <!-- Utilisateurs (Admin) -->
        <RouterLink
          v-if="authStore.isAdmin"
          to="/utilisateurs"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-purple-50 text-purple-600 rounded-xl p-3 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
            <UsersIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Gestion Utilisateurs
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Gérez les comptes utilisateurs, affectez des rôles et modifiez les profils.
            </p>
          </div>
        </RouterLink>
      </div>
    </div>

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

        <div v-if="affectationError" class="p-3 bg-red-50 text-red-700 text-xs font-semibold rounded-xl border border-red-100">
          {{ affectationError }}
        </div>

        <form @submit.prevent="affecterIncidentSubmit" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Maintenancier *
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
                {{ m.name }} ({{ m.matricule || m.email }})
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
              :disabled="!affectationForm.maintenancier_id || affectationLoading"
              class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-indigo-600/10"
            >
              <span v-if="affectationLoading">Affectation...</span>
              <span v-else>Affecter</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
