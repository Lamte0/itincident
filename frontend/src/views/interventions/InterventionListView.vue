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
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Mes Interventions</h1>
      <p class="mt-1 text-sm text-gray-500">
        Gérer les incidents qui vous sont affectés
      </p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Message d'erreur -->
    <div
      v-if="errorMessage"
      class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
    >
      {{ errorMessage }}
    </div>

    <template v-else-if="!loading">
      <!-- Cartes de synthèse -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
              <ClockIcon class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">En attente</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ mesInterventionsEnAttente.length }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
              <WrenchScrewdriverIcon class="h-6 w-6 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">En cours</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ mesInterventionsEnCours.length }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
              <CheckCircleIcon class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Terminées</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ mesInterventionsTerminees.length }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Interventions en attente de prise en charge -->
      <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          <span class="inline-flex items-center">
            <ClockIcon class="h-5 w-5 mr-2 text-blue-500" />
            En attente de prise en charge
            <span
              v-if="mesInterventionsEnAttente.length > 0"
              class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-100 bg-blue-600 rounded-full"
            >
              {{ mesInterventionsEnAttente.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="mesInterventionsEnAttente.length === 0"
          class="bg-white shadow rounded-lg p-6 text-center text-gray-500"
        >
          Aucune intervention en attente
        </div>

        <div v-else class="bg-white shadow overflow-hidden rounded-lg">
          <div class="divide-y divide-gray-200">
            <div
              v-for="incident in mesInterventionsEnAttente"
              :key="incident.id"
              class="p-4 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-primary-600">
                      {{ incident.reference }}
                    </span>
                    <span
                      :class="[
                        'px-2 py-0.5 text-xs font-semibold rounded-full',
                        getPrioriteBadge(incident.priorite),
                      ]"
                    >
                      {{ incident.priorite }}
                    </span>
                  </div>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ incident.titre }}
                  </p>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ incident.type }} • {{ formatDate(incident.created_at) }}
                  </p>
                  <p
                    v-if="incident.affectation_active?.instructions"
                    class="mt-2 text-sm text-gray-600 bg-gray-50 p-2 rounded"
                  >
                    <strong>Instructions :</strong>
                    {{ incident.affectation_active.instructions }}
                  </p>
                </div>
                <div class="flex space-x-2">
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    Voir
                  </RouterLink>
                  <button
                    @click="prendreEnCharge(incident)"
                    class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                  >
                    <PlayIcon class="h-4 w-4 mr-1" />
                    Prendre en charge
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interventions en cours -->
      <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          <span class="inline-flex items-center">
            <WrenchScrewdriverIcon class="h-5 w-5 mr-2 text-yellow-500" />
            En cours de traitement
            <span
              v-if="mesInterventionsEnCours.length > 0"
              class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-yellow-100 bg-yellow-600 rounded-full"
            >
              {{ mesInterventionsEnCours.length }}
            </span>
          </span>
        </h2>

        <div
          v-if="mesInterventionsEnCours.length === 0"
          class="bg-white shadow rounded-lg p-6 text-center text-gray-500"
        >
          Aucune intervention en cours
        </div>

        <div v-else class="bg-white shadow overflow-hidden rounded-lg">
          <div class="divide-y divide-gray-200">
            <div
              v-for="incident in mesInterventionsEnCours"
              :key="incident.id"
              class="p-4 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-primary-600">
                      {{ incident.reference }}
                    </span>
                    <span
                      :class="[
                        'px-2 py-0.5 text-xs font-semibold rounded-full',
                        getPrioriteBadge(incident.priorite),
                      ]"
                    >
                      {{ incident.priorite }}
                    </span>
                    <span
                      :class="[
                        'px-2 py-0.5 text-xs font-semibold rounded-full',
                        getStatutBadge(incident.statut),
                      ]"
                    >
                      EN COURS
                    </span>
                  </div>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ incident.titre }}
                  </p>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ incident.type }} • Pris en charge le
                    {{
                      incident.affectation_active?.date_prise_en_charge
                        ? formatDate(
                            incident.affectation_active.date_prise_en_charge
                          )
                        : "-"
                    }}
                  </p>
                </div>
                <div class="flex space-x-2">
                  <RouterLink
                    :to="`/incidents/${incident.id}`"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    Voir
                  </RouterLink>
                  <button
                    @click="openResolutionModal(incident)"
                    class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                  >
                    <CheckCircleIcon class="h-4 w-4 mr-1" />
                    Résoudre
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interventions terminées -->
      <div>
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          <span class="inline-flex items-center">
            <CheckCircleIcon class="h-5 w-5 mr-2 text-green-500" />
            Terminées
          </span>
        </h2>

        <div
          v-if="mesInterventionsTerminees.length === 0"
          class="bg-white shadow rounded-lg p-6 text-center text-gray-500"
        >
          Aucune intervention terminée
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
                  Date résolution
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
                v-for="incident in mesInterventionsTerminees"
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
    </template>

    <!-- Modal Résolution -->
    <div v-if="showResolutionModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75"
          @click="showResolutionModal = false"
        ></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Rapport d'intervention
          </h3>

          <div v-if="selectedIncident" class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-900">
              {{ selectedIncident.reference }} - {{ selectedIncident.titre }}
            </p>
          </div>

          <form @submit.prevent="resoudre" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Rapport d'intervention *
              </label>
              <textarea
                v-model="resolutionForm.rapport_intervention"
                rows="6"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Décrivez les actions effectuées pour résoudre l'incident...

• Diagnostic effectué
• Actions correctives
• Pièces remplacées (si applicable)
• Recommandations"
              ></textarea>
            </div>
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="showResolutionModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="
                  !resolutionForm.rapport_intervention || incidentStore.loading
                "
                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                <span v-if="incidentStore.loading">Envoi...</span>
                <span v-else>Marquer comme résolu</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
