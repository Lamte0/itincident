<script setup lang="ts">
import { ref, onMounted } from "vue";
import { reportService, incidentService } from "@/services/api";
import type { Incident } from "@/types";
import {
  DocumentArrowDownIcon,
  FunnelIcon,
  TableCellsIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";

const loading = ref(false);
const loadingIncidents = ref(false);
const incidents = ref<Incident[]>([]);
const selectedIncidentId = ref<number | null>(null);

// Dates par défaut : dernier mois
const today = new Date();
const oneMonthAgo = new Date(
  today.getFullYear(),
  today.getMonth() - 1,
  today.getDate()
);

const filters = ref({
  date_debut: oneMonthAgo.toISOString().split("T")[0],
  date_fin: today.toISOString().split("T")[0],
});

const statutOptions = [
  { value: "", label: "Tous les statuts" },
  { value: "CLOTURE", label: "Clôturé" },
  { value: "RESOLU", label: "Résolu" },
  { value: "EN_ATTENTE_VALIDATION", label: "En attente validation" },
];

const filterStatut = ref("CLOTURE");

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

function formatDate(date: string) {
  return new Date(date).toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

async function fetchIncidents() {
  loadingIncidents.value = true;
  try {
    const response = await incidentService.getAll({
      statut: filterStatut.value,
      date_debut: filters.value.date_debut,
      date_fin: filters.value.date_fin,
    });
    incidents.value = response.data.data;
  } catch (err) {
    console.error("Erreur lors du chargement des incidents", err);
  } finally {
    loadingIncidents.value = false;
  }
}

async function downloadFicheIntervention(incidentId: number) {
  loading.value = true;
  try {
    const response = await reportService.getFicheIntervention(incidentId);
    const incident = incidents.value.find((i) => i.id === incidentId);
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `fiche-intervention-${incident?.reference || incidentId}.pdf`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error("Erreur lors du téléchargement", err);
    alert("Erreur lors de la génération de la fiche d'intervention");
  } finally {
    loading.value = false;
  }
}

async function exportIncidents(format: "pdf" | "excel") {
  loading.value = true;
  try {
    const response = await reportService.exportIncidents({
      date_debut: filters.value.date_debut || "",
      date_fin: filters.value.date_fin || "",
      format,
    });
    const extension = format === "pdf" ? "pdf" : "xlsx";
    const mimeType =
      format === "pdf"
        ? "application/pdf"
        : "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
    const url = window.URL.createObjectURL(
      new Blob([response.data], { type: mimeType })
    );
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `incidents-${filters.value.date_debut}-${filters.value.date_fin}.${extension}`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error("Erreur lors de l'export", err);
    alert("Erreur lors de l'export des incidents");
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchIncidents();
});
</script>

<template>
  <div class="space-y-6">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Rapports</h1>
      <p class="text-sm text-slate-500">
        Générer des fiches d'intervention et exporter les données d'incidents.
      </p>
    </div>

    <!-- Filtres et Export -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-6">
      <div class="flex items-center gap-2 pb-4 border-b border-slate-100">
        <FunnelIcon class="h-5 w-5 text-slate-400" />
        <h2 class="text-base font-bold text-slate-800">Filtres et Export</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date de début</label>
          <input
            v-model="filters.date_debut"
            type="date"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date de fin</label>
          <input
            v-model="filters.date_fin"
            type="date"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Statut</label>
          <select
            v-model="filterStatut"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
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
        <div class="flex items-end">
          <button
            @click="fetchIncidents"
            :disabled="loadingIncidents"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors disabled:opacity-50 w-full"
          >
            <span v-if="loadingIncidents">Chargement...</span>
            <span v-else>Filtrer</span>
          </button>
        </div>
      </div>

      <div class="border-t border-slate-100 pt-6 space-y-3">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
          Exporter tous les incidents de la période
        </h3>
        <div class="flex flex-wrap gap-3">
          <button
            @click="exportIncidents('pdf')"
            :disabled="loading"
            class="inline-flex items-center justify-center px-4 py-2.5 border border-slate-250 bg-white hover:bg-slate-50 font-semibold rounded-xl text-sm text-slate-700 transition-colors shadow-sm gap-2"
          >
            <DocumentTextIcon class="h-4.5 w-4.5 text-red-500" />
            Export PDF
          </button>
          <button
            @click="exportIncidents('excel')"
            :disabled="loading"
            class="inline-flex items-center justify-center px-4 py-2.5 border border-slate-250 bg-white hover:bg-slate-50 font-semibold rounded-xl text-sm text-slate-700 transition-colors shadow-sm gap-2"
          >
            <TableCellsIcon class="h-4.5 w-4.5 text-green-550" />
            Export Excel
          </button>
        </div>
      </div>
    </div>

    <!-- Fiches d'intervention -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-6">
      <div class="flex items-center gap-2 pb-4 border-b border-slate-100">
        <DocumentArrowDownIcon class="h-5 w-5 text-slate-400" />
        <h2 class="text-base font-bold text-slate-800">Fiches d'intervention</h2>
      </div>

      <p class="text-sm text-slate-500">
        Sélectionnez un incident clôturé pour générer sa fiche d'intervention PDF officielle.
      </p>

      <!-- Loading -->
      <div v-if="loadingIncidents" class="flex justify-center py-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Liste des incidents -->
      <div
        v-else-if="incidents.length === 0"
        class="text-center py-8 text-slate-550 text-sm font-medium"
      >
        Aucun incident trouvé pour cette période et ce statut.
      </div>

      <div v-else class="overflow-x-auto border border-slate-150 rounded-2xl overflow-hidden">
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
                Auteur
              </th>
              <th
                class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
              >
                Maintenancier
              </th>
              <th
                class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
              >
                Date clôture
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
              v-for="incident in incidents"
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
                {{ incident.auteur?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                {{ incident.affectation_active?.maintenancier?.name || "-" }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 font-semibold">
                {{
                  incident.date_cloture
                    ? formatDate(incident.date_cloture)
                    : "-"
                }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <button
                  v-if="incident.statut === 'CLOTURE'"
                  @click="downloadFicheIntervention(incident.id)"
                  :disabled="loading"
                  class="inline-flex items-center justify-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs transition-colors disabled:opacity-50 gap-1.5 shadow-sm shadow-indigo-650/10"
                >
                  <DocumentArrowDownIcon class="h-3.5 w-3.5" />
                  Fiche PDF
                </button>
                <span v-else class="text-slate-400 text-xs font-semibold">
                  Non disponible
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Aide -->
    <div class="bg-indigo-50/40 border border-indigo-100/85 rounded-2xl p-5 space-y-2">
      <h3 class="text-sm font-bold text-indigo-950 flex items-center gap-2">
        À propos des fiches d'intervention
      </h3>
      <ul class="text-sm text-indigo-850 list-disc list-inside space-y-1.5 pl-1 leading-relaxed font-medium">
        <li>
          Les fiches d'intervention ne sont générables que pour les incidents ayant le statut <strong class="text-indigo-950">Clôturé</strong>.
        </li>
        <li>
          Chaque document PDF regroupe l'historique complet, le rapport d'intervention du technicien et la validation de l'utilisateur.
        </li>
        <li>
          L'export au format Excel est idéal pour l'analyse globale et la création de tableaux croisés dynamiques.
        </li>
      </ul>
    </div>
  </div>
</template>
