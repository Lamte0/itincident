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
      return "bg-red-500/10 text-red-400 border border-red-500/20";
    case "CHEF_SERVICE":
      return "bg-purple-500/10 text-purple-400 border border-purple-500/20";
    case "MAINTENANCIER":
      return "bg-blue-500/10 text-blue-400 border border-blue-500/20";
    default:
      return "bg-slate-500/10 text-slate-400 border border-slate-500/20";
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans">
    <!-- Sidebar Mobile -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 lg:hidden"
      @click="sidebarOpen = false"
    >
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
    </div>

    <!-- Sidebar Layout -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800/50 transform transition-transform duration-300 ease-in-out lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <!-- Logo -->
      <div class="flex h-20 items-center justify-between px-6 border-b border-slate-800/50 bg-slate-950/40">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <span class="text-white font-bold text-lg tracking-wide bg-gradient-to-r from-white to-slate-200 bg-clip-text text-transparent">IT Incident</span>
        </div>
        <button class="lg:hidden text-slate-400 hover:text-white" @click="sidebarOpen = false">
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="mt-6 px-3 space-y-1.5 overflow-y-auto max-h-[calc(100vh-12rem)] pb-8">
        <RouterLink
          v-for="item in filteredNavigation"
          :key="item.name"
          :to="item.href"
          class="group flex items-center px-4 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:bg-slate-800/60 hover:text-white transition-all duration-200"
          active-class="bg-gradient-to-r from-indigo-600/90 to-violet-600/90 text-white shadow-lg shadow-indigo-600/25 font-semibold"
          @click="sidebarOpen = false"
        >
          <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover:scale-105" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <!-- User info -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-800/60 bg-slate-950/50 backdrop-blur-md">
        <div class="flex items-center">
          <div class="relative flex-shrink-0">
            <div class="h-10 w-10 bg-gradient-to-tr from-indigo-500 to-violet-500 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md">
              {{ authStore.user?.name?.charAt(0).toUpperCase() }}
            </div>
            <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 border-2 border-slate-900"></div>
          </div>
          <div class="ml-3 min-w-0">
            <p class="text-sm font-semibold text-white truncate">
              {{ authStore.user?.name }}
            </p>
            <span
              :class="[
                'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase mt-0.5 shadow-sm',
                getRoleBadgeColor(authStore.user?.role || ''),
              ]"
            >
              {{ authStore.user?.role }}
            </span>
          </div>
        </div>
        <button
          @click="logout"
          class="mt-4 w-full flex items-center justify-center px-4 py-2.5 text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white border border-slate-700/50 rounded-xl transition-all duration-200 shadow-sm"
        >
          <ArrowRightOnRectangleIcon class="h-4 w-4 mr-2" />
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="lg:pl-64 flex flex-col min-h-screen">
      <!-- Top bar -->
      <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30">
        <div class="flex h-20 items-center justify-between px-8">
          <button
            class="lg:hidden text-slate-600 hover:text-slate-900 mr-4"
            @click="sidebarOpen = true"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
          
          <div class="flex items-center space-x-4">
            <span class="text-sm font-semibold text-slate-700 flex items-center gap-2">
              <span class="h-2 w-2 rounded-full bg-indigo-600 animate-pulse-soft"></span>
              Direction Générale du Trésor et de la Comptabilité Publique
            </span>
          </div>

          <div class="flex items-center gap-4">
            <div class="hidden sm:flex items-center gap-2 px-3.5 py-1.5 bg-slate-50 rounded-full border border-slate-200/50">
              <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
              <span class="text-xs font-medium text-slate-600">Système opérationnel</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-grow p-8 max-w-7xl w-full mx-auto animate-fade-in-up">
        <RouterView />
      </main>
    </div>
  </div>
</template>

