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
  <div class="max-w-3xl mx-auto space-y-6">
    <div class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Déclarer un incident</h1>
      <p class="text-sm text-slate-500">
        Remplissez ce formulaire pour signaler un problème informatique rencontré.
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div
        v-if="errorMessage"
        class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-2xl text-sm font-semibold animate-fade-in-up"
      >
        {{ errorMessage }}
      </div>

      <div class="bg-white border border-slate-200/80 rounded-2xl p-6 md:p-8 shadow-sm space-y-6">
        <!-- Titre -->
        <div class="space-y-1.5">
          <label for="titre" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
            Titre de l'incident *
          </label>
          <input
            id="titre"
            v-model="form.titre"
            type="text"
            required
            placeholder="Ex: Impossible d'accéder à l'application X"
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/55 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          />
        </div>

        <!-- Type -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
            Type d'incident *
          </label>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <label
              v-for="option in typeOptions"
              :key="option.value"
              :class="[
                'relative flex cursor-pointer rounded-2xl border p-4 focus:outline-none transition-all duration-200',
                form.type === option.value
                  ? 'border-indigo-600 bg-indigo-50/30 ring-2 ring-indigo-500/10'
                  : 'border-slate-200 hover:border-slate-350 hover:bg-slate-50/30',
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
                <span class="block text-sm font-bold text-slate-800">
                  {{ option.label }}
                </span>
                <span class="mt-1 text-xs text-slate-500 leading-normal">
                  {{ option.description }}
                </span>
              </div>
            </label>
          </div>
        </div>

        <!-- Priorité -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
            Niveau de priorité *
          </label>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <label
              v-for="option in prioriteOptions"
              :key="option.value"
              :class="[
                'relative flex cursor-pointer rounded-2xl border p-3.5 focus:outline-none justify-center text-center transition-all duration-200',
                form.priorite === option.value
                  ? 'border-indigo-600 bg-indigo-50/30 ring-2 ring-indigo-500/10'
                  : 'border-slate-200 hover:border-slate-350 hover:bg-slate-50/30',
              ]"
            >
              <input
                v-model="form.priorite"
                type="radio"
                name="priorite"
                :value="option.value"
                class="sr-only"
              />
              <div class="flex flex-1 flex-col items-center justify-center">
                <span class="block text-sm font-bold text-slate-800">
                  {{ option.label }}
                </span>
              </div>
            </label>
          </div>
        </div>

        <!-- Description -->
        <div class="space-y-1.5">
          <label
            for="description"
            class="block text-xs font-bold text-slate-500 uppercase tracking-wider"
          >
            Description détaillée *
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            required
            placeholder="Décrivez le problème en détail pour aider à sa résolution..."
            class="block w-full rounded-xl border border-slate-200 bg-slate-50/55 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
          ></textarea>
        </div>

        <!-- Lieu et Equipement -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div class="space-y-1.5">
            <label for="lieu" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
              Localisation
            </label>
            <input
              id="lieu"
              v-model="form.lieu"
              type="text"
              placeholder="Ex: Bureau 205, Bâtiment A"
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/55 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
            />
          </div>

          <div class="space-y-1.5">
            <label
              for="equipement"
              class="block text-xs font-bold text-slate-500 uppercase tracking-wider"
            >
              Équipement concerné
            </label>
            <input
              id="equipement"
              v-model="form.equipement"
              type="text"
              placeholder="Ex: PC-12345, Imprimante HP LaserJet"
              class="block w-full rounded-xl border border-slate-200 bg-slate-50/55 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500 focus:bg-white transition-all"
            />
          </div>
        </div>

        <!-- Images -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
            Images / Captures d'écran
          </label>
          <div
            class="flex justify-center px-6 pt-5 pb-6 border-2 border-slate-250 border-dashed rounded-2xl bg-slate-50/20 hover:bg-slate-50/60 transition-colors"
          >
            <div class="space-y-2 text-center">
              <PhotoIcon class="mx-auto h-12 w-12 text-slate-400" />
              <div class="flex text-sm text-slate-600 justify-center">
                <label
                  for="images"
                  class="relative cursor-pointer bg-transparent rounded-md font-semibold text-indigo-650 hover:text-indigo-800 focus-within:outline-none"
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
                <p class="pl-1 text-slate-500">ou glisser-déposer</p>
              </div>
              <p class="text-xs text-slate-400">PNG, JPG, GIF jusqu'à 10MB</p>
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
              class="relative rounded-xl overflow-hidden border border-slate-200 shadow-sm"
            >
              <img :src="preview" class="h-24 w-full object-cover" />
              <button
                type="button"
                @click="removeImage(index)"
                class="absolute top-1.5 right-1.5 bg-slate-900/60 text-white rounded-full p-1 hover:bg-slate-900 transition-colors"
              >
                <XMarkIcon class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3">
        <button
          type="button"
          @click="router.back()"
          class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-colors"
        >
          Annuler
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-indigo-600/10"
        >
          {{ loading ? "Envoi en cours..." : "Soumettre l'incident" }}
        </button>
      </div>
    </form>
  </div>
</template>
