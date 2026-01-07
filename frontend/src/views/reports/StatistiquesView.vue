<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { reportService } from "@/services/api";
import type { Statistiques } from "@/types";
import {
  Chart as ChartJS,
  ArcElement,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";
import { Doughnut, Bar, Line } from "vue-chartjs";
import {
  ChartBarIcon,
  ClockIcon,
  StarIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ArrowTrendingUpIcon,
} from "@heroicons/vue/24/outline";

// Enregistrement des composants Chart.js
ChartJS.register(
  ArcElement,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const loading = ref(false);
const error = ref<string | null>(null);
const statistiques = ref<Statistiques | null>(null);

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

// Configuration des graphiques
const chartKey = ref(0);

// Graphique Doughnut - Répartition par statut
const statutChartData = computed(() => {
  if (!statistiques.value) return { labels: [], datasets: [] };
  const statuts = statistiques.value.incidents_par_statut;
  return {
    labels: [
      "Ouvert",
      "Affecté",
      "En cours",
      "Résolu",
      "En attente",
      "Clôturé",
    ],
    datasets: [
      {
        data: [
          statuts.OUVERT || 0,
          statuts.AFFECTE || 0,
          statuts.EN_COURS || 0,
          statuts.RESOLU || 0,
          statuts.EN_ATTENTE_VALIDATION || 0,
          statuts.CLOTURE || 0,
        ],
        backgroundColor: [
          "#EF4444", // red
          "#3B82F6", // blue
          "#F59E0B", // yellow
          "#10B981", // green
          "#8B5CF6", // purple
          "#6B7280", // gray
        ],
        borderWidth: 0,
      },
    ],
  };
});

const statutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "right" as const,
      labels: {
        usePointStyle: true,
        padding: 15,
      },
    },
  },
};

// Graphique Bar - Répartition par type
const typeChartData = computed(() => {
  if (!statistiques.value) return { labels: [], datasets: [] };
  const types = statistiques.value.incidents_par_type;
  return {
    labels: ["Réseau", "Logiciel", "Hardware"],
    datasets: [
      {
        label: "Incidents",
        data: [types.RESEAU || 0, types.LOGICIEL || 0, types.HARDWARE || 0],
        backgroundColor: ["#3B82F6", "#8B5CF6", "#F97316"],
        borderRadius: 8,
      },
    ],
  };
});

const typeChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
      },
    },
  },
};

// Graphique Bar - Répartition par priorité
const prioriteChartData = computed(() => {
  if (!statistiques.value) return { labels: [], datasets: [] };
  const priorites = statistiques.value.incidents_par_priorite;
  return {
    labels: ["Basse", "Moyenne", "Haute", "Critique"],
    datasets: [
      {
        label: "Incidents",
        data: [
          priorites.BASSE || 0,
          priorites.MOYENNE || 0,
          priorites.HAUTE || 0,
          priorites.CRITIQUE || 0,
        ],
        backgroundColor: ["#10B981", "#F59E0B", "#F97316", "#EF4444"],
        borderRadius: 8,
      },
    ],
  };
});

const prioriteChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: "y" as const,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
      },
    },
  },
};

// Graphique Line - Évolution sur la période
const evolutionChartData = computed(() => {
  if (!statistiques.value?.incidents_par_jour)
    return { labels: [], datasets: [] };
  const data = statistiques.value.incidents_par_jour;
  return {
    labels: data.map((d) => formatDateShort(d.date)),
    datasets: [
      {
        label: "Incidents",
        data: data.map((d) => d.count),
        borderColor: "#6366F1",
        backgroundColor: "rgba(99, 102, 241, 0.1)",
        fill: true,
        tension: 0.4,
        pointBackgroundColor: "#6366F1",
        pointBorderColor: "#fff",
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
      },
    ],
  };
});

const evolutionChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      mode: "index" as const,
      intersect: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
      },
    },
    x: {
      grid: {
        display: false,
      },
    },
  },
  interaction: {
    mode: "nearest" as const,
    axis: "x" as const,
    intersect: false,
  },
};

const totalParStatut = computed(() => {
  if (!statistiques.value) return [];
  const statuts = statistiques.value.incidents_par_statut;
  return [
    { label: "Ouvert", value: statuts.OUVERT || 0, color: "bg-red-500" },
    { label: "Affecté", value: statuts.AFFECTE || 0, color: "bg-blue-500" },
    { label: "En cours", value: statuts.EN_COURS || 0, color: "bg-yellow-500" },
    { label: "Résolu", value: statuts.RESOLU || 0, color: "bg-green-500" },
    {
      label: "En attente validation",
      value: statuts.EN_ATTENTE_VALIDATION || 0,
      color: "bg-purple-500",
    },
    { label: "Clôturé", value: statuts.CLOTURE || 0, color: "bg-gray-500" },
  ];
});

function formatTemps(heures: number): string {
  if (heures < 1) {
    return `${Math.round(heures * 60)} min`;
  } else if (heures < 24) {
    return `${heures.toFixed(1)} h`;
  } else {
    const jours = Math.floor(heures / 24);
    const heuresRestantes = Math.round(heures % 24);
    return `${jours}j ${heuresRestantes}h`;
  }
}

