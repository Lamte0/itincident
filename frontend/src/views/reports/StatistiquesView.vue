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
      date_debut: filters.value.date_debut || "",
      date_fin: filters.value.date_fin || "",
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
  <div class="space-y-6">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Statistiques</h1>
      <p class="text-sm text-slate-500">
        Restitutions et analyses des incidents sur la période sélectionnée.
      </p>
    </div>

    <!-- Filtres de période -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm">
      <div class="flex flex-wrap items-end gap-4">
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date de début</label>
          <input
            v-model="filters.date_debut"
            type="date"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          />
        </div>
        <div class="space-y-1.5">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date de fin</label>
          <input
            v-model="filters.date_fin"
            type="date"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          />
        </div>
        <button
          @click="fetchStatistiques"
          :disabled="loading"
          class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors disabled:opacity-50 h-[38px] flex items-center justify-center shadow-sm shadow-indigo-600/10"
        >
          <span v-if="loading">Chargement...</span>
          <span v-else>Actualiser</span>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-100 rounded-2xl p-4 text-red-650 text-sm font-semibold animate-fade-in-up"
    >
      {{ error }}
    </div>

    <template v-else-if="statistiques">
      <!-- Cartes de synthèse -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total incidents -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-red-50 text-red-600 border border-red-100 rounded-xl">
            <ExclamationTriangleIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">Total Incidents</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
              {{ statistiques.total_incidents }}
            </p>
          </div>
        </div>

        <!-- Temps moyen de résolution -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-blue-50 text-blue-600 border border-blue-100 rounded-xl">
            <ClockIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">Temps moyen résol.</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
              {{ formatTemps(statistiques.temps_moyen_resolution) }}
            </p>
          </div>
        </div>

        <!-- Note moyenne -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-amber-50 text-amber-600 border border-amber-100 rounded-xl">
            <StarIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">Note moyenne</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
              {{ statistiques.note_moyenne.toFixed(1) }} <span class="text-slate-400 text-sm font-semibold">/ 5</span>
            </p>
          </div>
        </div>

        <!-- Taux de clôture -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm flex items-center gap-4">
          <div class="flex-shrink-0 p-3 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-xl">
            <CheckCircleIcon class="h-6 w-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-450 uppercase tracking-wider">Taux de clôture</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-0.5">
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

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Graphique Doughnut - Répartition par statut -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
          <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
            Répartition par statut
          </h2>
          <div class="h-64 relative">
            <Doughnut
              :key="'statut-' + chartKey"
              :data="statutChartData"
              :options="statutChartOptions"
            />
          </div>
        </div>

        <!-- Graphique Bar - Répartition par type -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
          <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
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

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Graphique Bar horizontal - Répartition par priorité -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
          <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
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
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
          <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
            <ArrowTrendingUpIcon class="h-4.5 w-4.5 text-slate-400" />
            Évolution sur la période
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
            class="flex items-center justify-center h-64 text-slate-450 text-sm font-semibold"
          >
            Aucune donnée sur cette période.
          </div>
        </div>
      </div>

      <!-- Détail par statut (barres de progression) -->
      <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100">
          Détail par statut
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
          <div v-for="item in totalParStatut" :key="item.label" class="space-y-1">
            <div class="flex justify-between text-sm">
              <span class="text-slate-550 font-semibold">{{ item.label }}</span>
              <span class="font-bold text-slate-800">{{ item.value }}</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
              <div
                :class="[item.color, 'h-2 rounded-full transition-all']"
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
      <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
          <ChartBarIcon class="h-4.5 w-4.5 text-slate-400" />
          Récapitulatif de la période
        </h2>
        <div class="overflow-x-auto border border-slate-150 rounded-2xl overflow-hidden">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/75">
              <tr>
                <th
                  class="px-6 py-3.5 text-left text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Indicateur
                </th>
                <th
                  class="px-6 py-3.5 text-right text-xs font-bold text-slate-450 uppercase tracking-wider"
                >
                  Valeur
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-semibold">
                  Nombre total d'incidents
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-slate-800"
                >
                  {{ statistiques.total_incidents }}
                </td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-semibold">
                  Incidents clôturés
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-emerald-600"
                >
                  {{ statistiques.incidents_par_statut.CLOTURE || 0 }}
                </td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-semibold">
                  Incidents en cours
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-amber-600"
                >
                  {{ statistiques.incidents_par_statut.EN_COURS || 0 }}
                </td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-semibold">
                  Incidents ouverts (non affectés)
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-650"
                >
                  {{ statistiques.incidents_par_statut.OUVERT || 0 }}
                </td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-semibold">
                  Temps moyen de résolution
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-slate-800"
                >
                  {{ formatTemps(statistiques.temps_moyen_resolution) }}
                </td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-semibold">
                  Note moyenne de satisfaction
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-slate-850"
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
