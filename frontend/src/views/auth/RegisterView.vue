<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  service: "",
  telephone: "",
  matricule: "",
});
const loading = ref(false);
const errorMessage = ref("");

async function handleSubmit() {
  loading.value = true;
  errorMessage.value = "";

  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = "Les mots de passe ne correspondent pas";
    loading.value = false;
    return;
  }

  const success = await authStore.register(form.value);

  if (success) {
    router.push("/");
  } else {
    errorMessage.value = authStore.error || "Erreur lors de l'inscription";
  }

  loading.value = false;
}
</script>

<template>
  <div class="min-h-screen w-full flex bg-slate-50 font-sans">
    <!-- Left Section: Abstract Gradient Background (Visible on lg screens) -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-indigo-900 via-blue-900 to-indigo-800 flex-col justify-between p-12">
      <!-- Background Shapes -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-10 right-10 w-72 h-72 bg-blue-400 opacity-20 rounded-full blur-2xl"></div>
      
      <!-- Content -->
      <div class="relative z-10">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/30">
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <span class="text-white font-bold text-xl tracking-wide">IT Incident</span>
        </div>
      </div>
      
      <div class="relative z-10 mb-20">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight mb-6">
          Rejoignez la<br/> Plateforme
        </h1>
        <p class="text-indigo-200 text-lg max-w-lg leading-relaxed">
          Direction Générale du Trésor et de la Comptabilité Publique. Créez votre compte pour signaler et suivre vos incidents informatiques.
        </p>
      </div>
      
      <div class="relative z-10 text-indigo-300 text-sm">
        &copy; {{ new Date().getFullYear() }} DGTCP. Tous droits réservés.
      </div>
    </div>

    <!-- Right Section: Register Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative overflow-y-auto max-h-screen">
      <!-- Mobile Background Shapes -->
      <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-100 rounded-full blur-3xl opacity-50 -z-10 lg:hidden fixed"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-100 rounded-full blur-3xl opacity-50 -z-10 lg:hidden fixed"></div>

      <div class="w-full max-w-lg my-auto pt-8 pb-8">
        <!-- Mobile Header -->
        <div class="lg:hidden mb-10 text-center">
          <div class="mx-auto h-16 w-16 bg-gradient-to-br from-indigo-600 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200 mb-4">
            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </div>
          <h2 class="text-3xl font-extrabold text-slate-900">Inscription</h2>
          <p class="mt-2 text-slate-500">Créez votre compte agent</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white/80 backdrop-blur-xl sm:bg-white shadow-2xl shadow-indigo-100/50 rounded-3xl p-8 border border-white/50 lg:border-none">
          <div class="hidden lg:block mb-8">
            <h2 class="text-3xl font-bold text-slate-900 mb-2">Créer un compte</h2>
            <p class="text-slate-500">Remplissez les informations ci-dessous.</p>
          </div>

          <form class="space-y-5" @submit.prevent="handleSubmit">
            <!-- Error Message -->
            <div
              v-if="errorMessage"
              class="flex items-center gap-3 bg-red-50 text-red-600 p-4 rounded-xl text-sm border border-red-100 animate-fade-in"
            >
              <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ errorMessage }}</span>
            </div>

            <!-- Two-column grid for some fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Name -->
              <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom complet *</label>
                <input
                  id="name"
                  v-model="form.name"
                  name="name"
                  type="text"
                  required
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="Jean Dupont"
                />
              </div>

              <!-- Email -->
              <div class="md:col-span-2">
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                <input
                  id="email"
                  v-model="form.email"
                  name="email"
                  type="email"
                  autocomplete="email"
                  required
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="jean.dupont@tresor.gouv.ci"
                />
              </div>

              <!-- Matricule -->
              <div>
                <label for="matricule" class="block text-sm font-medium text-slate-700 mb-1">Matricule</label>
                <input
                  id="matricule"
                  v-model="form.matricule"
                  name="matricule"
                  type="text"
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="M123456"
                />
              </div>

              <!-- Telephone -->
              <div>
                <label for="telephone" class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
                <input
                  id="telephone"
                  v-model="form.telephone"
                  name="telephone"
                  type="tel"
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="0102030405"
                />
              </div>

              <!-- Service -->
              <div class="md:col-span-2">
                <label for="service" class="block text-sm font-medium text-slate-700 mb-1">Service / Département</label>
                <input
                  id="service"
                  v-model="form.service"
                  name="service"
                  type="text"
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="Direction Informatique"
                />
              </div>

              <!-- Password -->
              <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe *</label>
                <input
                  id="password"
                  v-model="form.password"
                  name="password"
                  type="password"
                  required
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="••••••••"
                />
              </div>

              <!-- Password Confirmation -->
              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmation *</label>
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  name="password_confirmation"
                  type="password"
                  required
                  class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 sm:text-sm"
                  placeholder="••••••••"
                />
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
              <button
                type="submit"
                :disabled="loading"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-indigo-200 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0"
              >
                <div v-if="loading" class="flex items-center gap-2">
                  <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Inscription en cours...</span>
                </div>
                <span v-else>S'inscrire</span>
              </button>
            </div>
            
            <div class="relative flex items-center py-2">
              <div class="flex-grow border-t border-slate-200"></div>
              <span class="flex-shrink-0 px-4 text-slate-400 text-sm">Ou</span>
              <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <!-- Login Link -->
            <div class="text-center">
              <p class="text-sm text-slate-600">
                Déjà un compte ?
                <RouterLink
                  to="/login"
                  class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors"
                >
                  Se connecter
                </RouterLink>
              </p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}
/* Custom scrollbar for webkit */
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: rgba(107, 114, 128, 0.8);
}
</style>
