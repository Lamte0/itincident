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
    OUVERT: "bg-red-100 text-red-800",
    AFFECTE: "bg-blue-100 text-blue-800",
    EN_COURS: "bg-yellow-100 text-yellow-800",
    RESOLU: "bg-green-100 text-green-800",
    EN_ATTENTE_VALIDATION: "bg-purple-100 text-purple-800",
    CLOTURE: "bg-gray-100 text-gray-800",
  };
  return badges[statut] || "bg-gray-100 text-gray-800";
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
      date_debut: filters.value.date_debut,
      date_fin: filters.value.date_fin,
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
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Rapports</h1>
      <p class="mt-1 text-sm text-gray-500">
        Générer des fiches d'intervention et exporter les données
      </p>
    </div>

    <!-- Filtres et Export -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <div class="flex items-center mb-4">
        <FunnelIcon class="h-5 w-5 text-gray-400 mr-2" />
        <h2 class="text-lg font-medium text-gray-900">Filtres et Export</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700"
            >Date de début</label
          >
          <input
            v-model="filters.date_debut"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700"
            >Date de fin</label
          >
          <input
            v-model="filters.date_fin"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Statut</label>
          <select
            v-model="filterStatut"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
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
            class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 disabled:opacity-50"
          >
            <span v-if="loadingIncidents">Chargement...</span>
            <span v-else>Filtrer</span>
          </button>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h3 class="text-sm font-medium text-gray-700 mb-3">
          Exporter tous les incidents de la période
        </h3>
        <div class="flex space-x-3">
          <button
            @click="exportIncidents('pdf')"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <DocumentTextIcon class="h-5 w-5 mr-2 text-red-500" />
            Export PDF
          </button>
          <button
            @click="exportIncidents('excel')"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <TableCellsIcon class="h-5 w-5 mr-2 text-green-500" />
            Export Excel
          </button>
        </div>
      </div>
    </div>

    <!-- Fiches d'intervention -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center mb-4">
        <DocumentArrowDownIcon class="h-5 w-5 text-gray-400 mr-2" />
        <h2 class="text-lg font-medium text-gray-900">Fiches d'intervention</h2>
      </div>

      <p class="text-sm text-gray-500 mb-4">
        Sélectionnez un incident clôturé pour générer sa fiche d'intervention
        PDF.
      </p>

      <!-- Loading -->
      <div v-if="loadingIncidents" class="flex justify-center py-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"
        ></div>
      </div>

      <!-- Liste des incidents -->
      <div
        v-else-if="incidents.length === 0"
        class="text-center py-8 text-gray-500"
      >
        Aucun incident trouvé pour cette période et ce statut.
      </div>

      <div v-else class="overflow-x-auto">
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
                Auteur
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Maintenancier
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Date clôture
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
              v-for="incident in incidents"
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
                {{ incident.auteur?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ incident.affectation_active?.maintenancier?.name || "-" }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                >
                  <DocumentArrowDownIcon class="h-4 w-4 mr-1" />
                  Fiche PDF
                </button>
                <span v-else class="text-gray-400 text-xs">
                  Non disponible
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Aide -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h3 class="text-sm font-medium text-blue-800 mb-2">
        À propos des fiches d'intervention
      </h3>
      <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
        <li>
          Les fiches d'intervention ne sont disponibles que pour les incidents
          clôturés.
        </li>
        <li>
          Chaque fiche contient : les informations de l'incident, le rapport
          d'intervention du maintenancier, et la validation de l'utilisateur.
        </li>
        <li>
          L'export Excel permet d'analyser les données dans un tableur pour des
          statistiques personnalisées.
        </li>
      </ul>
    </div>
  </div>
</template>
