# 🖥️ Application de Gestion des Incidents Informatiques

## Direction Générale du Trésor et de la Comptabilité Publique (DGTCP)

---

## 📋 Description du Projet

Application web permettant de suivre les incidents informatiques :

- Soumission d'incidents par les utilisateurs
- Affectation aux maintenanciers par le chef de service
- Résolution et validation avec double confirmation
- Génération de fiches d'intervention PDF
- Statistiques et restitutions sur période

---

## 🛠️ Technologies Utilisées

### Backend

- **Laravel 12** - Framework PHP
- **MySQL** - Base de données
- **Laravel Sanctum** - Authentification API
- **DomPDF** - Génération de PDF

### Frontend

- **Vue.js 3** - Framework JavaScript (Composition API)
- **TypeScript** - Typage statique
- **Pinia** - State management
- **Vue Router 4** - Routage
- **Tailwind CSS** - Styles
- **Heroicons** - Icônes
- **Axios** - Client HTTP

---

## 🚀 Installation

### Prérequis

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL >= 8.0

### 1. Configuration de la base de données

```bash
# Créer la base de données MySQL
mysql -u root -p
CREATE DATABASE gestion_incidents_dgtcp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
```

### 2. Installation du Backend (Laravel)

```bash
cd backend

# Installer les dépendances
composer install

# Copier le fichier d'environnement (si pas déjà fait)
cp .env.example .env

# Configurer la base de données dans .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=gestion_incidents_dgtcp
# DB_USERNAME=root
# DB_PASSWORD=

# Générer la clé d'application (si pas déjà fait)
php artisan key:generate

# Lancer les migrations
php artisan migrate

# Créer le lien symbolique pour le storage
php artisan storage:link

# Remplir la base avec des données de test
php artisan db:seed

# Lancer le serveur de développement
php artisan serve
```

Le backend sera accessible sur : `http://localhost:8000`

### 3. Installation du Frontend (Vue.js)

```bash
cd frontend

# Installer les dépendances
npm install

# Lancer le serveur de développement
npm run dev
```

Le frontend sera accessible sur : `http://localhost:5173`

---

## 👥 Comptes de Test

Après avoir exécuté `php artisan db:seed`, les comptes suivants sont disponibles :

| Rôle          | Email                     | Mot de passe |
| ------------- | ------------------------- | ------------ |
| Admin         | admin@dgtcp.ci            | password     |
| Chef Service  | chef.maintenance@dgtcp.ci | password     |
| Maintenancier | tech.reseau@dgtcp.ci      | password     |
| Maintenancier | tech.hardware@dgtcp.ci    | password     |
| Maintenancier | tech.logiciel@dgtcp.ci    | password     |
| Utilisateur   | jean.dupont@dgtcp.ci      | password     |
| Utilisateur   | marie.kouassi@dgtcp.ci    | password     |
| Utilisateur   | pierre.konan@dgtcp.ci     | password     |

---

## 📁 Structure du Projet

```
itincident/
├── backend/                    # API Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       ├── AuthController.php
│   │   │   │       ├── IncidentController.php
│   │   │   │       ├── UserController.php
│   │   │   │       └── ReportController.php
│   │   │   └── Middleware/
│   │   │       └── CheckRole.php
│   │   └── Models/
│   │       ├── User.php
│   │       ├── Incident.php
│   │       ├── IncidentImage.php
│   │       ├── IncidentAssignment.php
│   │       └── IncidentStatusHistory.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── resources/views/pdf/
│   │   ├── fiche-intervention.blade.php
│   │   └── liste-incidents.blade.php
│   └── routes/
│       └── api.php
│
├── frontend/                   # Application Vue.js
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   ├── layouts/
│   │   │   └── MainLayout.vue
│   │   ├── router/
│   │   │   └── index.ts
│   │   ├── services/
│   │   │   └── api.ts
│   │   ├── stores/
│   │   │   ├── auth.ts
│   │   │   └── incidents.ts
│   │   ├── types/
│   │   │   └── index.ts
│   │   └── views/
│   │       ├── auth/
│   │       ├── incidents/
│   │       ├── affectations/
│   │       ├── interventions/
│   │       ├── reports/
│   │       └── admin/
│   └── tailwind.config.js
│
└── PLANIFICATION_PROJET.md
```

---

## 🔄 Workflow des Incidents

```
OUVERT → AFFECTÉ → EN_COURS → RÉSOLU → EN_ATTENTE_VALIDATION → CLÔTURÉ
```

1. **OUVERT** : L'utilisateur crée un incident
2. **AFFECTÉ** : Le chef service affecte l'incident à un maintenancier
3. **EN_COURS** : Le maintenancier prend en charge l'incident
4. **RÉSOLU** : Le maintenancier marque l'incident comme résolu
5. **EN_ATTENTE_VALIDATION** : L'auteur doit valider la résolution
6. **CLÔTURÉ** : L'auteur valide et note la résolution (double validation)

---

## 📡 Endpoints API

### Authentification

- `POST /api/register` - Inscription
- `POST /api/login` - Connexion
- `POST /api/logout` - Déconnexion
- `GET /api/me` - Utilisateur connecté

### Incidents

- `GET /api/incidents` - Liste des incidents (filtrable)
- `GET /api/incidents/mes-incidents` - Mes incidents
- `POST /api/incidents` - Créer un incident
- `GET /api/incidents/{id}` - Détail d'un incident
- `PUT /api/incidents/{id}` - Modifier un incident
- `DELETE /api/incidents/{id}` - Supprimer un incident

### Actions sur incidents

- `POST /api/incidents/{id}/affecter` - Affecter (Chef Service)
- `POST /api/incidents/{id}/prendre-en-charge` - Prendre en charge (Maintenancier)
- `POST /api/incidents/{id}/resoudre` - Résoudre (Maintenancier)
- `POST /api/incidents/{id}/valider` - Valider/Clôturer (Auteur)
- `POST /api/incidents/{id}/rejeter` - Rejeter la résolution (Auteur)

### Utilisateurs (Admin)

- `GET /api/users` - Liste des utilisateurs
- `GET /api/users/maintenanciers` - Liste des maintenanciers
- `POST /api/users` - Créer un utilisateur
- `PUT /api/users/{id}` - Modifier un utilisateur

### Rapports

- `GET /api/reports/fiche-intervention/{id}` - Fiche d'intervention PDF
- `GET /api/reports/statistiques` - Statistiques sur période
- `GET /api/reports/export` - Export des incidents

---

## 🔧 Commandes Utiles

### Backend

```bash
# Réinitialiser la base de données
php artisan migrate:fresh --seed

# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Voir les routes API
php artisan route:list --path=api
```

### Frontend

```bash
# Build pour la production
npm run build

# Prévisualiser le build
npm run preview

# Linter
npm run lint
```

---

## 📝 Tâches Restantes

Voir le fichier `PLANIFICATION_PROJET.md` pour la liste complète des tâches.

### Priorités :

1. ✅ Configuration initiale Backend/Frontend
2. ⏳ Compléter les vues frontend (détail incident, affectations, statistiques)
3. ⏳ Ajouter les graphiques pour les statistiques
4. ⏳ Tests unitaires et d'intégration
5. ⏳ Documentation API (Swagger)
6. ⏳ Déploiement

---

## 📄 Licence

Projet développé pour la Direction Générale du Trésor et de la Comptabilité Publique par Romanuis et Oscar .

---
