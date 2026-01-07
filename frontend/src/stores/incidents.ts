import { defineStore } from "pinia";
import { ref } from "vue";
import { incidentService } from "@/services/api";
import type { Incident, PaginatedResponse } from "@/types";

export const useIncidentStore = defineStore("incidents", () => {
  const incidents = ref<Incident[]>([]);
  const currentIncident = ref<Incident | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
  });

  // Filtres
  const filters = ref({
    statut: "",
    type: "",
    priorite: "",
    date_debut: "",
    date_fin: "",
  });

  // Actions
  async function fetchIncidents(page = 1, perPage = 15) {
    loading.value = true;
    error.value = null;

    try {
      console.log("Fetching incidents with params:", {
        page,
        per_page: perPage,
        ...filters.value,
      });
      const response = await incidentService.getAll({
        page,
        per_page: perPage,
        ...filters.value,
      });
      console.log("API Full Response:", response);
      console.log("API Response data:", response.data);
      console.log("API Response data.data:", response.data.data);
      console.log("API Response total:", response.data.total);
      const data: PaginatedResponse<Incident> = response.data;
      incidents.value = data.data;
      console.log("Incidents loaded:", incidents.value);
      pagination.value = {
        currentPage: data.current_page,
        lastPage: data.last_page,
        perPage: data.per_page,
        total: data.total,
      };
    } catch (err: any) {
      console.error("Error fetching incidents:", err);
      error.value = err.response?.data?.message || "Erreur lors du chargement";
    } finally {
      loading.value = false;
    }
  }

  async function fetchMyIncidents(page = 1) {
    loading.value = true;
    error.value = null;

    try {
      const response = await incidentService.getMine({
        page,
        statut: filters.value.statut,
      });
      const data: PaginatedResponse<Incident> = response.data;
      incidents.value = data.data;
      pagination.value = {
        currentPage: data.current_page,
        lastPage: data.last_page,
        perPage: data.per_page,
        total: data.total,
      };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Erreur lors du chargement";
    } finally {
      loading.value = false;
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
    currentIncident,
    loading,
    error,
    pagination,
    filters,
    // Actions
    fetchIncidents,
    fetchMyIncidents,
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
