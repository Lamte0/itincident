<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";
import {
  ExclamationTriangleIcon,
  ClockIcon,
  CheckCircleIcon,
  WrenchScrewdriverIcon,
  UsersIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  ArrowRightIcon,
} from "@heroicons/vue/24/outline";

const authStore = useAuthStore();

// Stats fictives pour le dashboard (seront remplacées par l'API)
const stats = [
  {
    name: "Incidents ouverts",
    value: "--",
    icon: ExclamationTriangleIcon,
    color: "from-red-500 to-rose-600 shadow-red-500/20",
    bgColor: "bg-red-50",
    textColor: "text-red-600",
  },
  {
    name: "En cours de traitement",
    value: "--",
    icon: ClockIcon,
    color: "from-amber-400 to-orange-500 shadow-orange-500/20",
    bgColor: "bg-amber-50",
    textColor: "text-amber-600",
  },
  {
    name: "Résolus ce mois",
    value: "--",
    icon: CheckCircleIcon,
    color: "from-emerald-400 to-teal-500 shadow-emerald-500/20",
    bgColor: "bg-emerald-50",
    textColor: "text-emerald-600",
  },
  {
    name: "Total interventions",
    value: "--",
    icon: WrenchScrewdriverIcon,
    color: "from-blue-500 to-indigo-600 shadow-blue-500/20",
    bgColor: "bg-blue-50",
    textColor: "text-blue-600",
  },
];
</script>

<template>
  <div class="space-y-8">
    <!-- Welcome Banner Card -->
    <div class="relative bg-gradient-to-r from-indigo-900 via-indigo-800 to-violet-800 rounded-3xl p-8 md:p-10 text-white overflow-hidden shadow-xl shadow-indigo-900/10">
      <!-- Decorative background shapes -->
      <div class="absolute -top-24 -left-24 w-80 h-80 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
      
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-3">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-semibold tracking-wider uppercase text-indigo-200 border border-white/10">
            <span class="h-1.5 w-1.5 rounded-full bg-green-400 animate-pulse-soft"></span>
            Session Active
          </span>
          <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">
            Bonjour, {{ authStore.user?.name }} 👋
          </h1>
          <p class="text-indigo-200 text-sm md:text-base max-w-2xl leading-relaxed">
            Bienvenue sur le système de gestion des incidents de la Direction Générale du Trésor et de la Comptabilité Publique. Suivez et gérez vos incidents informatiques en temps réel.
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
      <h2 class="text-lg font-bold text-slate-800 tracking-tight">Statistiques générales</h2>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="stat in stats"
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

        <!-- Statistiques (Chef Service) -->
        <RouterLink
          v-if="authStore.isChefService"
          to="/statistiques"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-emerald-50 text-emerald-600 rounded-xl p-3 group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300">
            <ChartBarIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Statistiques
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
              Utilisateurs
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Gérez les comptes utilisateurs, affectez des rôles et modifiez les profils.
            </p>
          </div>
        </RouterLink>

        <!-- Tous les incidents (Admin / Chef) -->
        <RouterLink
          v-if="authStore.isAdmin || authStore.isChefService"
          to="/incidents"
          class="group relative bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-md hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 flex items-start gap-4 card-glow"
        >
          <div class="flex-shrink-0 bg-teal-50 text-teal-600 rounded-xl p-3 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
            <ClipboardDocumentListIcon class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <h3 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">
              Tous les incidents
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
              Accédez à la base globale de l'ensemble des pannes déclarées.
            </p>
          </div>
        </RouterLink>
      </div>
    </div>

    <!-- Workflow Informative Section -->
    <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-6">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0 bg-indigo-100 text-indigo-600 rounded-xl p-2 mt-0.5">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="space-y-2">
          <h3 class="text-sm font-bold text-slate-800">Processus de traitement des incidents</h3>
          <p class="text-xs text-slate-600 leading-relaxed max-w-4xl">
            Pour assurer un traitement efficace, chaque incident suit un parcours structuré au sein de la DGTCP.
          </p>
          <div class="flex flex-wrap items-center gap-2 pt-2 text-xs font-semibold">
            <span class="px-2.5 py-1 bg-red-100 text-red-800 rounded-lg">Ouvert</span>
            <span class="text-slate-400">→</span>
            <span class="px-2.5 py-1 bg-blue-100 text-blue-800 rounded-lg">Affecté</span>
            <span class="text-slate-400">→</span>
            <span class="px-2.5 py-1 bg-yellow-100 text-yellow-800 rounded-lg">En cours</span>
            <span class="text-slate-400">→</span>
            <span class="px-2.5 py-1 bg-green-100 text-green-800 rounded-lg">Résolu</span>
            <span class="text-slate-400">→</span>
            <span class="px-2.5 py-1 bg-purple-100 text-purple-800 rounded-lg">Validation</span>
            <span class="text-slate-400">→</span>
            <span class="px-2.5 py-1 bg-slate-200 text-slate-800 rounded-lg">Clôturé</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

