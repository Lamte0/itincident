<script setup lang="ts">
import { ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import {
  HomeIcon,
  ExclamationTriangleIcon,
  ClipboardDocumentListIcon,
  WrenchScrewdriverIcon,
  ChartBarIcon,
  DocumentTextIcon,
  UsersIcon,
  Bars3Icon,
  XMarkIcon,
  ArrowRightOnRectangleIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";

const authStore = useAuthStore();
const router = useRouter();
const sidebarOpen = ref(false);

const navigation = [
  { name: "Tableau de bord", href: "/", icon: HomeIcon, roles: ["all"] },
  {
    name: "Mes Incidents",
    href: "/mes-incidents",
    icon: ExclamationTriangleIcon,
    roles: ["UTILISATEUR", "MAINTENANCIER", "CHEF_SERVICE", "ADMIN"],
  },
  {
    name: "Nouveau Incident",
    href: "/incidents/nouveau",
    icon: ClipboardDocumentListIcon,
    roles: ["UTILISATEUR", "MAINTENANCIER", "CHEF_SERVICE", "ADMIN"],
  },
  {
    name: "Tous les Incidents",
    href: "/incidents",
    icon: ClipboardDocumentListIcon,
    roles: ["CHEF_SERVICE", "ADMIN"],
  },
  {
    name: "Affectations",
    href: "/affectations",
    icon: ClipboardDocumentListIcon,
    roles: ["CHEF_SERVICE", "ADMIN"],
  },
  {
    name: "Mes Interventions",
    href: "/interventions",
    icon: WrenchScrewdriverIcon,
    roles: ["MAINTENANCIER"],
  },
  {
    name: "Statistiques",
    href: "/statistiques",
    icon: ChartBarIcon,
    roles: ["CHEF_SERVICE", "ADMIN"],
  },
  {
    name: "Rapports",
    href: "/rapports",
    icon: DocumentTextIcon,
    roles: ["CHEF_SERVICE", "ADMIN"],
  },
  {
    name: "Utilisateurs",
    href: "/utilisateurs",
    icon: UsersIcon,
    roles: ["ADMIN"],
  },
];

const filteredNavigation = navigation.filter((item) => {
  if (item.roles.includes("all")) return true;
  return authStore.hasRole(item.roles);
});

async function logout() {
  await authStore.logout();
  router.push("/login");
}

function getRoleBadgeColor(role: string) {
  switch (role) {
    case "ADMIN":
      return "bg-red-100 text-red-800";
    case "CHEF_SERVICE":
      return "bg-purple-100 text-purple-800";
    case "MAINTENANCIER":
      return "bg-blue-100 text-blue-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sidebar Mobile -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 lg:hidden"
      @click="sidebarOpen = false"
    >
      <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
    </div>

    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-primary-800 transform transition-transform duration-300 ease-in-out lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <!-- Logo -->
      <div class="flex h-16 items-center justify-between px-4 bg-primary-900">
        <h1 class="text-white text-lg font-bold">DGTCP - Incidents</h1>
        <button class="lg:hidden text-white" @click="sidebarOpen = false">
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="mt-5 px-2 space-y-1">
        <RouterLink
          v-for="item in filteredNavigation"
          :key="item.name"
          :to="item.href"
          class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-primary-100 hover:bg-primary-700 hover:text-white"
          active-class="bg-primary-900 text-white"
          @click="sidebarOpen = false"
        >
          <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <!-- User info -->
      <div class="absolute bottom-0 left-0 right-0 p-4 bg-primary-900">
        <div class="flex items-center">
          <UserCircleIcon class="h-10 w-10 text-primary-300" />
          <div class="ml-3">
            <p class="text-sm font-medium text-white">
              {{ authStore.user?.name }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                getRoleBadgeColor(authStore.user?.role || ''),
              ]"
            >
              {{ authStore.user?.role }}
            </span>
          </div>
        </div>
        <button
          @click="logout"
          class="mt-3 w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-primary-100 bg-primary-700 rounded-md hover:bg-primary-600"
        >
          <ArrowRightOnRectangleIcon class="h-5 w-5 mr-2" />
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="lg:pl-64">
      <!-- Top bar -->
      <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="flex h-16 items-center justify-between px-4">
          <button
            class="lg:hidden text-gray-500 hover:text-gray-700"
            @click="sidebarOpen = true"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">
              Direction Générale du Trésor et de la Comptabilité Publique
            </span>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
