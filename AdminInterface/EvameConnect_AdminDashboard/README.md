<div align="center">
<img src="public/images/logo.jpg" width="200" alt="ASHUM CARE Logo">

# 🏥 ASHUM — CARE · Dashboard Web

**Interface d'administration de la plateforme digitale d'assistance infirmière et de soins à domicile**

[![Status](https://img.shields.io/badge/status-en%20développement-yellow)](.)
[![License](https://img.shields.io/badge/license-MIT-blue)](./LICENSE)
[![Version](https://img.shields.io/badge/version-0.1.0-informational)](.)
[![Vue](https://img.shields.io/badge/Vue-3-42b883)](.)
[![Vuetify](https://img.shields.io/badge/Vuetify-3-1867c0)](.)

_Transparence · Traçabilité · Continuité des soins_

</div>

---

## 📋 Table des matières

- [À propos](#-à-propos)
- [Fonctionnalités](#-fonctionnalités)
- [Stack technique](#-stack-technique)
- [Démarrage rapide](#-démarrage-rapide)
- [Structure du projet](#-structure-du-projet)
- [Variables d'environnement](#-variables-denvironnement)
- [Sécurité](#-sécurité)
- [Contribuer](#-contribuer)

---

## 🎯 À propos

Ce dépôt contient le **dashboard web d'administration** de la plateforme **ASHUM-CARE**, qui connecte des infirmiers certifiés avec des patients à domicile ou en milieu hospitalier.

Le dashboard permet aux administrateurs de superviser et gérer l'ensemble de la plateforme : catalogue de services, catégories de soins, utilisateurs et infirmiers.

> **Contexte** : ASHUM-CARE est pensée pour les familles éloignées (diaspora) qui souhaitent déléguer la prise en charge médicale d'un proche tout en conservant une visibilité totale et en temps réel sur chaque intervention.

---

## ✨ Fonctionnalités

### Gestion du catalogue

- 🗂️ **Catégories de soins** — Création, édition, activation/désactivation (avec génération automatique du slug)
- 🩺 **Services** — Ajout et suppression de prestations avec prix de base et catégorie associée

### Gestion des utilisateurs

- 👥 **Liste des utilisateurs** — Vue d'ensemble avec rôle (Acteur de vie, Infirmier, Admin) et statut de vérification
- 🔍 Recherche en temps réel sur tous les tableaux

### Workflow métier (backend)

1. **Commande** : L'acteur de vie commande un soin pour son proche.
2. **Matching** : Le système assigne un infirmier certifié à proximité.
3. **Terrain** : L'infirmier géo-valide son arrivée (rayon ≤ 20 m).
4. **Suivi** : Les constantes vitales sont transmises en temps réel.
5. **Clôture** : Rapport généré, infirmier payé.

### Catalogue de prestations

| Catégorie | Exemples |
|---|---|
| 🏨 Accompagnement hospitalier | Transport domicile ↔ hôpital, assistance administrative |
| 💉 Soins infirmiers techniques | Injections, pansements, perfusions, éducation thérapeutique |
| 🛁 Soins de base & Hygiène | Toilette, aide au lever/coucher, aide à la prise de médicaments |

---

## 🏗️ Stack technique

| Technologie | Rôle |
|---|---|
| **Vue 3** | Framework JavaScript |
| **Vuetify 3** | Composants UI Material Design |
| **Vite** | Bundler & serveur de développement |
| **Pinia** | Gestion d'état |
| **Vue Router 4** | Routing (file-based via unplugin-vue-router) |
| **VueUse** | Composables utilitaires (`useApi`, `useCookie`…) |
| **ofetch** | Requêtes HTTP vers l'API Laravel |

> Le dashboard communique avec le **backend Laravel 12** via une API REST sécurisée par Laravel Sanctum (token Bearer).

---

## 🚀 Démarrage rapide

### Prérequis

- Node.js 18+
- pnpm (recommandé) ou npm

### Installation

```bash
# 1. Cloner le projet
git clone <votre-repo-url>
cd ashum_care_web_dashboard

# 2. Installer les dépendances
pnpm install

# 3. Configurer l'environnement
cp .env.example .env
# → Renseigner VITE_API_BASE_URL (voir section Variables d'environnement)

# 4. Lancer le serveur de développement
pnpm dev
```

### Build de production

```bash
pnpm build
pnpm preview
```

---

## 📁 Structure du projet

```
src/
├── pages/
│   └── app/                  # Pages de l'application (routing file-based)
│       ├── services/         # Gestion des services
│       ├── categories/       # Gestion des catégories
│       └── users/            # Gestion des utilisateurs
├── navigation/
│   └── vertical/             # Configuration du drawer de navigation
│       └── index.js          # Entrées du menu principal
├── composables/
│   └── useApi.js             # Client HTTP (baseUrl + token Bearer)
├── layouts/                  # Layouts (default avec drawer, blank…)
├── components/               # Composants réutilisables
├── plugins/                  # Vuetify, Pinia, Router, i18n…
└── assets/                   # Styles & images
```

---

## ⚙️ Variables d'environnement

Copier `.env.example` en `.env` et renseigner les valeurs :

```env
# URL de base de l'API backend Laravel
VITE_API_BASE_URL=https://<votre-domaine>/api/v1
```

Le composable `useApi` utilise automatiquement cette variable pour toutes les requêtes, et y injecte le token Bearer depuis le cookie `accessToken`.

---

## 🔒 Sécurité

- Les requêtes API sont authentifiées via **token Bearer** (Laravel Sanctum).
- Le token est stocké dans un cookie `accessToken` et injecté automatiquement dans chaque requête.
- Aucune donnée sensible n'est stockée en clair dans le localStorage.

---

## 🤝 Contribuer

1. Créer une branche : `git checkout -b feature/nom-de-la-feature`
2. Développer et tester localement : `pnpm dev`
3. Builder pour vérifier : `pnpm build`
4. Ouvrir une Pull Request vers `main`.

---

<div align="center">

Fait avec ❤️ par l'équipe **ASHUM-CARE**

_Des soins dignes, traçables et accessibles pour tous._

</div>
