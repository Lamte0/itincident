<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useIncidentStore } from "@/stores/incidents";
import { PhotoIcon, XMarkIcon } from "@heroicons/vue/24/outline";

const router = useRouter();
const incidentStore = useIncidentStore();

const form = ref({
  titre: "",
  description: "",
  type: "LOGICIEL",
  priorite: "MOYENNE",
  lieu: "",
  equipement: "",
});

const images = ref<File[]>([]);
const imagesPreviews = ref<string[]>([]);
const loading = ref(false);
const errorMessage = ref("");

const typeOptions = [
  {
    value: "RESEAU",
    label: "Réseau",
    description: "Problèmes de connexion, wifi, câblage...",
  },
  {
    value: "LOGICIEL",
    label: "Logiciel",
    description: "Bugs, erreurs, applications qui ne fonctionnent pas...",
  },
  {
    value: "HARDWARE",
    label: "Hardware",
    description: "Équipement physique défaillant (PC, imprimante, écran...)",
  },
];

const prioriteOptions = [
  { value: "BASSE", label: "Basse", description: "Peut attendre, pas urgent" },
  {
    value: "MOYENNE",
    label: "Moyenne",
    description: "À traiter dans les délais normaux",
  },
  { value: "HAUTE", label: "Haute", description: "Urgent, impacte le travail" },
  {
    value: "CRITIQUE",
    label: "Critique",
    description: "Bloquant, nécessite une intervention immédiate",
  },
];

function handleImageUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files) {
    const newFiles = Array.from(input.files);
    images.value = [...images.value, ...newFiles];

    newFiles.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        imagesPreviews.value.push(e.target?.result as string);
      };
      reader.readAsDataURL(file);
    });
  }
}

function removeImage(index: number) {
  images.value.splice(index, 1);
  imagesPreviews.value.splice(index, 1);
}

async function handleSubmit() {
  loading.value = true;
  errorMessage.value = "";

  try {
    const formData = new FormData();
    formData.append("titre", form.value.titre);
    formData.append("description", form.value.description);
    formData.append("type", form.value.type);
    formData.append("priorite", form.value.priorite);
    if (form.value.lieu) formData.append("lieu", form.value.lieu);
    if (form.value.equipement)
      formData.append("equipement", form.value.equipement);

    images.value.forEach((image, index) => {
      formData.append(`images[${index}]`, image);
    });

    await incidentStore.createIncident(formData);
    router.push("/mes-incidents");
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message || "Erreur lors de la création";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Déclarer un incident</h1>
      <p class="mt-1 text-sm text-gray-500">
        Remplissez ce formulaire pour signaler un problème informatique
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div
        v-if="errorMessage"
        class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded"
      >
        {{ errorMessage }}
      </div>

      <div class="bg-white shadow rounded-lg p-6 space-y-6">
        <!-- Titre -->
        <div>
          <label for="titre" class="block text-sm font-medium text-gray-700">
            Titre de l'incident *
          </label>
          <input
            id="titre"
            v-model="form.titre"
            type="text"
            required
            placeholder="Ex: Impossible d'accéder à l'application X"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Type d'incident *
          </label>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <label
              v-for="option in typeOptions"
              :key="option.value"
              :class="[
                'relative flex cursor-pointer rounded-lg border p-4 focus:outline-none',
                form.type === option.value
                  ? 'border-primary-500 ring-2 ring-primary-500'
                  : 'border-gray-300',
              ]"
            >
              <input
                v-model="form.type"
                type="radio"
                name="type"
                :value="option.value"
                class="sr-only"
              />
              <div class="flex flex-1 flex-col">
                <span class="block text-sm font-medium text-gray-900">
                  {{ option.label }}
                </span>
                <span class="mt-1 text-xs text-gray-500">
                  {{ option.description }}
                </span>
              </div>
            </label>
          </div>
        </div>

        <!-- Priorité -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Niveau de priorité *
          </label>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <label
              v-for="option in prioriteOptions"
              :key="option.value"
              :class="[
                'relative flex cursor-pointer rounded-lg border p-3 focus:outline-none',
                form.priorite === option.value
                  ? 'border-primary-500 ring-2 ring-primary-500'
                  : 'border-gray-300',
              ]"
            >
              <input
                v-model="form.priorite"
                type="radio"
                name="priorite"
                :value="option.value"
                class="sr-only"
              />
              <div class="flex flex-1 flex-col">
                <span class="block text-sm font-medium text-gray-900">
                  {{ option.label }}
                </span>
              </div>
            </label>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label
            for="description"
            class="block text-sm font-medium text-gray-700"
          >
            Description détaillée *
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            required
            placeholder="Décrivez le problème en détail..."
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          ></textarea>
        </div>

        <!-- Lieu -->
        <div>
          <label for="lieu" class="block text-sm font-medium text-gray-700">
            Localisation
          </label>
          <input
            id="lieu"
            v-model="form.lieu"
            type="text"
            placeholder="Ex: Bureau 205, Bâtiment A"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>

        <!-- Équipement -->
        <div>
          <label
            for="equipement"
            class="block text-sm font-medium text-gray-700"
          >
            Équipement concerné
          </label>
          <input
            id="equipement"
            v-model="form.equipement"
            type="text"
            placeholder="Ex: PC-12345, Imprimante HP LaserJet"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>

        <!-- Images -->
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Images / Captures d'écran
          </label>
          <div
            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md"
          >
            <div class="space-y-1 text-center">
              <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
              <div class="flex text-sm text-gray-600">
                <label
                  for="images"
                  class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none"
                >
                  <span>Télécharger des images</span>
                  <input
                    id="images"
                    name="images"
                    type="file"
                    accept="image/*"
                    multiple
                    class="sr-only"
                    @change="handleImageUpload"
                  />
                </label>
                <p class="pl-1">ou glisser-déposer</p>
              </div>
              <p class="text-xs text-gray-500">PNG, JPG, GIF jusqu'à 10MB</p>
            </div>
          </div>

          <!-- Aperçu des images -->
          <div
            v-if="imagesPreviews.length > 0"
            class="mt-4 grid grid-cols-3 gap-4"
          >
            <div
              v-for="(preview, index) in imagesPreviews"
              :key="index"
              class="relative"
            >
              <img :src="preview" class="h-24 w-full object-cover rounded-lg" />
              <button
                type="button"
                @click="removeImage(index)"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1"
              >
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="router.back()"
          class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Annuler
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
        >
          {{ loading ? "Envoi en cours..." : "Soumettre l'incident" }}
        </button>
      </div>
    </form>
  </div>
</template>
