# 📋 Planification du Projet - Gestion des Incidents Informatiques

## Direction Générale du Trésor et de la Comptabilité Publique

---

## 🎯 Objectif du Projet

Application de suivi des incidents informatiques permettant la soumission, l'affectation, la résolution et la validation des incidents.

---

## 👥 Rôles Utilisateurs

| Rôle              | Permissions                                                                                   |
| ----------------- | --------------------------------------------------------------------------------------------- |
| **UTILISATEUR**   | Créer incidents, Valider clôture (double validation), Noter la résolution                     |
| **MAINTENANCIER** | Voir incidents affectés, Résoudre incidents, Valider résolution                               |
| **CHEF_SERVICE**  | Voir tous les incidents, Affecter incidents, Générer fiches d'intervention, Voir restitutions |
| **ADMIN**         | Toutes les permissions + Gestion des utilisateurs                                             |

---

## 📊 Statuts des Incidents

```
OUVERT → AFFECTE → EN_COURS → RESOLU → EN_ATTENTE_VALIDATION → CLOTURE
```

---

## 🗂️ Structure de la Base de Données

### Tables principales :

1. **users** - Utilisateurs du système
2. **incidents** - Incidents déclarés
3. **incident_images** - Images associées aux incidents
4. **incident_assignments** - Affectations des incidents
5. **incident_validations** - Validations de clôture

---

## 📁 Organisation des Tâches

### PHASE 1 : BACKEND (Laravel) - Semaine 1-2

#### 1.1 Configuration initiale

- [ ] Créer le projet Laravel
- [ ] Configurer la connexion MySQL
- [ ] Installer Laravel Sanctum (API Auth)
- [ ] Configurer CORS pour Vue.js

#### 1.2 Base de données

- [ ] Migration `users` (ajout champs role, service)
- [ ] Migration `incidents`
- [ ] Migration `incident_images`
- [ ] Migration `incident_assignments`
- [ ] Migration `incident_validations`
- [ ] Seeders pour données de test

#### 1.3 Modèles Eloquent

- [ ] Model `User` avec relations
- [ ] Model `Incident` avec relations
- [ ] Model `IncidentImage`
- [ ] Model `IncidentAssignment`
- [ ] Model `IncidentValidation`

#### 1.4 API Controllers

- [ ] `AuthController` - Inscription, Connexion, Déconnexion
- [ ] `IncidentController` - CRUD incidents
- [ ] `IncidentImageController` - Upload/Suppression images
- [ ] `AssignmentController` - Affectation incidents
- [ ] `ValidationController` - Validation/Clôture
- [ ] `ReportController` - Fiches d'intervention & Restitutions

#### 1.5 Middleware & Policies

- [ ] Middleware vérification des rôles
- [ ] Policies pour autorisations par ressource

#### 1.6 Services

- [ ] `IncidentService` - Logique métier incidents
- [ ] `ReportService` - Génération PDF fiches d'intervention
- [ ] `StatisticsService` - Calculs pour restitutions

---

### PHASE 2 : FRONTEND (Vue.js) - Semaine 2-3

#### 2.1 Configuration initiale

- [ ] Créer projet Vue.js avec Vite
- [ ] Installer Vue Router
- [ ] Installer Pinia (state management)
- [ ] Installer Axios pour les requêtes API
- [ ] Configurer Tailwind CSS

#### 2.2 Authentification

- [ ] Page de connexion
- [ ] Page d'inscription
- [ ] Store Pinia pour l'authentification
- [ ] Guards de navigation (routes protégées)

#### 2.3 Module Incidents (Utilisateur)

- [ ] Formulaire de création d'incident
- [ ] Upload multiple d'images
- [ ] Liste des mes incidents
- [ ] Détail d'un incident
- [ ] Formulaire de validation/notation (clôture)

#### 2.4 Module Affectation (Chef Service)

- [ ] Liste de tous les incidents
- [ ] Interface d'affectation aux maintenanciers
- [ ] Génération fiche d'intervention (PDF)
- [ ] Dashboard restitutions/statistiques

#### 2.5 Module Résolution (Maintenancier)

- [ ] Liste des incidents affectés
- [ ] Interface de résolution
- [ ] Validation de la résolution

#### 2.6 Composants UI

- [ ] Layout principal avec sidebar
- [ ] Header avec notifications
- [ ] Tableaux de données avec pagination
- [ ] Modales de confirmation
- [ ] Alertes/Toasts
- [ ] Filtres par période/type/statut

---

### PHASE 3 : INTÉGRATION & TESTS - Semaine 4

#### 3.1 Tests Backend

- [ ] Tests unitaires des services
- [ ] Tests des API endpoints
- [ ] Tests des policies

#### 3.2 Tests Frontend

- [ ] Tests des composants
- [ ] Tests d'intégration

#### 3.3 Finalisation

- [ ] Documentation API
- [ ] Manuel utilisateur
- [ ] Déploiement

---

## 🛠️ Stack Technique

### Backend

- **Framework** : Laravel 11
- **Base de données** : MySQL 8
- **Auth API** : Laravel Sanctum
- **PDF** : DomPDF ou Snappy
- **Upload** : Laravel Storage

### Frontend

- **Framework** : Vue.js 3 (Composition API)
- **Build Tool** : Vite
- **State** : Pinia
- **Router** : Vue Router 4
- **HTTP** : Axios
- **CSS** : Tailwind CSS
- **UI** : HeadlessUI / PrimeVue
- **Charts** : Chart.js / ApexCharts

---

## 📐 Architecture des Dossiers

### Backend (Laravel)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── IncidentController.php
│   │   │   │   ├── AssignmentController.php
│   │   │   │   ├── ValidationController.php
│   │   │   │   └── ReportController.php
│   │   ├── Middleware/
│   │   │   └── CheckRole.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Incident.php
│   │   ├── IncidentImage.php
│   │   ├── IncidentAssignment.php
│   │   └── IncidentValidation.php
│   ├── Services/
│   │   ├── IncidentService.php
│   │   ├── ReportService.php
│   │   └── StatisticsService.php
│   └── Policies/
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

### Frontend (Vue.js)

```
frontend/
├── src/
│   ├── assets/
│   ├── components/
│   │   ├── common/
│   │   ├── incidents/
│   │   └── layout/
│   ├── composables/
│   ├── router/
│   ├── services/
│   │   └── api.js
│   ├── stores/
│   │   ├── auth.js
│   │   └── incidents.js
│   ├── views/
│   │   ├── auth/
│   │   ├── incidents/
│   │   ├── dashboard/
│   │   └── reports/
│   ├── App.vue
│   └── main.js
├── index.html
└── vite.config.js
```

---

## 📅 Planning Estimé

| Phase                         | Durée      | Dates estimées |
| ----------------------------- | ---------- | -------------- |
| Phase 1 - Backend             | 2 semaines | Semaine 1-2    |
| Phase 2 - Frontend            | 2 semaines | Semaine 2-3    |
| Phase 3 - Tests & Déploiement | 1 semaine  | Semaine 4      |

**Durée totale estimée : 4-5 semaines**

---

## 🚀 Commandes d'initialisation

### Backend

```bash
cd /home/roma/Desktop/nouveau\ dossier/itincident
composer create-project laravel/laravel backend
cd backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
composer require barryvdh/laravel-dompdf
```

### Frontend

```bash
cd /home/roma/Desktop/nouveau\ dossier/itincident
npm create vue@latest frontend
cd frontend
npm install
npm install axios pinia vue-router@4 @headlessui/vue @heroicons/vue
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```
