import { describe, it, expect } from "vitest";
import {
  getStatutLabel,
  getStatutColor,
  getPrioriteLabel,
  getPrioriteColor,
  getTypeLabel,
  formatDate,
  formatDateShort,
  getTimeAgo,
  truncateText,
  getInitials,
  isValidEmail,
  isStrongPassword,
} from "@/utils/helpers";

describe("Helpers", () => {
  describe("getStatutLabel", () => {
    it("doit retourner le libellé correct pour chaque statut", () => {
      expect(getStatutLabel("OUVERT")).toBe("Ouvert");
      expect(getStatutLabel("AFFECTE")).toBe("Affecté");
      expect(getStatutLabel("EN_COURS")).toBe("En cours");
      expect(getStatutLabel("RESOLU")).toBe("Résolu");
      expect(getStatutLabel("EN_ATTENTE_VALIDATION")).toBe(
        "En attente de validation"
      );
      expect(getStatutLabel("CLOTURE")).toBe("Clôturé");
    });
  });

  describe("getStatutColor", () => {
    it("doit retourner la couleur correcte pour chaque statut", () => {
      expect(getStatutColor("OUVERT")).toContain("blue");
      expect(getStatutColor("AFFECTE")).toContain("yellow");
      expect(getStatutColor("EN_COURS")).toContain("purple");
      expect(getStatutColor("RESOLU")).toContain("green");
      expect(getStatutColor("CLOTURE")).toContain("gray");
    });
  });

  describe("getPrioriteLabel", () => {
    it("doit retourner le libellé correct pour chaque priorité", () => {
      expect(getPrioriteLabel("BASSE")).toBe("Basse");
      expect(getPrioriteLabel("MOYENNE")).toBe("Moyenne");
      expect(getPrioriteLabel("HAUTE")).toBe("Haute");
      expect(getPrioriteLabel("CRITIQUE")).toBe("Critique");
    });
  });

  describe("getPrioriteColor", () => {
    it("doit retourner la couleur correcte pour chaque priorité", () => {
      expect(getPrioriteColor("BASSE")).toContain("green");
      expect(getPrioriteColor("MOYENNE")).toContain("yellow");
      expect(getPrioriteColor("HAUTE")).toContain("orange");
      expect(getPrioriteColor("CRITIQUE")).toContain("red");
    });
  });

  describe("getTypeLabel", () => {
    it("doit retourner le libellé correct pour chaque type", () => {
      expect(getTypeLabel("RESEAU")).toBe("Réseau");
      expect(getTypeLabel("LOGICIEL")).toBe("Logiciel");
      expect(getTypeLabel("HARDWARE")).toBe("Matériel");
    });
  });

  describe("formatDate", () => {
    it("doit formater une date ISO en format français", () => {
      const result = formatDate("2024-01-15T10:30:00");
      expect(result).toMatch(/15\/01\/2024/);
      expect(result).toMatch(/10:30|10h30/);
    });

    it('doit retourner "-" pour une date null', () => {
      expect(formatDate(null)).toBe("-");
      expect(formatDate(undefined)).toBe("-");
    });
  });

  describe("formatDateShort", () => {
    it("doit formater une date ISO en format court", () => {
      const result = formatDateShort("2024-01-15T10:30:00");
      expect(result).toBe("15/01/2024");
    });

    it('doit retourner "-" pour une date null', () => {
      expect(formatDateShort(null)).toBe("-");
    });
  });

  describe("getTimeAgo", () => {
    it('doit retourner "À l\'instant" pour une date récente', () => {
      const now = new Date().toISOString();
      expect(getTimeAgo(now)).toBe("À l'instant");
    });

    it('doit retourner "Il y a X min" pour une date de quelques minutes', () => {
      const fiveMinutesAgo = new Date(Date.now() - 5 * 60000).toISOString();
      expect(getTimeAgo(fiveMinutesAgo)).toBe("Il y a 5 min");
    });

    it('doit retourner "Il y a Xh" pour une date de quelques heures', () => {
      const twoHoursAgo = new Date(Date.now() - 2 * 3600000).toISOString();
      expect(getTimeAgo(twoHoursAgo)).toBe("Il y a 2h");
    });

    it('doit retourner "Il y a Xj" pour une date de quelques jours', () => {
      const threeDaysAgo = new Date(Date.now() - 3 * 86400000).toISOString();
      expect(getTimeAgo(threeDaysAgo)).toBe("Il y a 3j");
    });
  });

  describe("truncateText", () => {
    it("doit tronquer un texte long", () => {
      const longText = "Ceci est un texte très long qui doit être tronqué";
      const result = truncateText(longText, 20);
      expect(result).toBe("Ceci est un texte...");
      expect(result.length).toBe(20);
    });

    it("ne doit pas modifier un texte court", () => {
      const shortText = "Court";
      expect(truncateText(shortText, 20)).toBe("Court");
    });

    it("doit gérer la longueur exacte", () => {
      const exactText = "Exactement vingt!";
      expect(truncateText(exactText, 17)).toBe("Exactement vingt!");
    });
  });

  describe("getInitials", () => {
    it("doit retourner les initiales d'un nom complet", () => {
      expect(getInitials("Jean Dupont")).toBe("JD");
    });

    it("doit retourner une seule lettre pour un prénom seul", () => {
      expect(getInitials("Jean")).toBe("J");
    });

    it("doit retourner max 2 initiales", () => {
      expect(getInitials("Jean Pierre Dupont")).toBe("JP");
    });

    it('doit retourner "?" pour un nom vide', () => {
      expect(getInitials("")).toBe("?");
    });
  });

  describe("isValidEmail", () => {
    it("doit valider une email correcte", () => {
      expect(isValidEmail("test@example.com")).toBe(true);
      expect(isValidEmail("user.name@domain.org")).toBe(true);
      expect(isValidEmail("user+tag@domain.co.fr")).toBe(true);
    });

    it("doit rejeter une email incorrecte", () => {
      expect(isValidEmail("invalid")).toBe(false);
      expect(isValidEmail("invalid@")).toBe(false);
      expect(isValidEmail("@domain.com")).toBe(false);
      expect(isValidEmail("test @example.com")).toBe(false);
    });
  });

  describe("isStrongPassword", () => {
    it("doit valider un mot de passe fort", () => {
      const result = isStrongPassword("Password123");
      expect(result.isValid).toBe(true);
      expect(result.errors).toHaveLength(0);
    });

    it("doit rejeter un mot de passe trop court", () => {
      const result = isStrongPassword("Pass1");
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain("Au moins 8 caractères requis");
    });

    it("doit rejeter un mot de passe sans majuscule", () => {
      const result = isStrongPassword("password123");
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain("Au moins une majuscule requise");
    });

    it("doit rejeter un mot de passe sans minuscule", () => {
      const result = isStrongPassword("PASSWORD123");
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain("Au moins une minuscule requise");
    });

    it("doit rejeter un mot de passe sans chiffre", () => {
      const result = isStrongPassword("PasswordABC");
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain("Au moins un chiffre requis");
    });

    it("doit retourner toutes les erreurs", () => {
      const result = isStrongPassword("abc");
      expect(result.isValid).toBe(false);
      expect(result.errors.length).toBeGreaterThan(1);
    });
  });
});
