import type { IncidentStatut, IncidentPriorite, IncidentType } from "@/types";

/**
 * Retourne le libellé en français d'un statut d'incident
 */
export function getStatutLabel(statut: IncidentStatut): string {
  const labels: Record<IncidentStatut, string> = {
    OUVERT: "Ouvert",
    AFFECTE: "Affecté",
    EN_COURS: "En cours",
    RESOLU: "Résolu",
    EN_ATTENTE_VALIDATION: "En attente de validation",
    CLOTURE: "Clôturé",
  };
  return labels[statut] || statut;
}

/**
 * Retourne la couleur CSS associée à un statut
 */
export function getStatutColor(statut: IncidentStatut): string {
  const colors: Record<IncidentStatut, string> = {
    OUVERT: "bg-blue-100 text-blue-800",
    AFFECTE: "bg-yellow-100 text-yellow-800",
    EN_COURS: "bg-purple-100 text-purple-800",
    RESOLU: "bg-green-100 text-green-800",
    EN_ATTENTE_VALIDATION: "bg-orange-100 text-orange-800",
    CLOTURE: "bg-gray-100 text-gray-800",
  };
  return colors[statut] || "bg-gray-100 text-gray-800";
}

/**
 * Retourne le libellé en français d'une priorité
 */
export function getPrioriteLabel(priorite: IncidentPriorite): string {
  const labels: Record<IncidentPriorite, string> = {
    BASSE: "Basse",
    MOYENNE: "Moyenne",
    HAUTE: "Haute",
    CRITIQUE: "Critique",
  };
  return labels[priorite] || priorite;
}

/**
 * Retourne la couleur CSS associée à une priorité
 */
export function getPrioriteColor(priorite: IncidentPriorite): string {
  const colors: Record<IncidentPriorite, string> = {
    BASSE: "bg-green-100 text-green-800",
    MOYENNE: "bg-yellow-100 text-yellow-800",
    HAUTE: "bg-orange-100 text-orange-800",
    CRITIQUE: "bg-red-100 text-red-800",
  };
  return colors[priorite] || "bg-gray-100 text-gray-800";
}

/**
 * Retourne le libellé en français d'un type d'incident
 */
export function getTypeLabel(type: IncidentType): string {
  const labels: Record<IncidentType, string> = {
    RESEAU: "Réseau",
    LOGICIEL: "Logiciel",
    HARDWARE: "Matériel",
  };
  return labels[type] || type;
}

/**
 * Formate une date ISO en format français
 */
export function formatDate(dateString: string | null | undefined): string {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

/**
 * Formate une date ISO en format court
 */
export function formatDateShort(dateString: string | null | undefined): string {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

/**
 * Calcule le temps écoulé depuis une date
 */
export function getTimeAgo(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return "À l'instant";
  if (diffMins < 60) return `Il y a ${diffMins} min`;
  if (diffHours < 24) return `Il y a ${diffHours}h`;
  if (diffDays < 30) return `Il y a ${diffDays}j`;

  return formatDateShort(dateString);
}

/**
 * Tronque un texte à une longueur maximale
 */
export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength - 3) + "...";
}

/**
 * Génère les initiales d'un nom
 */
export function getInitials(name: string): string {
  if (!name) return "?";
  return name
    .split(" ")
    .map((word) => word.charAt(0).toUpperCase())
    .slice(0, 2)
    .join("");
}

/**
 * Valide une adresse email
 */
export function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * Vérifie si un mot de passe est assez fort
 */
export function isStrongPassword(password: string): {
  isValid: boolean;
  errors: string[];
} {
  const errors: string[] = [];

  if (password.length < 8) {
    errors.push("Au moins 8 caractères requis");
  }
  if (!/[A-Z]/.test(password)) {
    errors.push("Au moins une majuscule requise");
  }
  if (!/[a-z]/.test(password)) {
    errors.push("Au moins une minuscule requise");
  }
  if (!/[0-9]/.test(password)) {
    errors.push("Au moins un chiffre requis");
  }

  return {
    isValid: errors.length === 0,
    errors,
  };
}
