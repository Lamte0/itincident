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
    OUVERT: "bg-red-100 text-red-800",
    AFFECTE: "bg-blue-100 text-blue-800",
    EN_COURS: "bg-yellow-100 text-yellow-800",
    RESOLU: "bg-green-100 text-green-800",
    EN_ATTENTE_VALIDATION: "bg-purple-100 text-purple-800",
    CLOTURE: "bg-gray-100 text-gray-800",
  };
  return badges[statut] || "bg-gray-100 text-gray-800";
}

function getPrioriteBadge(priorite: string) {
  const badges: Record<string, string> = {
    BASSE: "bg-green-100 text-green-800",
    MOYENNE: "bg-yellow-100 text-yellow-800",
    HAUTE: "bg-orange-100 text-orange-800",
    CRITIQUE: "bg-red-100 text-red-800",
  };
  return badges[priorite] || "bg-gray-100 text-gray-800";
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
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
    >
      {{ error }}
    </div>

    <!-- Content -->
    <template v-else-if="incident">
      <!-- Header -->
      <div class="mb-6">
        <button
          @click="router.back()"
          class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
        >
          <ArrowLeftIcon class="h-4 w-4 mr-1" />
          Retour
        </button>

        <div class="sm:flex sm:items-center sm:justify-between">
          <div>
            <div class="flex items-center space-x-3">
              <h1 class="text-2xl font-bold text-gray-900">
                {{ incident.reference }}
              </h1>
              <span
                :class="[
                  'px-3 py-1 text-sm font-semibold rounded-full',
                  getStatutBadge(incident.statut),
                ]"
              >
                {{ incident.statut.replace(/_/g, " ") }}
              </span>
              <span
                :class="[
                  'px-3 py-1 text-sm font-semibold rounded-full',
                  getPrioriteBadge(incident.priorite),
                ]"
              >
                {{ incident.priorite }}
              </span>
            </div>
            <p class="mt-1 text-lg text-gray-600">{{ incident.titre }}</p>
          </div>

          <div class="mt-4 sm:mt-0 flex space-x-3">
            <button
              v-if="incident.statut === 'CLOTURE'"
              @click="downloadFiche"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
              Fiche d'intervention
            </button>

            <button
              v-if="canAffecter"
              @click="openAffectationModal"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
            >
              <PaperAirplaneIcon class="h-5 w-5 mr-2" />
              Affecter
            </button>

            <button
              v-if="canPrendreEnCharge"
              @click="prendreEnCharge"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
            >
              <PlayIcon class="h-5 w-5 mr-2" />
              Prendre en charge
            </button>

            <button
              v-if="canResoudre"
              @click="showResolutionModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
            >
              <CheckCircleIcon class="h-5 w-5 mr-2" />
              Marquer résolu
            </button>

            <template v-if="canValider">
              <button
                @click="showValidationModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
              >
                <CheckCircleIcon class="h-5 w-5 mr-2" />
                Valider
              </button>
              <button
                @click="showRejetModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700"
              >
                <XCircleIcon class="h-5 w-5 mr-2" />
                Rejeter
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Colonne principale -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Description</h2>
            <p class="text-gray-700 whitespace-pre-wrap">
              {{ incident.description }}
            </p>
          </div>

          <!-- Images -->
          <div
            v-if="incident.images?.length"
            class="bg-white shadow rounded-lg p-6"
          >
            <h2 class="text-lg font-medium text-gray-900 mb-4">Images</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="image in incident.images"
                :key="image.id"
                class="relative"
              >
                <img
                  :src="`http://localhost:8000/storage/${image.chemin}`"
                  :alt="image.nom_fichier"
                  class="w-full h-32 object-cover rounded-lg"
                />
              </div>
            </div>
          </div>

          <!-- Historique -->
          <div
            v-if="incident.historique_statuts?.length"
            class="bg-white shadow rounded-lg p-6"
          >
            <h2 class="text-lg font-medium text-gray-900 mb-4">Historique</h2>
            <div class="flow-root">
              <ul class="-mb-8">
                <li
                  v-for="(item, index) in incident.historique_statuts"
                  :key="item.id"
                  class="relative pb-8"
                >
                  <span
                    v-if="index !== incident.historique_statuts.length - 1"
                    class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                  ></span>
                  <div class="relative flex space-x-3">
                    <div>
                      <span
                        class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center"
                      >
                        <ClockIcon class="h-5 w-5 text-primary-600" />
                      </span>
                    </div>
                    <div
                      class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4"
                    >
                      <div>
                        <p class="text-sm text-gray-500">
                          <span class="font-medium text-gray-900">{{
                            item.nouveau_statut.replace(/_/g, " ")
                          }}</span>
                          <span v-if="item.commentaire">
                            - {{ item.commentaire }}</span
                          >
                        </p>
                      </div>
                      <div
                        class="text-right text-sm whitespace-nowrap text-gray-500"
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
            class="bg-white shadow rounded-lg p-6"
          >
            <h2 class="text-lg font-medium text-gray-900 mb-4">
              Rapport d'intervention
            </h2>
            <p class="text-gray-700 whitespace-pre-wrap">
              {{ incident.affectation_active.rapport_intervention }}
            </p>
          </div>

          <!-- Note et commentaire validation -->
          <div v-if="incident.note" class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Évaluation</h2>
            <div class="flex items-center space-x-1 mb-2">
              <template v-for="i in 5" :key="i">
                <StarSolidIcon
                  v-if="i <= incident.note"
                  class="h-5 w-5 text-yellow-400"
                />
                <StarIcon v-else class="h-5 w-5 text-gray-300" />
              </template>
              <span class="ml-2 text-sm text-gray-600"
                >{{ incident.note }}/5</span
              >
            </div>
            <p v-if="incident.commentaire_validation" class="text-gray-700">
              {{ incident.commentaire_validation }}
            </p>
          </div>
        </div>

        <!-- Colonne latérale -->
        <div class="space-y-6">
          <!-- Informations -->
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Informations</h2>
            <dl class="space-y-4">
              <div class="flex items-start">
                <dt class="flex-shrink-0">
                  <UserIcon class="h-5 w-5 text-gray-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-sm font-medium text-gray-900">Déclaré par</p>
                  <p class="text-sm text-gray-500">
                    {{ incident.auteur?.name }}
                  </p>
                </dd>
              </div>
              <div class="flex items-start">
                <dt class="flex-shrink-0">
                  <CalendarIcon class="h-5 w-5 text-gray-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-sm font-medium text-gray-900">
                    Date de création
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ formatDate(incident.created_at) }}
                  </p>
                </dd>
              </div>
              <div class="flex items-start">
                <dt class="flex-shrink-0">
                  <MapPinIcon class="h-5 w-5 text-gray-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-sm font-medium text-gray-900">Lieu</p>
                  <p class="text-sm text-gray-500">{{ incident.lieu }}</p>
                </dd>
              </div>
              <div class="flex items-start">
                <dt class="flex-shrink-0">
                  <ComputerDesktopIcon class="h-5 w-5 text-gray-400" />
                </dt>
                <dd class="ml-3">
                  <p class="text-sm font-medium text-gray-900">Équipement</p>
                  <p class="text-sm text-gray-500">{{ incident.equipement }}</p>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Affectation -->
          <div
            v-if="incident.affectation_active"
            class="bg-white shadow rounded-lg p-6"
          >
            <h2 class="text-lg font-medium text-gray-900 mb-4">Affectation</h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-sm font-medium text-gray-900">Maintenancier</dt>
                <dd class="text-sm text-gray-500">
                  {{ incident.affectation_active.maintenancier?.name }}
                </dd>
              </div>
              <div v-if="incident.affectation_active.instructions">
                <dt class="text-sm font-medium text-gray-900">Instructions</dt>
                <dd class="text-sm text-gray-500">
                  {{ incident.affectation_active.instructions }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-900">
                  Date d'affectation
                </dt>
                <dd class="text-sm text-gray-500">
                  {{ formatDate(incident.affectation_active.date_affectation) }}
                </dd>
              </div>
              <div v-if="incident.affectation_active.date_prise_en_charge">
                <dt class="text-sm font-medium text-gray-900">
                  Prise en charge
                </dt>
                <dd class="text-sm text-gray-500">
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
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div class="flex items-center justify-center min-h-screen px-4">
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75"
            @click="showAffectationModal = false"
          ></div>
          <div
            class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6"
          >
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Affecter l'incident
            </h3>
            <form @submit.prevent="affecter" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Maintenancier *</label
                >
                <select
                  v-model="affectationForm.maintenancier_id"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
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
                <label class="block text-sm font-medium text-gray-700"
                  >Instructions</label
                >
                <textarea
                  v-model="affectationForm.instructions"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                ></textarea>
              </div>
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="showAffectationModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="!affectationForm.maintenancier_id"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
                >
                  Affecter
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Modal Résolution -->
      <div
        v-if="showResolutionModal"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div class="flex items-center justify-center min-h-screen px-4">
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75"
            @click="showResolutionModal = false"
          ></div>
          <div
            class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6"
          >
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Marquer comme résolu
            </h3>
            <form @submit.prevent="resoudre" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Rapport d'intervention *</label
                >
                <textarea
                  v-model="resolutionForm.rapport_intervention"
                  rows="4"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="Décrivez les actions réalisées..."
                ></textarea>
              </div>
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="showResolutionModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="!resolutionForm.rapport_intervention"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                  Marquer résolu
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Modal Validation -->
      <div
        v-if="showValidationModal"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <div class="flex items-center justify-center min-h-screen px-4">
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75"
            @click="showValidationModal = false"
          ></div>
          <div
            class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6"
          >
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Valider et clôturer
            </h3>
            <form @submit.prevent="valider" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Note</label
                >
                <div class="flex items-center space-x-1">
                  <button
                    v-for="i in 5"
                    :key="i"
                    type="button"
                    @click="validationForm.note = i"
                    class="focus:outline-none"
                  >
                    <StarSolidIcon
                      v-if="i <= validationForm.note"
                      class="h-8 w-8 text-yellow-400"
                    />
                    <StarIcon
                      v-else
                      class="h-8 w-8 text-gray-300 hover:text-yellow-400"
                    />
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Commentaire</label
                >
                <textarea
                  v-model="validationForm.commentaire_validation"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                ></textarea>
              </div>
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="showValidationModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700"
                >
                  Valider et clôturer
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Modal Rejet -->
      <div v-if="showRejetModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75"
            @click="showRejetModal = false"
          ></div>
          <div
            class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6"
          >
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Rejeter la résolution
            </h3>
            <form @submit.prevent="rejeter" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Motif du rejet *</label
                >
                <textarea
                  v-model="rejetForm.motif"
                  rows="4"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="Expliquez pourquoi la résolution n'est pas satisfaisante..."
                ></textarea>
              </div>
              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="showRejetModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="!rejetForm.motif"
                  class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
                >
                  Rejeter
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>

    <!-- Incident non trouvé -->
    <div
      v-else
      class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg"
    >
      Incident non trouvé
    </div>
  </div>
</template>
