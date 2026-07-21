<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useIncidentStore } from "@/stores/incidents";
import { useAuthStore } from "@/stores/auth";
import { userService, reportService } from "@/services/api";
import type { User } from "@/types";
import {
  ArrowLeftIcon,
  ClockIcon,
  MapPinIcon,
  ComputerDesktopIcon,
  UserIcon,
  CalendarIcon,
  DocumentArrowDownIcon,
  StarIcon,
  CheckCircleIcon,
  XCircleIcon,
  PlayIcon,
  PaperAirplaneIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import { StarIcon as StarSolidIcon } from "@heroicons/vue/24/solid";

const route = useRoute();
const router = useRouter();
const incidentStore = useIncidentStore();
const authStore = useAuthStore();

const incident = computed(() => incidentStore.currentIncident);
const loading = computed(() => incidentStore.loading);
const error = computed(() => incidentStore.error);

// Modals et formulaires
const showAffectationModal = ref(false);
const showResolutionModal = ref(false);
const showValidationModal = ref(false);
const showRejetModal = ref(false);

const maintenanciers = ref<User[]>([]);
const affectationForm = ref({
  maintenancier_id: 0,
  instructions: "",
});
const resolutionForm = ref({
  rapport_intervention: "",
});
const validationForm = ref({
  note: 5,
  commentaire_validation: "",
});
const rejetForm = ref({
  motif: "",
});

// Computed pour permissions
const canAffecter = computed(() => {
  return (
    authStore.hasRole(["CHEF_SERVICE", "ADMIN"]) &&
    incident.value?.statut === "OUVERT"
  );
});

const canPrendreEnCharge = computed(() => {
  return (
    authStore.user?.role === "MAINTENANCIER" &&
    incident.value?.statut === "AFFECTE" &&
    incident.value?.affectation_active?.maintenancier_id === authStore.user?.id
  );
});

const canResoudre = computed(() => {
  return (
    authStore.user?.role === "MAINTENANCIER" &&
    incident.value?.statut === "EN_COURS" &&
    incident.value?.affectation_active?.maintenancier_id === authStore.user?.id
  );
});

const canValider = computed(() => {
  return (
    incident.value?.auteur_id === authStore.user?.id &&
    incident.value?.statut === "EN_ATTENTE_VALIDATION"
  );
});

function getStatutBadge(statut: string) {
  const badges: Record<string, string> = {
    OUVERT: "bg-red-50 text-red-700 border border-red-100",
    AFFECTE: "bg-blue-50 text-blue-700 border border-blue-100",
    EN_COURS: "bg-amber-50 text-amber-700 border border-amber-100",
    RESOLU: "bg-emerald-50 text-emerald-700 border border-emerald-100",
    EN_ATTENTE_VALIDATION: "bg-purple-50 text-purple-700 border border-purple-100",
    CLOTURE: "bg-slate-100 text-slate-700 border border-slate-200",
  };
  return badges[statut] || "bg-slate-100 text-slate-700 border border-slate-200";
}

function getPrioriteBadge(priorite: string) {
  const badges: Record<string, string> = {
    BASSE: "bg-green-50 text-green-700 border border-green-100",
    MOYENNE: "bg-yellow-50 text-yellow-700 border border-yellow-100",
    HAUTE: "bg-orange-50 text-orange-700 border border-orange-100",
    CRITIQUE: "bg-red-50 text-red-700 border border-red-100",
  };
  return badges[priorite] || "bg-slate-100 text-slate-700 border border-slate-200";
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function loadMaintenanciers() {
  try {
    const response = await userService.getMaintenanciers();
    maintenanciers.value = response.data;
  } catch (err) {
    console.error("Erreur chargement maintenanciers", err);
  }
}

async function affecter() {
  if (!incident.value || !affectationForm.value.maintenancier_id) return;
  try {
    await incidentStore.affecterIncident(
      incident.value.id,
      affectationForm.value.maintenancier_id,
      affectationForm.value.instructions
    );
    showAffectationModal.value = false;
    affectationForm.value = { maintenancier_id: 0, instructions: "" };
  } catch (err) {
    console.error("Erreur affectation", err);
  }
}

async function prendreEnCharge() {
  if (!incident.value) return;
  try {
    await incidentStore.prendreEnCharge(incident.value.id);
  } catch (err) {
    console.error("Erreur prise en charge", err);
  }
}

async function resoudre() {
  if (!incident.value || !resolutionForm.value.rapport_intervention) return;
  try {
    await incidentStore.resoudreIncident(
      incident.value.id,
      resolutionForm.value.rapport_intervention
    );
    showResolutionModal.value = false;
    resolutionForm.value = { rapport_intervention: "" };
  } catch (err) {
    console.error("Erreur résolution", err);
  }
}

async function valider() {
  if (!incident.value) return;
  try {
    await incidentStore.validerIncident(
      incident.value.id,
      validationForm.value.note,
      validationForm.value.commentaire_validation
    );
    showValidationModal.value = false;
  } catch (err) {
    console.error("Erreur validation", err);
  }
}

async function rejeter() {
  if (!incident.value || !rejetForm.value.motif) return;
  try {
    await incidentStore.rejeterResolution(
      incident.value.id,
      rejetForm.value.motif
    );
    showRejetModal.value = false;
    rejetForm.value = { motif: "" };
  } catch (err) {
    console.error("Erreur rejet", err);
  }
}

async function downloadFiche() {
  if (!incident.value) return;
  try {
    const response = await reportService.getFicheIntervention(
      incident.value.id
    );
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `fiche-intervention-${incident.value.reference}.pdf`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    console.error("Erreur téléchargement", err);
  }
}

function openAffectationModal() {
  loadMaintenanciers();
  affectationForm.value = { maintenancier_id: 0, instructions: "" };
  showAffectationModal.value = true;
}

onMounted(async () => {
  const id = Number(route.params.id);
  if (id) {
    await incidentStore.fetchIncident(id);
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-100 text-red-650 p-4 rounded-2xl text-sm font-semibold animate-fade-in-up"
    >
      {{ error }}
    </div>

    <!-- Content -->
    <template v-else-if="incident">
      <!-- Header -->
      <div class="space-y-4">
        <button
          @click="router.back()"
          class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-slate-800 gap-1.5 transition-colors"
        >
          <ArrowLeftIcon class="h-4 w-4" />
          Retour
        </button>

        <div class="sm:flex sm:items-start sm:justify-between gap-6">
          <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-2xl font-extrabold text-slate-850 tracking-tight">
                {{ incident.reference }}
              </h1>
              <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg', getStatutBadge(incident.statut)]">
                {{ incident.statut.replace(/_/g, " ") }}
              </span>
              <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg', getPrioriteBadge(incident.priorite)]">
                {{ incident.priorite }}
              </span>
            </div>
            <p class="text-lg font-bold text-slate-700">{{ incident.titre }}</p>
          </div>

          <div class="flex-shrink-0 flex flex-wrap gap-2.5 mt-4 sm:mt-0">
            <button
              v-if="incident.statut === 'CLOTURE'"
              @click="downloadFiche"
              class="inline-flex items-center justify-center px-4 py-2 border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 font-semibold rounded-xl text-sm transition-colors shadow-sm"
            >
              <DocumentArrowDownIcon class="h-4.5 w-4.5 mr-2" />
              Fiche d'intervention
            </button>

            <button
              v-if="canAffecter"
              @click="openAffectationModal"
              class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm shadow-indigo-600/10 hover:-translate-y-0.5"
            >
              <PaperAirplaneIcon class="h-4.5 w-4.5 mr-2" />
              Affecter
            </button>

            <button
              v-if="canPrendreEnCharge"
              @click="prendreEnCharge"
              class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm shadow-blue-600/10 hover:-translate-y-0.5"
            >
              <PlayIcon class="h-4.5 w-4.5 mr-2" />
              Prendre en charge
            </button>

            <button
              v-if="canResoudre"
              @click="showResolutionModal = true"
              class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm shadow-emerald-600/10 hover:-translate-y-0.5"
            >
              <CheckCircleIcon class="h-4.5 w-4.5 mr-2" />
              Marquer résolu
            </button>

            <template v-if="canValider">
              <button
                @click="showValidationModal = true"
                class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm shadow-emerald-600/10 hover:-translate-y-0.5"
              >
                <CheckCircleIcon class="h-4.5 w-4.5 mr-2" />
                Valider
              </button>
              <button
                @click="showRejetModal = true"
                class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm shadow-red-600/10 hover:-translate-y-0.5"
              >
                <XCircleIcon class="h-4.5 w-4.5 mr-2" />
                Rejeter
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- Main Grid Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Description</h2>
            <p class="text-sm font-medium text-slate-700 whitespace-pre-wrap leading-relaxed">
              {{ incident.description }}
            </p>
          </div>

          <!-- Images -->
          <div
            v-if="incident.images?.length"
            class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm"
          >
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Pièces jointes / Captures</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="image in incident.images"
                :key="image.id"
                class="relative rounded-xl overflow-hidden border border-slate-200 shadow-sm"
              >
                <img
                  :src="`http://localhost:8000/storage/${image.chemin}`"
                  :alt="image.nom_fichier"
                  class="w-full h-32 object-cover hover:scale-105 transition-transform duration-300 cursor-pointer"
                />
              </div>
            </div>
          </div>

          <!-- Timeline Historique -->
          <div
            v-if="incident.historique_statuts?.length"
            class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm"
          >
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-6">Historique des statuts</h2>
            <div class="flow-root">
              <ul class="-mb-8">
                <li
                  v-for="(item, index) in incident.historique_statuts"
                  :key="item.id"
                  class="relative pb-8"
                >
                  <span
                    v-if="index !== incident.historique_statuts.length - 1"
                    class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-100"
                  ></span>
                  <div class="relative flex space-x-3">
                    <div>
                      <span
                        class="h-8 w-8 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center"
                      >
                        <ClockIcon class="h-4.5 w-4.5 text-indigo-600" />
                      </span>
                    </div>
                    <div
                      class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4"
                    >
                      <div>
                        <p class="text-sm font-semibold text-slate-800">
                          <span>{{
                            item.nouveau_statut.replace(/_/g, " ")
                          }}</span>
                          <span v-if="item.commentaire" class="text-slate-500 font-normal ml-2">
                            - {{ item.commentaire }}</span
                          >
                        </p>
                      </div>
                      <div
                        class="text-right text-xs font-semibold whitespace-nowrap text-slate-450"
                      >
                        {{ formatDate(item.created_at) }}
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Rapport d'intervention -->
          <div
            v-if="incident.affectation_active?.rapport_intervention"
            class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm"
          >
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">
              Rapport d'intervention
            </h2>
            <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-150 font-medium">
              {{ incident.affectation_active.rapport_intervention }}
            </p>
          </div>

          <!-- Note et commentaire validation -->
          <div v-if="incident.note" class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Évaluation du demandeur</h2>
            <div class="flex items-center space-x-1 mb-3">
              <template v-for="i in 5" :key="i">
                <StarSolidIcon
                  v-if="i <= incident.note"
                  class="h-6 w-6 text-amber-400"
                />
                <StarIcon v-else class="h-6 w-6 text-slate-250" />
              </template>
              <span class="ml-2.5 text-sm font-bold text-slate-650"
                >{{ incident.note }}/5</span
              >
            </div>
            <p v-if="incident.commentaire_validation" class="text-sm font-medium text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-150 leading-relaxed">
              {{ incident.commentaire_validation }}
            </p>
          </div>
        </div>

        <!-- Sidebar Column -->
        <div class="space-y-6">
          <!-- Informations -->
          <div class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Informations générales</h2>
            <dl class="space-y-4">
              <div class="flex items-start">
                <dt class="flex-shrink-0 mt-0.5">
                  <UserIcon class="h-5 w-5 text-slate-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Déclaré par</p>
                  <p class="text-sm font-bold text-slate-700">
                    {{ incident.auteur?.name }}
                  </p>
                </dd>
              </div>
              <div class="flex items-start">
                <dt class="flex-shrink-0 mt-0.5">
                  <CalendarIcon class="h-5 w-5 text-slate-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">
                    Date de création
                  </p>
                  <p class="text-sm font-bold text-slate-700">
                    {{ formatDate(incident.created_at) }}
                  </p>
                </dd>
              </div>
              <div class="flex items-start">
                <dt class="flex-shrink-0 mt-0.5">
                  <MapPinIcon class="h-5 w-5 text-slate-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Lieu</p>
                  <p class="text-sm font-bold text-slate-700">{{ incident.lieu || "Non spécifié" }}</p>
                </dd>
              </div>
              <div class="flex items-start">
                <dt class="flex-shrink-0 mt-0.5">
                  <ComputerDesktopIcon class="h-5 w-5 text-slate-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Équipement</p>
                  <p class="text-sm font-bold text-slate-700">{{ incident.equipement || "Non spécifié" }}</p>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Affectation -->
          <div
            v-if="incident.affectation_active"
            class="bg-white border border-slate-200/85 rounded-2xl p-6 shadow-sm"
          >
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Détails d'affectation</h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">Maintenancier</dt>
                <dd class="text-sm font-bold text-slate-700 flex items-center gap-2">
                  <div class="h-6 w-6 bg-indigo-50 border border-indigo-150 rounded-full flex items-center justify-center text-xs text-indigo-700 font-bold">
                    {{ incident.affectation_active.maintenancier?.name?.charAt(0).toUpperCase() }}
                  </div>
                  {{ incident.affectation_active.maintenancier?.name }}
                </dd>
              </div>
              <div v-if="incident.affectation_active.instructions">
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">Instructions</dt>
                <dd class="text-sm font-semibold text-slate-650 bg-slate-50 p-3 rounded-xl border border-slate-150">
                  {{ incident.affectation_active.instructions }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">
                  Date d'affectation
                </dt>
                <dd class="text-sm font-semibold text-slate-700">
                  {{ formatDate(incident.affectation_active.date_affectation) }}
                </dd>
              </div>
              <div v-if="incident.affectation_active.date_prise_en_charge">
                <dt class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-0.5">
                  Prise en charge le
                </dt>
                <dd class="text-sm font-semibold text-slate-700">
                  {{
                    formatDate(incident.affectation_active.date_prise_en_charge)
                  }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Modal Affectation -->
      <div
        v-if="showAffectationModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      >
        <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full p-6 animate-fade-in-up space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">
              Affecter l'incident
            </h3>
            <button
              @click="showAffectationModal = false"
              class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <form @submit.prevent="affecter" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Maintenancier *</label>
              <select
                v-model="affectationForm.maintenancier_id"
                required
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              >
                <option value="0" disabled>
                  Sélectionner un maintenancier
                </option>
                <option v-for="m in maintenanciers" :key="m.id" :value="m.id">
                  {{ m.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Instructions</label>
              <textarea
                v-model="affectationForm.instructions"
                rows="3"
                placeholder="Spécifiez des instructions particulières pour le technicien..."
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showAffectationModal = false"
                class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="!affectationForm.maintenancier_id"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-indigo-600/10"
              >
                Affecter
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Résolution -->
      <div
        v-if="showResolutionModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      >
        <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full p-6 animate-fade-in-up space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">
              Marquer comme résolu
            </h3>
            <button
              @click="showResolutionModal = false"
              class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <form @submit.prevent="resoudre" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Rapport d'intervention *</label>
              <textarea
                v-model="resolutionForm.rapport_intervention"
                rows="4"
                required
                placeholder="Décrivez les actions réalisées et les pièces remplacées..."
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showResolutionModal = false"
                class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="!resolutionForm.rapport_intervention"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-emerald-600/10"
              >
                Marquer résolu
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Validation -->
      <div
        v-if="showValidationModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      >
        <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full p-6 animate-fade-in-up space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">
              Valider et clôturer
            </h3>
            <button
              @click="showValidationModal = false"
              class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <form @submit.prevent="valider" class="space-y-4">
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Attribuer une note</label>
              <div class="flex items-center gap-1">
                <button
                  v-for="i in 5"
                  :key="i"
                  type="button"
                  @click="validationForm.note = i"
                  class="focus:outline-none transition-transform active:scale-95"
                >
                  <StarSolidIcon
                    v-if="i <= validationForm.note"
                    class="h-8 w-8 text-yellow-400"
                  />
                  <StarIcon
                    v-else
                    class="h-8 w-8 text-slate-300 hover:text-yellow-400"
                  />
                </button>
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Commentaire de validation</label>
              <textarea
                v-model="validationForm.commentaire_validation"
                rows="3"
                placeholder="Votre avis sur la qualité de l'intervention..."
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showValidationModal = false"
                class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-emerald-600/10"
              >
                Valider et clôturer
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Rejet -->
      <div
        v-if="showRejetModal"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      >
        <div class="bg-white border border-slate-100 rounded-3xl shadow-2xl max-w-md w-full p-6 animate-fade-in-up space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">
              Rejeter la résolution
            </h3>
            <button
              @click="showRejetModal = false"
              class="text-slate-400 hover:text-slate-650 bg-slate-100 hover:bg-slate-200 rounded-full p-1 transition-colors"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <form @submit.prevent="rejeter" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Motif du rejet *</label>
              <textarea
                v-model="rejetForm.motif"
                rows="4"
                required
                placeholder="Veuillez spécifier pourquoi la panne n'est toujours pas résolue..."
                class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button
                type="button"
                @click="showRejetModal = false"
                class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="!rejetForm.motif"
                class="px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-red-600/10"
              >
                Rejeter la résolution
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>

    <!-- Incident non trouvé -->
    <div
      v-else
      class="bg-amber-50 border border-amber-100 text-amber-600 p-4 rounded-2xl text-sm font-semibold"
    >
      Incident non trouvé.
    </div>
  </div>
</template>

