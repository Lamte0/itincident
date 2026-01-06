import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Routes publiques
    {
      path: "/login",
      name: "login",
      component: () => import("@/views/auth/LoginView.vue"),
      meta: { guest: true },
    },
    {
      path: "/register",
      name: "register",
      component: () => import("@/views/auth/RegisterView.vue"),
      meta: { guest: true },
    },

    // Routes protégées - Layout principal
    {
      path: "/",
      component: () => import("@/layouts/MainLayout.vue"),
      meta: { requiresAuth: true },
      children: [
        {
          path: "",
          name: "dashboard",
          component: () => import("@/views/DashboardView.vue"),
        },

        // Incidents - Utilisateur
        {
          path: "incidents",
          name: "incidents",
          component: () => import("@/views/incidents/IncidentListView.vue"),
        },
        {
          path: "incidents/nouveau",
          name: "incident-create",
          component: () => import("@/views/incidents/IncidentCreateView.vue"),
        },
        {
          path: "incidents/:id",
          name: "incident-detail",
          component: () => import("@/views/incidents/IncidentDetailView.vue"),
          props: true,
        },
        {
          path: "mes-incidents",
          name: "mes-incidents",
          component: () => import("@/views/incidents/MesIncidentsView.vue"),
        },

        // Affectations - Chef Service
        {
          path: "affectations",
          name: "affectations",
          component: () =>
            import("@/views/affectations/AffectationListView.vue"),
          meta: { roles: ["CHEF_SERVICE", "ADMIN"] },
        },

        // Interventions - Maintenancier
        {
          path: "interventions",
          name: "interventions",
          component: () =>
            import("@/views/interventions/InterventionListView.vue"),
          meta: { roles: ["MAINTENANCIER"] },
        },

        // Rapports et statistiques - Chef Service
        {
          path: "rapports",
          name: "rapports",
          component: () => import("@/views/reports/ReportsView.vue"),
          meta: { roles: ["CHEF_SERVICE", "ADMIN"] },
        },
        {
          path: "statistiques",
          name: "statistiques",
          component: () => import("@/views/reports/StatistiquesView.vue"),
          meta: { roles: ["CHEF_SERVICE", "ADMIN"] },
        },

        // Administration - Admin
        {
          path: "utilisateurs",
          name: "utilisateurs",
          component: () => import("@/views/admin/UsersListView.vue"),
          meta: { roles: ["ADMIN"] },
        },
      ],
    },

    // 404
    {
      path: "/:pathMatch(.*)*",
      name: "not-found",
      component: () => import("@/views/NotFoundView.vue"),
    },
  ],
});

// Guards de navigation
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  // Route nécessitant une authentification
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: "login", query: { redirect: to.fullPath } });
  }

  // Route réservée aux visiteurs (login, register)
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: "dashboard" });
  }

  // Vérification des rôles
  if (to.meta.roles && Array.isArray(to.meta.roles)) {
    const hasRole = authStore.hasRole(to.meta.roles as string[]);
    if (!hasRole) {
      return next({ name: "dashboard" });
    }
  }

  next();
});

export default router;
