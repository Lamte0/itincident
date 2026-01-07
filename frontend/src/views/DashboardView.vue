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
} from "@heroicons/vue/24/outline";

const authStore = useAuthStore();

// Stats fictives pour le dashboard (seront remplacées par l'API)
const stats = [
  {
    name: "Incidents ouverts",
    value: "--",
    icon: ExclamationTriangleIcon,
    color: "bg-red-500",
  },
  {
    name: "En cours de traitement",
    value: "--",
    icon: ClockIcon,
    color: "bg-yellow-500",
  },
  {
    name: "Résolus ce mois",
    value: "--",
    icon: CheckCircleIcon,
    color: "bg-green-500",
  },
  {
    name: "Total interventions",
    value: "--",
    icon: WrenchScrewdriverIcon,
    color: "bg-blue-500",
  },
];
</script>

<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">
        Bonjour, {{ authStore.user?.name }} 👋
      </h1>
      <p class="mt-1 text-sm text-gray-500">
        Bienvenue sur le système de gestion des incidents informatiques de la
        DGTCP
      </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <div
        v-for="stat in stats"
        :key="stat.name"
        class="bg-white overflow-hidden shadow rounded-lg"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div :class="[stat.color, 'flex-shrink-0 rounded-md p-3']">
              <component :is="stat.icon" class="h-6 w-6 text-white" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  {{ stat.name }}
                </dt>
                <dd class="text-lg font-semibold text-gray-900">
                  {{ stat.value }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h2>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          to="/incidents/nouveau"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-10 w-10 text-primary-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">
              Signaler un incident
            </p>
            <p class="text-sm text-gray-500">Créer une nouvelle déclaration</p>
          </div>
        </RouterLink>

        <RouterLink
          to="/mes-incidents"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <ClockIcon class="h-10 w-10 text-yellow-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">Mes incidents</p>
            <p class="text-sm text-gray-500">Suivre mes déclarations</p>
          </div>
        </RouterLink>

        <RouterLink
          v-if="authStore.isChefService"
          to="/statistiques"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-10 w-10 text-green-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">Statistiques</p>
            <p class="text-sm text-gray-500">Voir les restitutions</p>
          </div>
        </RouterLink>

        <RouterLink
          v-if="authStore.isMaintenancier"
          to="/interventions"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <WrenchScrewdriverIcon class="h-10 w-10 text-blue-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">Mes interventions</p>
            <p class="text-sm text-gray-500">Incidents à traiter</p>
          </div>
        </RouterLink>

        <!-- Actions Admin -->
        <RouterLink
          v-if="authStore.isAdmin"
          to="/utilisateurs"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <UsersIcon class="h-10 w-10 text-purple-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">Utilisateurs</p>
            <p class="text-sm text-gray-500">Gérer les utilisateurs et rôles</p>
          </div>
        </RouterLink>

        <RouterLink
          v-if="authStore.isAdmin"
          to="/incidents"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <ClipboardDocumentListIcon class="h-10 w-10 text-indigo-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">Tous les incidents</p>
            <p class="text-sm text-gray-500">Vue complète des incidents</p>
          </div>
        </RouterLink>

        <RouterLink
          v-if="authStore.isAdmin"
          to="/affectations"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
        >
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-10 w-10 text-teal-600" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="absolute inset-0" aria-hidden="true"></span>
            <p class="text-sm font-medium text-gray-900">Affectations</p>
            <p class="text-sm text-gray-500">Assigner aux techniciens</p>
          </div>
        </RouterLink>
      </div>
    </div>

    <!-- Informations -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-blue-400"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-blue-700">
            <strong>Workflow des incidents :</strong> Ouvert → Affecté → En
            cours → Résolu → En attente de validation → Clôturé
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
