import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Intercepteur pour ajouter le token d'authentification
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs d'authentification
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
      window.location.href = "/login";
    }
    return Promise.reject(error);
  }
);

export default api;

// Services API
export const authService = {
  login: (credentials: { email: string; password: string }) =>
    api.post("/login", credentials),
  register: (data: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    service?: string;
    telephone?: string;
    matricule?: string;
  }) => api.post("/register", data),
  logout: () => api.post("/logout"),
  me: () => api.get("/me"),
};

export const incidentService = {
  // Liste des incidents
  getAll: (params?: {
    page?: number;
    statut?: string;
    type?: string;
    priorite?: string;
    date_debut?: string;
    date_fin?: string;
  }) => api.get("/incidents", { params }),

  // Mes incidents (pour l'utilisateur connecté)
  getMine: (params?: { page?: number; statut?: string }) =>
    api.get("/incidents/mes-incidents", { params }),

  // Détail d'un incident
  getOne: (id: number) => api.get(`/incidents/${id}`),

  // Créer un incident
  create: (data: FormData) =>
    api.post("/incidents", data, {
      headers: { "Content-Type": "multipart/form-data" },
    }),

  // Mettre à jour un incident
  update: (id: number, data: object) => api.put(`/incidents/${id}`, data),

  // Supprimer un incident
  delete: (id: number) => api.delete(`/incidents/${id}`),

  // Affecter un incident à un maintenancier
  affecter: (
    id: number,
    data: { maintenancier_id: number; instructions?: string }
  ) => api.post(`/incidents/${id}/affecter`, data),

  // Prendre en charge un incident (maintenancier)
  prendreEnCharge: (id: number) =>
    api.post(`/incidents/${id}/prendre-en-charge`),

  // Résoudre un incident (maintenancier)
  resoudre: (id: number, data: { rapport_intervention: string }) =>
    api.post(`/incidents/${id}/resoudre`, data),

  // Valider/Clôturer un incident (auteur)
  valider: (
    id: number,
    data: { note: number; commentaire_validation?: string }
  ) => api.post(`/incidents/${id}/valider`, data),

  // Rejeter la résolution (auteur)
  rejeter: (id: number, data: { motif: string }) =>
    api.post(`/incidents/${id}/rejeter`, data),
};

export const userService = {
  // Liste des utilisateurs
  getAll: (params?: { role?: string }) => api.get("/users", { params }),

  // Liste des maintenanciers
  getMaintenanciers: () =>
    api.get("/users", { params: { role: "MAINTENANCIER" } }),

  // Détail d'un utilisateur
  getOne: (id: number) => api.get(`/users/${id}`),

  // Créer un utilisateur (admin)
  create: (data: object) => api.post("/users", data),

  // Mettre à jour un utilisateur
  update: (id: number, data: object) => api.put(`/users/${id}`, data),

  // Supprimer un utilisateur
  delete: (id: number) => api.delete(`/users/${id}`),
};

export const reportService = {
  // Fiche d'intervention PDF
  getFicheIntervention: (incidentId: number) =>
    api.get(`/reports/fiche-intervention/${incidentId}`, {
      responseType: "blob",
    }),

  // Statistiques / Restitutions
  getStatistiques: (params: { date_debut: string; date_fin: string }) =>
    api.get("/reports/statistiques", { params }),

  // Export des incidents
  exportIncidents: (params: {
    date_debut: string;
    date_fin: string;
    format: "pdf" | "excel";
  }) => api.get("/reports/export", { params, responseType: "blob" }),
};
