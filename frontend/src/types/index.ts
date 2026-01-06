// Types pour l'application de gestion des incidents

export type UserRole =
  | "UTILISATEUR"
  | "MAINTENANCIER"
  | "CHEF_SERVICE"
  | "ADMIN";

export type IncidentType = "RESEAU" | "LOGICIEL" | "HARDWARE";

export type IncidentPriorite = "BASSE" | "MOYENNE" | "HAUTE" | "CRITIQUE";

export type IncidentStatut =
  | "OUVERT"
  | "AFFECTE"
  | "EN_COURS"
  | "RESOLU"
  | "EN_ATTENTE_VALIDATION"
  | "CLOTURE";

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  service: string | null;
  telephone: string | null;
  matricule: string | null;
  is_active: boolean;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface IncidentImage {
  id: number;
  incident_id: number;
  nom_fichier: string;
  chemin: string;
  type_mime: string | null;
  taille: number | null;
  url: string;
  created_at: string;
}

export interface IncidentAssignment {
  id: number;
  incident_id: number;
  maintenancier_id: number;
  assigne_par_id: number;
  instructions: string | null;
  date_affectation: string;
  date_prise_en_charge: string | null;
  date_resolution: string | null;
  rapport_intervention: string | null;
  is_active: boolean;
  maintenancier: User;
  assigne_par: User;
  created_at: string;
}

export interface IncidentStatusHistory {
  id: number;
  incident_id: number;
  ancien_statut: string | null;
  nouveau_statut: string;
  modifie_par_id: number;
  commentaire: string | null;
  modifie_par: User;
  created_at: string;
}

export interface Incident {
  id: number;
  reference: string;
  titre: string;
  description: string;
  type: IncidentType;
  priorite: IncidentPriorite;
  statut: IncidentStatut;
  auteur_id: number;
  lieu: string | null;
  equipement: string | null;
  date_resolution: string | null;
  date_cloture: string | null;
  commentaire_resolution: string | null;
  note: number | null;
  commentaire_validation: string | null;
  created_at: string;
  updated_at: string;
  // Relations
  auteur: User;
  images: IncidentImage[];
  affectation_active: IncidentAssignment | null;
  affectations: IncidentAssignment[];
  historique_statuts: IncidentStatusHistory[];
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

export interface Statistiques {
  total_incidents: number;
  incidents_par_statut: Record<IncidentStatut, number>;
  incidents_par_type: Record<IncidentType, number>;
  incidents_par_priorite: Record<IncidentPriorite, number>;
  temps_moyen_resolution: number; // en heures
  note_moyenne: number;
  incidents_par_jour: Array<{
    date: string;
    count: number;
  }>;
}

// Form types
export interface IncidentFormData {
  titre: string;
  description: string;
  type: IncidentType;
  priorite: IncidentPriorite;
  lieu?: string;
  equipement?: string;
  images?: File[];
}

export interface LoginFormData {
  email: string;
  password: string;
}

export interface RegisterFormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  service?: string;
  telephone?: string;
  matricule?: string;
}
