<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useIncidentStore } from "@/stores/incidents";
import { useAuthStore } from "@/stores/auth";
import { incidentService } from "@/services/api";
import type { Incident } from "@/types";
import { RouterLink } from "vue-router";
import {
  EyeIcon,
  PlayIcon,
  CheckCircleIcon,
  ClockIcon,
  WrenchScrewdriverIcon,
} from "@heroicons/vue/24/outline";

const incidentStore = useIncidentStore();
const authStore = useAuthStore();

const loading = ref(false);
const interventions = ref<Incident[]>([]);
const errorMessage = ref<string | null>(null);

// Modal résolution
const showResolutionModal = ref(false);
const selectedIncident = ref<Incident | null>(null);
const resolutionForm = ref({
  rapport_intervention: "",
});

// Filtrer les incidents par statut (le backend filtre déjà par maintenancier)
const mesInterventionsEnAttente = computed(() => {
  return interventions.value.filter(
    (incident) => incident.statut === "AFFECTE"
  );
});

const mesInterventionsEnCours = computed(() => {
  return interventions.value.filter(
    (incident) => incident.statut === "EN_COURS"
  );
});

const mesInterventionsTerminees = computed(() => {
  return interventions.value.filter(
    (incident) =>
      incident.statut === "RESOLU" ||
      incident.statut === "EN_ATTENTE_VALIDATION" ||
      incident.statut === "CLOTURE"
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

async function fetchInterventions() {
  loading.value = true;
  errorMessage.value = null;
  try {
    // Utiliser l'endpoint dédié pour les interventions du maintenancier
    const response = await incidentService.getMyInterventions();
    interventions.value = response.data;
    console.log("Interventions chargées:", interventions.value);
  } catch (err: any) {
    console.error("Erreur lors du chargement des interventions", err);
    errorMessage.value =
      err.response?.data?.message ||
      "Erreur lors du chargement des interventions";
  } finally {
    loading.value = false;
  }
}

async function prendreEnCharge(incident: Incident) {
  try {
    await incidentStore.prendreEnCharge(incident.id);
    await fetchInterventions();
  } catch (err) {
    console.error("Erreur lors de la prise en charge", err);
  }
}

function openResolutionModal(incident: Incident) {
  selectedIncident.value = incident;
  resolutionForm.value = { rapport_intervention: "" };
  showResolutionModal.value = true;
}

async function resoudre() {
  if (!selectedIncident.value || !resolutionForm.value.rapport_intervention)
    return;
  try {
    await incidentStore.resoudreIncident(
      selectedIncident.value.id,
      resolutionForm.value.rapport_intervention
    );
    showResolutionModal.value = false;
    selectedIncident.value = null;
    await fetchInterventions();
  } catch (err) {
    console.error("Erreur lors de la résolution", err);
  }
}

onMounted(() => {
  fetchInterventions();
});
</script>

<template>
  <div class="space-y-6">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Mes Interventions</h1>
      <p class="text-sm text-slate-500">
        Gérer et traiter les incidents qui vous sont affectés.
      </p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Message d'erreur -->
    <div
      v-if="errorMessage"
      class="bg-red-50 border border-red-100 text-red-650 p-4 rounded-2xl text-sm font-semibold animate-fade-in-up"
    >
      {{ errorMessage }}
    </div>

    <template v-else-if="!loading">
      <!-- Cartes de synthèse -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-blue-50 text-blue-600 border border-blue-100 rounded-xl">
            <ClockIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">En attente</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
              {{ mesInterventionsEnAttente.length }}
            </p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-amber-50 text-amber-600 border border-amber-100 rounded-xl">
            <WrenchScrewdriverIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">En cours</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
              {{ mesInterventionsEnCours.length }}
            </p>
          </div>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-xl">
            <CheckCircleIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">Terminées</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
              {{ mesInterventionsTerminees.length }}
            </p>
          </div>
        </div>
      </div>

      <!-- Interventions en attente de prise en charge -->
      <div class="space-y-4 pt-2">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
          <span class="inline-flex items-center gap-2">
            <ClockIcon class="h-4.5 w-4.5 text-blue-500" />
            En attente de prise en charge
            <span
              v-if="mesInterventionsEnAttente.length > 0"
              class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-blue-700 bg-blue-50 border border-blue-100 rounded-md"
            >
              {{ mesInterventionsEnAttente.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="mesInterventionsEnAttente.length === 0"
          class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-slate-500 text-sm font-medium shadow-sm"
        >
          Aucune intervention en attente.
        </div>

        <div v-else class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
          <div class="divide-y divide-slate-100">
            <div
              v-for="incident in mesInterventionsEnAttente"
              :key="incident.id"
              class="p-6 hover:bg-slate-50/50 transition-colors"
            >
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex-1 space-y-2">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-indigo-650 tracking-wider">
                      {{ incident.reference }}
                    </span>
                    <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getPrioriteBadge(incident.priorite)]">
                      {{ incident.priorite }}
                    </span>
                  </div>
                  <h3 class="text-base font-bold text-slate-700 leading-snug">
                    {{ incident.titre }}
                  </h3>
                  <p class="text-xs font-semibold text-slate-450">
                    {{ incident.type }} <span class="text-slate-300">•</span> {{ formatDate(incident.created_at) }}
                  </p>
                  <div
                    v-if="incident.affectation_active?.instructions"
                    class="text-sm text-slate-650 bg-slate-50/80 border border-slate-200/50 p-4 rounded-xl leading-relaxed"
                  >
                    <strong class="text-slate-800 block text-xs uppercase tracking-wider mb-1 font-bold">Instructions :</strong>
                    {{ incident.affectation_active.instructions }}
                  </div>
                </div>
                <div class="flex sm:self-center gap-2">
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="inline-flex items-center justify-center px-4 py-2 border border-slate-250 bg-white hover:bg-slate-50 font-semibold rounded-xl text-sm text-slate-700 transition-colors shadow-sm gap-2"
                  >
                    <EyeIcon class="h-4.5 w-4.5" />
                    Voir
                  </RouterLink>
                  <button
                    @click="prendreEnCharge(incident)"
                    class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-indigo-600/10 gap-2"
                  >
                    <PlayIcon class="h-4.5 w-4.5" />
                    Prendre en charge
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interventions en cours -->
      <div class="space-y-4 pt-2">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
          <span class="inline-flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-4.5 w-4.5 text-amber-500" />
            En cours de traitement
            <span
              v-if="mesInterventionsEnCours.length > 0"
              class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-amber-700 bg-amber-50 border border-amber-100 rounded-md"
            >
              {{ mesInterventionsEnCours.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="mesInterventionsEnCours.length === 0"
          class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-slate-500 text-sm font-medium shadow-sm"
        >
          Aucune intervention en cours.
        </div>

        <div v-else class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
          <div class="divide-y divide-slate-100">
            <div
              v-for="incident in mesInterventionsEnCours"
              :key="incident.id"
              class="p-6 hover:bg-slate-50/50 transition-colors"
            >
              <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="flex-1 space-y-2">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-indigo-650 tracking-wider">
                      {{ incident.reference }}
                    </span>
                    <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getPrioriteBadge(incident.priorite)]">
                      {{ incident.priorite }}
                    </span>
                    <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getStatutBadge(incident.statut)]">
                      EN COURS
                    </span>
                  </div>
                  <h3 class="text-base font-bold text-slate-700 leading-snug">
                    {{ incident.titre }}
                  </h3>
                  <p class="text-xs font-semibold text-slate-450">
                    {{ incident.type }} <span class="text-slate-300">•</span> Pris en charge le
                    {{
                      incident.affectation_active?.date_prise_en_charge
                        ? formatDate(incident.affectation_active.date_prise_en_charge)
                        : "-"
                    }}
                  </p>
                </div>
                <div class="flex sm:self-center gap-2">
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="inline-flex items-center justify-center px-4 py-2 border border-slate-250 bg-white hover:bg-slate-50 font-semibold rounded-xl text-sm text-slate-700 transition-colors shadow-sm gap-2"
                  >
                    <EyeIcon class="h-4.5 w-4.5" />
                    Voir
                  </RouterLink>
                  <button
                    @click="openResolutionModal(incident)"
                    class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm shadow-emerald-600/10 gap-2"
                  >
                    <CheckCircleIcon class="h-4.5 w-4.5" />
                    Résoudre
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interventions terminées -->
      <div class="space-y-4 pt-2">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
          <span class="inline-flex items-center gap-2">
            <CheckCircleIcon class="h-4.5 w-4.5 text-emerald-500" />
            Terminées
          </span>
        </h2>

        <div
          v-if="mesInterventionsTerminees.length === 0"
          class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center text-slate-500 text-sm font-medium shadow-sm"
        >
          Aucune intervention terminée.
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
                    Date résolution
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
                  v-for="incident in mesInterventionsTerminees"
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
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                    {{
                      incident.affectation_active?.date_resolution
                        ? formatDate(incident.affectation_active.date_resolution)
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
    </template>

    <!-- Modal Résolution -->
    <div
      v-if="showResolutionModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-lg w-full p-6 animate-fade-in-up space-y-6">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-800">
            Rapport d'intervention
          </h3>
          <button
            @click="showResolutionModal = false"
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

        <form @submit.prevent="resoudre" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Rapport d'intervention *
            </label>
            <textarea
              v-model="resolutionForm.rapport_intervention"
              rows="6"
              required
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all leading-relaxed"
              placeholder="Décrivez les actions effectuées pour résoudre l'incident...

• Diagnostic effectué
• Actions correctives
• Pièces remplacées (si applicable)
• Recommandations"
            ></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showResolutionModal = false"
              class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="
                !resolutionForm.rapport_intervention || incidentStore.loading
              "
              class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-emerald-600/10"
            >
              <span v-if="incidentStore.loading">Envoi...</span>
              <span v-else>Marquer comme résolu</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
