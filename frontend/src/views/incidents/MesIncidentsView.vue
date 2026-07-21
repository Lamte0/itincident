<script setup lang="ts">
import { onMounted } from "vue";
import { useIncidentStore } from "@/stores/incidents";
import { RouterLink } from "vue-router";
import { EyeIcon, PlusIcon } from "@heroicons/vue/24/outline";

const incidentStore = useIncidentStore();

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

onMounted(() => {
  incidentStore.fetchMyIncidents();
});
</script>

<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Mes Incidents</h1>
        <p class="text-sm text-slate-500">
          Liste des incidents que vous avez déclarés.
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <RouterLink
          to="/incidents/nouveau"
          class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm shadow-indigo-600/10 hover:-translate-y-0.5"
        >
          <PlusIcon class="h-4.5 w-4.5 mr-2" />
          Nouvel incident
        </RouterLink>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="incidentStore.loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Liste vide -->
    <div
      v-else-if="incidentStore.incidents.length === 0"
      class="text-center py-12 bg-white border border-slate-200/80 rounded-2xl shadow-sm"
    >
      <svg
        class="mx-auto h-12 w-12 text-slate-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
        />
      </svg>
      <h3 class="mt-4 text-base font-bold text-slate-800">Aucun incident déclaré</h3>
      <p class="mt-1 text-sm text-slate-500">
        Vous n'avez pas encore déclaré d'incident de votre côté.
      </p>
      <div class="mt-6">
        <RouterLink
          to="/incidents/nouveau"
          class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors shadow-sm"
        >
          <PlusIcon class="h-4.5 w-4.5 mr-2" />
          Déclarer un incident
        </RouterLink>
      </div>
    </div>

    <!-- Liste des incidents -->
    <div v-else class="space-y-4">
      <div
        v-for="incident in incidentStore.incidents"
        :key="incident.id"
        class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-slate-300 transition-all duration-200"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs font-bold text-indigo-650 tracking-wider">{{
                incident.reference
              }}</span>
              <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getStatutBadge(incident.statut)]">
                {{ incident.statut.replace("_", " ") }}
              </span>
              <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-md', getPrioriteBadge(incident.priorite)]">
                {{ incident.priorite }}
              </span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 leading-tight">
              {{ incident.titre }}
            </h3>
            <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed">
              {{ incident.description }}
            </p>
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-450 pt-1">
              <span>Type: {{ incident.type }}</span>
              <span class="text-slate-300">•</span>
              <span>Créé le {{ new Date(incident.created_at).toLocaleDateString("fr-FR") }}</span>
            </div>
          </div>
          <RouterLink
            :to="`/incidents/${incident.id}`"
            class="text-slate-400 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-150 rounded-xl p-2 transition-colors flex-shrink-0"
          >
            <EyeIcon class="h-5 w-5" />
          </RouterLink>
        </div>

        <!-- Actions selon le statut -->
        <div
          v-if="incident.statut === 'EN_ATTENTE_VALIDATION'"
          class="mt-4 pt-4 border-t border-slate-100"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-purple-50/40 border border-purple-100 p-4 rounded-xl">
            <p class="text-sm text-purple-700 font-semibold flex items-center gap-2">
              <span>✅</span> Cet incident a été résolu. Veuillez valider la clôture.
            </p>
            <RouterLink
              :to="`/incidents/${incident.id}`"
              class="inline-flex justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm shadow-purple-600/10"
            >
              Valider la clôture
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