function formatDateShort(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleDateString("fr-FR", { day: "2-digit", month: "short" });
}

async function fetchStatistiques() {
  loading.value = true;
  error.value = null;

  try {
    const response = await reportService.getStatistiques({
      date_debut: filters.value.date_debut,
      date_fin: filters.value.date_fin,
    });
    statistiques.value = response.data;
    // Force le re-render des graphiques
    chartKey.value++;
  } catch (err: any) {
    error.value =
      err.response?.data?.message ||
      "Erreur lors du chargement des statistiques";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchStatistiques();
});
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Statistiques</h1>
      <p class="mt-1 text-sm text-gray-500">
        Restitutions et analyses des incidents sur période
      </p>
    </div>

    <!-- Filtres de période -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <div class="flex flex-wrap items-end gap-4">
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
        <button
          @click="fetchStatistiques"
          :disabled="loading"
          class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 disabled:opacity-50"
        >
          <span v-if="loading">Chargement...</span>
          <span v-else>Actualiser</span>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700"
    >
      {{ error }}
    </div>

    <template v-else-if="statistiques">
      <!-- Cartes de synthèse -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total incidents -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-primary-100 rounded-lg">
              <ExclamationTriangleIcon class="h-6 w-6 text-primary-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Incidents</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ statistiques.total_incidents }}
              </p>
            </div>
          </div>
        </div>

        <!-- Temps moyen de résolution -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
              <ClockIcon class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">
                Temps moyen résolution
              </p>
              <p class="text-2xl font-bold text-gray-900">
                {{ formatTemps(statistiques.temps_moyen_resolution) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Note moyenne -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
              <StarIcon class="h-6 w-6 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Note moyenne</p>
              <p class="text-2xl font-bold text-gray-900">
                {{ statistiques.note_moyenne.toFixed(1) }} / 5
              </p>
            </div>
          </div>
        </div>

        <!-- Taux de clôture -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
              <CheckCircleIcon class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Taux de clôture</p>
              <p class="text-2xl font-bold text-gray-900">
                {{
                  statistiques.total_incidents > 0
                    ? Math.round(
                        ((statistiques.incidents_par_statut.CLOTURE || 0) /
                          statistiques.total_incidents) *
                          100
                      )
                    : 0
                }}%
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Graphique Doughnut - Répartition par statut -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">
            Répartition par statut
          </h2>
          <div class="h-64">
            <Doughnut
              :key="'statut-' + chartKey"
              :data="statutChartData"
              :options="statutChartOptions"
            />
          </div>
        </div>

        <!-- Graphique Bar - Répartition par type -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">
            Répartition par type
          </h2>
          <div class="h-64">
            <Bar
              :key="'type-' + chartKey"
              :data="typeChartData"
              :options="typeChartOptions"
            />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Graphique Bar horizontal - Répartition par priorité -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">
            Répartition par priorité
          </h2>
          <div class="h-64">
            <Bar
              :key="'priorite-' + chartKey"
              :data="prioriteChartData"
              :options="prioriteChartOptions"
            />
          </div>
        </div>

        <!-- Graphique Line - Évolution sur la période -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">
            <div class="flex items-center">
              <ArrowTrendingUpIcon class="h-5 w-5 mr-2 text-gray-400" />
              Évolution sur la période
            </div>
          </h2>
          <div v-if="statistiques.incidents_par_jour?.length" class="h-64">
            <Line
              :key="'evolution-' + chartKey"
              :data="evolutionChartData"
              :options="evolutionChartOptions"
            />
          </div>
          <div
            v-else
            class="flex items-center justify-center h-64 text-gray-500"
          >
            Aucune donnée sur cette période
          </div>
        </div>
      </div>

      <!-- Détail par statut (barres de progression) -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          Détail par statut
        </h2>
        <div class="space-y-4">
          <div v-for="item in totalParStatut" :key="item.label">
            <div class="flex justify-between text-sm mb-1">
              <span class="text-gray-600">{{ item.label }}</span>
              <span class="font-medium text-gray-900">{{ item.value }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
              <div
                :class="[item.color, 'h-2.5 rounded-full transition-all']"
                :style="{
                  width: `${
                    statistiques.total_incidents > 0
                      ? (item.value / statistiques.total_incidents) * 100
                      : 0
                  }%`,
                }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tableau récapitulatif -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          <div class="flex items-center">
            <ChartBarIcon class="h-5 w-5 mr-2 text-gray-400" />
            Récapitulatif de la période
          </div>
        </h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Indicateur
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Valeur
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  Nombre total d'incidents
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium"
                >
                  {{ statistiques.total_incidents }}
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  Incidents clôturés
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-green-600"
                >
                  {{ statistiques.incidents_par_statut.CLOTURE || 0 }}
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  Incidents en cours
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-yellow-600"
                >
                  {{ statistiques.incidents_par_statut.EN_COURS || 0 }}
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  Incidents ouverts (non affectés)
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-red-600"
                >
                  {{ statistiques.incidents_par_statut.OUVERT || 0 }}
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  Temps moyen de résolution
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium"
                >
                  {{ formatTemps(statistiques.temps_moyen_resolution) }}
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  Note moyenne de satisfaction
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium"
                >
                  {{ statistiques.note_moyenne.toFixed(2) }} / 5
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
