import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useIncidentStore } from "@/stores/incidents";
import { incidentService } from "@/services/api";

// Mock du service API
vi.mock("@/services/api", () => ({
  incidentService: {
    getAll: vi.fn(),
    getMine: vi.fn(),
    getOne: vi.fn(),
    create: vi.fn(),
    affecter: vi.fn(),
    prendreEnCharge: vi.fn(),
    resoudre: vi.fn(),
    valider: vi.fn(),
    rejeter: vi.fn(),
  },
}));

const mockIncident = {
  id: 1,
  reference: "INC-2024-0001",
  titre: "Test Incident",
  description: "Description du test",
  type: "RESEAU",
  priorite: "HAUTE",
  statut: "OUVERT",
  auteur_id: 1,
  lieu: "Bureau 101",
  equipement: "PC-0001",
  created_at: "2024-01-01T10:00:00",
  updated_at: "2024-01-01T10:00:00",
  auteur: {
    id: 1,
    name: "Test User",
    email: "test@example.com",
  },
  images: [],
  affectations: [],
  historique_statuts: [],
};

const mockPaginatedResponse = {
  data: [mockIncident],
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 1,
};

describe("Incident Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  describe("État initial", () => {
    it("doit avoir une liste d'incidents vide par défaut", () => {
      const store = useIncidentStore();
      expect(store.incidents).toEqual([]);
    });

    it("doit avoir currentIncident à null par défaut", () => {
      const store = useIncidentStore();
      expect(store.currentIncident).toBeNull();
    });

    it("doit avoir loading à false par défaut", () => {
      const store = useIncidentStore();
      expect(store.loading).toBe(false);
    });

    it("doit avoir des filtres vides par défaut", () => {
      const store = useIncidentStore();
      expect(store.filters).toEqual({
        statut: "",
        type: "",
        priorite: "",
        date_debut: "",
        date_fin: "",
      });
    });
  });

  describe("fetchIncidents", () => {
    it("doit charger les incidents avec succès", async () => {
      vi.mocked(incidentService.getAll).mockResolvedValue({
        data: mockPaginatedResponse,
      });

      const store = useIncidentStore();
      await store.fetchIncidents();

      expect(store.incidents).toHaveLength(1);
      expect(store.incidents[0]).toEqual(mockIncident);
      expect(store.pagination.total).toBe(1);
      expect(store.loading).toBe(false);
    });

    it("doit gérer les erreurs de chargement", async () => {
      const mockError = {
        response: {
          data: {
            message: "Erreur de chargement",
          },
        },
      };
      vi.mocked(incidentService.getAll).mockRejectedValue(mockError);

      const store = useIncidentStore();
      await store.fetchIncidents();

      expect(store.incidents).toEqual([]);
      expect(store.error).toBe("Erreur de chargement");
    });

    it("doit mettre loading à true pendant le chargement", async () => {
      vi.mocked(incidentService.getAll).mockImplementation(
        () =>
          new Promise((resolve) =>
            setTimeout(() => resolve({ data: mockPaginatedResponse }), 100)
          )
      );

      const store = useIncidentStore();
      const fetchPromise = store.fetchIncidents();

      expect(store.loading).toBe(true);
      await fetchPromise;
      expect(store.loading).toBe(false);
    });

    it("doit passer les filtres à l'API", async () => {
      vi.mocked(incidentService.getAll).mockResolvedValue({
        data: mockPaginatedResponse,
      });

      const store = useIncidentStore();
      store.filters.statut = "OUVERT";
      store.filters.type = "RESEAU";

      await store.fetchIncidents(2);

      expect(incidentService.getAll).toHaveBeenCalledWith({
        page: 2,
        statut: "OUVERT",
        type: "RESEAU",
        priorite: "",
        date_debut: "",
        date_fin: "",
      });
    });
  });

  describe("fetchMyIncidents", () => {
    it("doit charger les incidents de l'utilisateur", async () => {
      vi.mocked(incidentService.getMine).mockResolvedValue({
        data: mockPaginatedResponse,
      });

      const store = useIncidentStore();
      await store.fetchMyIncidents();

      expect(store.incidents).toHaveLength(1);
      expect(incidentService.getMine).toHaveBeenCalled();
    });
  });

  describe("fetchIncident", () => {
    it("doit charger un incident par ID", async () => {
      vi.mocked(incidentService.getOne).mockResolvedValue({
        data: mockIncident,
      });

      const store = useIncidentStore();
      const result = await store.fetchIncident(1);

      expect(result).toEqual(mockIncident);
      expect(store.currentIncident).toEqual(mockIncident);
    });

    it("doit retourner null si l'incident n'existe pas", async () => {
      const mockError = {
        response: {
          data: {
            message: "Incident non trouvé",
          },
        },
      };
      vi.mocked(incidentService.getOne).mockRejectedValue(mockError);

      const store = useIncidentStore();
      const result = await store.fetchIncident(999);

      expect(result).toBeNull();
      expect(store.error).toBe("Incident non trouvé");
    });
  });

  describe("createIncident", () => {
    it("doit créer un incident avec succès", async () => {
      const newIncident = { ...mockIncident, id: 2 };
      vi.mocked(incidentService.create).mockResolvedValue({
        data: newIncident,
      });

      const store = useIncidentStore();
      const formData = new FormData();
      formData.append("titre", "Nouveau incident");

      const result = await store.createIncident(formData);

      expect(result).toEqual(newIncident);
      expect(incidentService.create).toHaveBeenCalledWith(formData);
    });

    it("doit lancer une erreur si la création échoue", async () => {
      const mockError = {
        response: {
          data: {
            message: "Validation échouée",
          },
        },
      };
      vi.mocked(incidentService.create).mockRejectedValue(mockError);

      const store = useIncidentStore();
      const formData = new FormData();

      await expect(store.createIncident(formData)).rejects.toEqual(mockError);
      expect(store.error).toBe("Validation échouée");
    });
  });

  describe("affecterIncident", () => {
    it("doit affecter un incident à un maintenancier", async () => {
      const affectedIncident = { ...mockIncident, statut: "AFFECTE" };
      vi.mocked(incidentService.affecter).mockResolvedValue({
        data: affectedIncident,
      });

      const store = useIncidentStore();
      store.currentIncident = mockIncident as any;

      const result = await store.affecterIncident(1, 2, "Instructions");

      expect(result.statut).toBe("AFFECTE");
      expect(store.currentIncident?.statut).toBe("AFFECTE");
      expect(incidentService.affecter).toHaveBeenCalledWith(1, {
        maintenancier_id: 2,
        instructions: "Instructions",
      });
    });
  });

  describe("prendreEnCharge", () => {
    it("doit prendre en charge un incident", async () => {
      const enCoursIncident = { ...mockIncident, statut: "EN_COURS" };
      vi.mocked(incidentService.prendreEnCharge).mockResolvedValue({
        data: enCoursIncident,
      });

      const store = useIncidentStore();
      store.currentIncident = { ...mockIncident, statut: "AFFECTE" } as any;

      const result = await store.prendreEnCharge(1);

      expect(result.statut).toBe("EN_COURS");
      expect(store.currentIncident?.statut).toBe("EN_COURS");
    });
  });

  describe("resoudreIncident", () => {
    it("doit résoudre un incident", async () => {
      const resoluIncident = {
        ...mockIncident,
        statut: "RESOLU",
        date_resolution: "2024-01-02T10:00:00",
      };
      vi.mocked(incidentService.resoudre).mockResolvedValue({
        data: resoluIncident,
      });

      const store = useIncidentStore();
      store.currentIncident = { ...mockIncident, statut: "EN_COURS" } as any;

      const result = await store.resoudreIncident(1, "Rapport d'intervention");

      expect(result.statut).toBe("RESOLU");
      expect(incidentService.resoudre).toHaveBeenCalledWith(1, {
        rapport_intervention: "Rapport d'intervention",
      });
    });
  });

  describe("validerIncident", () => {
    it("doit valider un incident avec note", async () => {
      const clotureIncident = {
        ...mockIncident,
        statut: "CLOTURE",
        note: 5,
        date_cloture: "2024-01-03T10:00:00",
      };
      vi.mocked(incidentService.valider).mockResolvedValue({
        data: clotureIncident,
      });

      const store = useIncidentStore();
      store.currentIncident = { ...mockIncident, statut: "RESOLU" } as any;

      const result = await store.validerIncident(1, 5, "Excellent travail");

      expect(result.statut).toBe("CLOTURE");
      expect(result.note).toBe(5);
      expect(incidentService.valider).toHaveBeenCalledWith(1, {
        note: 5,
        commentaire_validation: "Excellent travail",
      });
    });
  });
});
