<script setup lang="ts">
import { onMounted } from "vue";
import { useIncidentStore } from "@/stores/incidents";
import { RouterLink } from "vue-router";
import { EyeIcon, PlusIcon } from "@heroicons/vue/24/outline";

const incidentStore = useIncidentStore();

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

onMounted(() => {
  incidentStore.fetchMyIncidents();
});
</script>

<template>
  <div>
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Mes Incidents</h1>
        <p class="mt-1 text-sm text-gray-500">
          Liste des incidents que vous avez déclarés
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <RouterLink
          to="/incidents/nouveau"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-black bg-primary-600 hover:bg-primary-700"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Nouvel incident
        </RouterLink>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="incidentStore.loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Liste vide -->
    <div
      v-else-if="incidentStore.incidents.length === 0"
      class="text-center py-12 bg-white rounded-lg shadow"
    >
      <svg
        class="mx-auto h-12 w-12 text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun incident</h3>
      <p class="mt-1 text-sm text-gray-500">
        Vous n'avez pas encore déclaré d'incident.
      </p>
      <div class="mt-6">
        <RouterLink
          to="/incidents/nouveau"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-green bg-primary-600 hover:bg-primary-700"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Déclarer un incident
        </RouterLink>
      </div>
    </div>

    <!-- Liste des incidents -->
    <div v-else class="space-y-4">
      <div
        v-for="incident in incidentStore.incidents"
        :key="incident.id"
        class="bg-white shadow rounded-lg p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <span class="text-sm font-medium text-primary-600">{{
                incident.reference
              }}</span>
              <span
                :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  getStatutBadge(incident.statut),
                ]"
              >
                {{ incident.statut.replace("_", " ") }}
              </span>
              <span
                :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  getPrioriteBadge(incident.priorite),
                ]"
              >
                {{ incident.priorite }}
              </span>
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">
              {{ incident.titre }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 line-clamp-2">
              {{ incident.description }}
            </p>
            <div class="mt-3 flex items-center space-x-4 text-xs text-gray-500">
              <span>Type: {{ incident.type }}</span>
              <span>•</span>
              <span
                >Créé le
                {{
                  new Date(incident.created_at).toLocaleDateString("fr-FR")
                }}</span
              >
            </div>
          </div>
          <RouterLink
            :to="`/incidents/${incident.id}`"
            class="ml-4 text-primary-600 hover:text-primary-900"
          >
            <EyeIcon class="h-6 w-6" />
          </RouterLink>
        </div>

        <!-- Actions selon le statut -->
        <div
          v-if="incident.statut === 'EN_ATTENTE_VALIDATION'"
          class="mt-4 pt-4 border-t border-gray-200"
        >
          <div class="flex items-center justify-between">
            <p class="text-sm text-purple-600 font-medium">
              ✅ Cet incident a été résolu. Veuillez valider la clôture.
            </p>
            <RouterLink
              :to="`/incidents/${incident.id}`"
              class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700"
            >
              Valider la clôture
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
