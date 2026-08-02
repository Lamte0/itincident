import { defineStore } from "pinia";
import { ref } from "vue";
import { incidentService } from "@/services/api";
import type { Incident, PaginatedResponse } from "@/types";

export const useIncidentStore = defineStore("incidents", () => {
  // State distincts pour éviter les conflits d'écrasement entre vues
  const incidents = ref<Incident[]>([]);
  const myIncidents = ref<Incident[]>([]);
  const myInterventions = ref<Incident[]>([]);
  const currentIncident = ref<Incident | null>(null);

  const loading = ref(false);
  const incidentsLoading = ref(false);
  const myIncidentsLoading = ref(false);
  const myInterventionsLoading = ref(false);
  const error = ref<string | null>(null);

  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
  });

  const myIncidentsPagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
  });

  // Filtres pour la liste globale
  const filters = ref({
    statut: "",
    type: "",
    priorite: "",
    date_debut: "",
    date_fin: "",
  });

  // Actions
  async function fetchIncidents(page = 1, perPage = 50, isBackground = false) {
    if (!isBackground && incidents.value.length === 0) {
      incidentsLoading.value = true;
    }
    error.value = null;

    const activeFilters: Record<string, any> = {};
    if (filters.value.statut) activeFilters.statut = filters.value.statut;
    if (filters.value.type) activeFilters.type = filters.value.type;
    if (filters.value.priorite) activeFilters.priorite = filters.value.priorite;
    if (filters.value.date_debut) activeFilters.date_debut = filters.value.date_debut;
    if (filters.value.date_fin) activeFilters.date_fin = filters.value.date_fin;

    try {
      const response = await incidentService.getAll({
        page,
        per_page: perPage,
        ...activeFilters,
      });
      const data: PaginatedResponse<Incident> = response.data;
      incidents.value = data.data || [];
      pagination.value = {
        currentPage: data.current_page || 1,
        lastPage: data.last_page || 1,
        perPage: data.per_page || perPage,
        total: data.total || 0,
      };
    } catch (err: any) {
      console.error("Error fetching incidents:", err);
      error.value = err.response?.data?.message || "Erreur lors du chargement";
    } finally {
      incidentsLoading.value = false;
    }
  }

  async function fetchMyIncidents(page = 1, isBackground = false) {
    if (!isBackground && myIncidents.value.length === 0) {
      myIncidentsLoading.value = true;
    }
    error.value = null;

    try {
      const response = await incidentService.getMine({
        page,
        statut: filters.value.statut,
      });
      const data: PaginatedResponse<Incident> = response.data;
      myIncidents.value = data.data || [];
      myIncidentsPagination.value = {
        currentPage: data.current_page || 1,
        lastPage: data.last_page || 1,
        perPage: data.per_page || 15,
        total: data.total || 0,
      };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Erreur lors du chargement";
    } finally {
      myIncidentsLoading.value = false;
    }
  }

  async function fetchMyInterventions(isBackground = false) {
    if (!isBackground && myInterventions.value.length === 0) {
      myInterventionsLoading.value = true;
    }
    error.value = null;

    try {
      const response = await incidentService.getMyInterventions();
      myInterventions.value = response.data || [];
    } catch (err: any) {
      error.value = err.response?.data?.message || "Erreur lors du chargement des interventions";
    } finally {
      myInterventionsLoading.value = false;
    }
  }

  async function fetchIncident(id: number) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.getOne(id);
      currentIncident.value = response.data;
      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || "Incident non trouvé";
      return null;
    } finally {
      loading.value = false;
    }
  }

  async function createIncident(formData: FormData) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.create(formData);
      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || "Erreur lors de la création";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function affecterIncident(
    id: number,
    maintenancier_id: number,
    instructions?: string
  ) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.affecter(id, {
        maintenancier_id,
        instructions,
      });
      if (currentIncident.value?.id === id) {
        currentIncident.value = response.data;
      }
      return response.data;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || "Erreur lors de l'affectation";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function prendreEnCharge(id: number) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.prendreEnCharge(id);
      if (currentIncident.value?.id === id) {
        currentIncident.value = response.data;
      }
      return response.data;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || "Erreur lors de la prise en charge";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function resoudreIncident(id: number, rapport_intervention: string) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.resoudre(id, {
        rapport_intervention,
      });
      if (currentIncident.value?.id === id) {
        currentIncident.value = response.data;
      }
      return response.data;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || "Erreur lors de la résolution";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function validerIncident(
    id: number,
    note: number,
    commentaire_validation?: string
  ) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.valider(id, {
        note,
        commentaire_validation,
      });
      if (currentIncident.value?.id === id) {
        currentIncident.value = response.data;
      }
      return response.data;
    } catch (err: any) {
      error.value =
        err.response?.data?.message || "Erreur lors de la validation";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function rejeterResolution(id: number, motif: string) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.rejeter(id, { motif });
      if (currentIncident.value?.id === id) {
        currentIncident.value = response.data;
      }
      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || "Erreur lors du rejet";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function setFilters(newFilters: Partial<typeof filters.value>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function clearFilters() {
    filters.value = {
      statut: "",
      type: "",
      priorite: "",
      date_debut: "",
      date_fin: "",
    };
  }

  return {
    // State
    incidents,
    myIncidents,
    myInterventions,
    currentIncident,
    loading,
    incidentsLoading,
    myIncidentsLoading,
    myInterventionsLoading,
    error,
    pagination,
    myIncidentsPagination,
    filters,
    // Actions
    fetchIncidents,
    fetchMyIncidents,
    fetchMyInterventions,
    fetchIncident,
    createIncident,
    affecterIncident,
    prendreEnCharge,
    resoudreIncident,
    validerIncident,
    rejeterResolution,
    setFilters,
    clearFilters,
  };
});
