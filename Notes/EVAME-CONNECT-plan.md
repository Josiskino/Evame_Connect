# Plan complet — EVAME CONNECT (Test pratique de recrutement)

## Context

Le **Groupe EVAME SA** (distribution de motos, pièces de rechange, SAV, leasing) lance un test
pratique de recrutement « Développeur Front-end & Mobile ». La mission : concevoir **EVAME CONNECT**,
une plateforme **Web & Mobile** de gestion commerciale, SAV et leasing moto, pensée comme une
solution réellement utilisée par les employés EVAME (commerciaux, agents SAV, Direction).

Le cahier des charges (9 pages, dans `CaptureDemande/`, sous forme d'images WhatsApp) décrit
**6 modules fonctionnels**, des **livrables** précis (code source + rapport `.docx` en 11 sections)
et une **grille de notation sur 100 points**. **Échéance de remise : dimanche 21 juin 2026 à 23h59**
(dépôt SharePoint). Aujourd'hui = 15 juin → **6 jours effectifs**.

Ce document est le plan d'exécution exhaustif : il traduit *tout* ce qui est demandé en périmètre
technique, modèle de données, écrans, planning et mapping avec la notation.

> 📌 **Emplacement des plans** : ce plan (et tout futur plan) est stocké dans le dossier **`Notes/`**
> du projet (`Notes/EVAME-CONNECT-plan.md`). Une copie est aussi conservée dans `~/.claude/plans/`.

> 🎯 **Périmètre de CETTE session** : **création/initialisation des 3 projets uniquement
> (scaffolding), SANS écrire de code métier.** On génère les squelettes vides, la structure de
> dossiers et les dépendances de base. Le développement fonctionnel (modules 1→6) viendra ensuite.

### Stack technique retenue (validée avec l'utilisateur)
- **Backend / API** : **Laravel (dernière version — Laravel 13)** (API REST réelle, auth **Sanctum**,
  MySQL/SQLite, seeders) → `BackendLaravel/`
- **Front Web** (dashboard Direction + gestion commerciale + leasing) : **Vue 3 + Vuetify**
  à partir d'un **template Vuetify fourni par l'utilisateur** → nouveau dossier `FrontWeb/`
- **Mobile** (SAV + commercial terrain) : **Flutter (dernière version, gérée via FVM)** +
  **Riverpod** (état) + **Dio** (HTTP) + **GoRouter** (navigation) +
  freezed/json_serializable (modèles) → `FrontFlutter/`
- **Docs / rapport** : `.docx` + captures → `DocumentsSpécifiques/` ; plans & brouillons → `Notes/`

> ⚠️ Le template Vuetify doit être fourni par l'utilisateur avant le démarrage du front web (phase de dev).

---

## 0. Périmètre de cette session — création des projets (scaffolding uniquement)

Objectif immédiat : **initialiser les 3 projets vides** dans leurs dossiers respectifs + déposer le
plan dans `Notes/`. **Aucun code métier** (pas de migrations, modèles, écrans, endpoints) — juste
les squelettes générés par les outils officiels, prêts à recevoir le développement.

**Étapes concrètes :**
1. **Plan** → copier ce plan dans `Notes/EVAME-CONNECT-plan.md`.
2. **Backend Laravel 13** → dans `BackendLaravel/` :
   - Vérifier la dernière version dispo (Laravel 13) puis `composer create-project laravel/laravel .`
     (ou `laravel new`) — squelette par défaut, sans code ajouté.
   - (Init seulement — l'ajout de Sanctum/migrations/seeders se fera en phase de dev.)
3. **Mobile Flutter (via FVM)** → dans `FrontFlutter/` :
   - S'assurer que **FVM** est installé ; `fvm install 3.44.2` (dernière stable, vérifiée le 16/06/2026) ;
     `fvm use 3.44.2` ; `fvm flutter create .` — projet Flutter par défaut.
   - Version pinnée en exact dans `.fvmrc` pour la reproductibilité (Dart 3.12.2).
   - (Les dépendances Riverpod/Dio/GoRouter/freezed s'ajouteront en phase de dev.)
4. **Front Web Vue 3** → dossier `FrontWeb/` :
   - En attente du **template Vuetify fourni par l'utilisateur**. À défaut, on peut amorcer un projet
     Vue 3 + Vite vierge ; sinon on intègre directement le template fourni le moment venu.
5. Vérifier que chaque projet a bien été généré (arborescence présente) — voir §9.

> Tout ce qui suit (§1 à §10) décrit le projet **complet** à réaliser ensuite, et sert de référence
> pour les phases de développement.

---

# PARTIE II — Réarchitecture Backend (Clean Architecture + RBAC dynamique + temps réel)

## Contexte de la refonte

Une première version de l'API (controllers « gras », Requests/Resources à la racine, pas de
versioning, rôles en dur sur `users.role`) a été construite et testée. La Direction veut maintenant
une **base professionnelle, évolutive et optimisée** :
- séparation stricte des responsabilités (le controller **orchestre**, ne gère pas la logique métier) ;
- logique métier déléguée à des **Actions** (un cas d'usage = une classe), regroupées **par feature** ;
- **inversion de dépendance** via des **Repositories à interfaces** (DIP) ;
- **RBAC dynamique** avec **Spatie laravel-permission** : un **Super Admin** crée les rôles et
  attribue les permissions ; chaque **écran/vue = une permission** ;
- **temps réel (Pusher)** : quand le Super Admin **retire une vue à un utilisateur précis**, l'écran
  disparaît **en direct** chez cet utilisateur (canal privé) ;
- **format de réponse API unifié**, **gestion centralisée des erreurs**, **logs centralisés** ;
- **tout en V1** (routes, controllers, requests, resources, actions) ; réponses **toujours en
  Resource/Collection (DTO)** ; **apiResource** au maximum ; **requêtes optimisées**.

Choix validés : Clean Architecture **pragmatique** · broadcast **Pusher Channels (cloud)** ·
stock = **colonne `stock` sur moto + table `stock_movements`** · permissions **directes par
utilisateur** (retrait ciblé + broadcast).

Versions (Context7, Laravel 13) : Spatie v7 (`Gate::before` pour Super Admin, permissions directes,
cache `permission:cache-reset`) ; Broadcasting via `ShouldBroadcast` + `PrivateChannel('user.{id}')`.

## A. Arborescence cible (`app/`, organisée par feature, versionnée V1)

```
app/
├── Actions/                # Cas d'usage (logique métier), 1 classe = 1 action, par feature
│   ├── Auth/               LoginAction, LogoutAction, GetAuthUserAction
│   ├── Dashboard/          GetDashboardMetricsAction
│   ├── Moto/               ListMotosAction, ShowMotoAction
│   ├── Client/             ListClientsAction, CreateClientAction, ShowClientAction
│   ├── Vente/              ListVentesAction, CreateVenteAction, ShowVenteAction
│   ├── Leasing/            List/Create/Show ContratAction, RegisterPaiementAction, SimulateLeasingAction
│   ├── Intervention/       List/Create/Show/Update Action, AddCommentaireAction
│   ├── Stock/              RecordStockMovementAction, DecrementStockAction
│   └── Admin/              Role/* , Permission/* , UserAccess/{Grant,Revoke}PermissionAction (→ broadcast)
├── DTOs/                   Objets d'entrée immuables (readonly) par feature (LoginData, CreateVenteData…)
├── Repositories/
│   ├── Contracts/          Interfaces (ports) : MotoRepositoryInterface, VenteRepositoryInterface…
│   └── Eloquent/           Implémentations Eloquent : EloquentMotoRepository…
├── Http/
│   ├── Controllers/Api/V1/ Controllers FINS (orchestration) + Admin/ (RBAC)
│   ├── Requests/V1/        Form Requests par feature (Auth/, Client/, Vente/, Leasing/, Intervention/, Admin/)
│   └── Resources/V1/       Resources (DTO de sortie) + Collections custom si besoin
├── Support/                Utilitaires : ApiResponse, TokenManager (Sanctum), helpers répétitifs
├── Events/                 UserAccessUpdated (ShouldBroadcast → PrivateChannel('user.{id}'))
├── Exceptions/             BusinessException (+ exceptions métier dédiées)
└── Providers/              RepositoryServiceProvider (bindings DIP) ; AppServiceProvider (Gate::before)
routes/
├── api.php                 charge la V1
└── api/v1.php              routes V1 (apiResource), groupées par middleware de rôle/permission
```

**Flux d'une requête** : Route → Controller (V1) → `FormRequest` (validation) → `DTO::from($request)`
→ `Action::execute($dto)` → `Repository` (interface) → Eloquent → `Resource`/`Collection` →
`ApiResponse` (format unifié).

## B. Format de réponse unifié + erreurs + logs centralisés

- `app/Support/ApiResponse.php` : `success($data, $message, $code)` / `error($message, $code, $errors)`.
  Enveloppe constante : `{ "status": "success|error", "message": "...", "data": {...}, "errors": {...} }`.
  Les Resources sont passées dans `data` (jamais d'Eloquent brut renvoyé).
- **Gestion centralisée** dans `bootstrap/app.php` → `withExceptions(...)` : mapping
  `ValidationException`→422, `AuthenticationException`→401, `AuthorizationException`/permission→403,
  `ModelNotFoundException`/`NotFound`→404, `BusinessException`→422/409, `Throwable`→500, **toujours**
  au format `ApiResponse`.
- **Logs centralisés** : channel dédié dans `config/logging.php` (ex. `errors`, driver `daily`) ;
  le handler logge chaque exception non-validation via `Log::channel('errors')`.

## C. RBAC dynamique (Spatie laravel-permission v7)

- `composer require spatie/laravel-permission` ; publier migration + config ; `User` → trait `HasRoles`.
- **Rôles seedés** : `super-admin`, `manager`, `commercial`, `sav`. Le `users.role` actuel est **remplacé**
  par les rôles Spatie (migration de transition).
- **Permissions = écrans/vues** : `view.dashboard`, `view.catalogue`, `view.ventes`, `view.clients`,
  `view.leasing`, `view.interventions`, `view.admin` + permissions d'action (`vente.create`,
  `leasing.create`, `paiement.create`, `intervention.update`, `rbac.manage`…).
- **Super Admin** : `Gate::before(fn($u,$a) => $u->hasRole('super-admin') ? true : null)` (AppServiceProvider).
- **Admin RBAC (super-admin only)** : endpoints pour créer/éditer rôles, lister/assigner permissions,
  et **accorder/révoquer une permission directe à un utilisateur** (`givePermissionTo`/`revokePermissionTo`).
- Routes protégées par `permission:` (middleware Spatie) en plus du `role:` quand utile.
- Penser au **reset de cache** des permissions après mutation (API Spatie le fait automatiquement).

## D. Temps réel — retrait de vue en direct (Pusher)

- `composer require pusher/pusher-php-server` ; `BROADCAST_CONNECTION=pusher` + clés `.env` ;
  `routes/channels.php` autorise `user.{id}` (l'utilisateur ne peut écouter que SON canal).
- Évènement `App\Events\UserAccessUpdated implements ShouldBroadcast` →
  `broadcastOn(): [new PrivateChannel('user.'.$this->user->id)]` ; payload = **liste à jour des
  vues/permissions** de l'utilisateur.
- Déclenché par `Grant/RevokePermissionAction` (et changement de rôle). Côté front (Vue/Flutter) :
  laravel-echo + pusher-js écoutent `user.{id}` et masquent/affichent la vue **sans rechargement**.
- `GET /me` renvoie aussi la liste des permissions/vues → source de vérité au login.

## E. Stock motos (colonne + mouvements)

- `motos.stock` (quantité courante, lecture rapide) **+** table `stock_movements`
  (`moto_id`, `type` in/out, `quantite`, `motif`, `reference`, `user_id`).
- `RecordStockMovementAction` écrit le mouvement ; `DecrementStockAction` décrémente + journalise ;
  `CreateVenteAction` appelle `DecrementStockAction` (dans une transaction).

## F. Optimisation & performance

- **Eager loading** systématique dans les repositories (`with([...])`) pour éliminer les N+1.
- **Index DB** ciblés : `ventes.date_vente`, `interventions(statut, date_intervention)`,
  `contrat_leasings.statut`, `motos.modele`. Pagination partout.
- `select` ciblés ; limiter les accesseurs `$appends` ; cache court éventuel des métriques dashboard.
- Cache de permissions Spatie activé (par défaut).

## G. Étapes d'exécution (ordre)

1. **Dépendances** : Spatie permission + Pusher PHP server ; publier configs/migrations.
2. **Support** : `ApiResponse`, `TokenManager`, helpers ; channel de logs ; handler d'exceptions centralisé.
3. **RBAC** : `HasRoles` sur User, migration de transition (drop `users.role` → rôles Spatie),
   `Gate::before`, seeders (rôles + permissions-écrans + super-admin + 3 comptes métier).
4. **DIP** : interfaces `Repositories/Contracts/*` + implémentations `Eloquent/*` + `RepositoryServiceProvider`.
5. **DTOs** + **Actions** par feature (Auth, Dashboard, Moto, Client, Vente, Stock, Leasing, Intervention, Admin).
6. **Restructuration HTTP** : déplacer Controllers→`Api/V1/`, Requests→`Requests/V1/…`,
   Resources→`Resources/V1/` ; controllers réduits à l'orchestration (appellent Request + Action + ApiResponse).
7. **Temps réel** : `UserAccessUpdated`, `channels.php`, déclenchement dans les actions Grant/Revoke.
8. **Stock** : migration `stock_movements`, actions stock, branchement sur la vente.
9. **Routes V1** : `routes/api/v1.php` avec `apiResource` + middlewares `role:`/`permission:`.
10. **Migration & seed** + tests end-to-end (cf. Vérification).

## H. Vérification (end-to-end)

- `php artisan migrate:fresh --seed` (PHP 8.5) → rôles/permissions créés, super-admin + 3 comptes.
- **Format unifié** : toute réponse = `{status,message,data}` ; erreurs 401/403/404/422/500 normalisées + loggées dans `storage/logs`.
- **RBAC** : un manager sans `view.dashboard` reçoit 403 ; super-admin passe partout (`Gate::before`).
- **Temps réel** : révoquer `view.dashboard` à un user → un évènement Pusher arrive sur `user.{id}`
  (vérifiable via debug console Pusher / log) avec la nouvelle liste de vues.
- **Stock** : une vente crée un `stock_movement` (out) et décrémente `motos.stock`.
- **Métier** : calculs leasing inchangés (contrat KOFFI = 360 000 / 56 % / à jour) ; simulation 180j×2000.
- **Perf** : `DB::listen` / Laravel Debugbar pour confirmer l'absence de N+1 sur les listes.

---

## 1. Décomposition fonctionnelle (les 6 modules — rien omis)

### Rôles / profils utilisateurs
- **Commercial** : menu = Mes clients, Nouvelle vente, Catalogue motos.
- **Agent SAV (technicien)** : menu = Interventions du jour (mobile).
- **Manager / Direction** : menu = Tableau de bord, Statistiques, Reporting.

### MODULE 1 — Connexion & espace utilisateur
- Interface d'authentification : connexion sécurisée, **message d'erreur**, **état de chargement**,
  **redirection vers l'espace personnel** selon le rôle.
- Après connexion, afficher : **nom**, **rôle**, **menu personnalisé**, **actions disponibles**.
- Menu conditionné par le rôle (cf. ci-dessus).

### MODULE 2 — Tableau de bord intelligent Direction
Objectif : « comprendre la situation en moins d'1 minute ». Visualisations = cartes + graphiques + tableaux dynamiques.
- **Activité commerciale** — cartes indicateurs : chiffre d'affaires total ; nombre de ventes ; évolution mensuelle.
- **Stock motos** : motos disponibles ; motos vendues ; **alertes stock faible**.
- **Leasing** : contrats actifs ; encaissements ; clients en retard.

### MODULE 3 — Gestion commerciale terrain
Parcours complet : (1) rechercher la moto → (2) vérifier la disponibilité → (3) enregistrer le client → (4) lancer la vente.
- **Catalogue motos** : image, modèle, couleur, prix, disponibilité + **recherche rapide**, **filtre**, **affichage détail**.
- **Création vente** : sélection client + moto + **mode d'achat** (achat direct / leasing) ; à la validation → **résumé clair de l'opération**.

### MODULE 4 — Gestion Leasing moto
- **Création contrat** : client, moto, date début, durée (jours), **montant journalier de référence**, **montant total**.
  - Exemple normatif du sujet : KOFFI Mensah / EVAME 125 CC / début 01/07/2026 / 180 jours / 2 000 FCFA/jour / total 360 000 FCFA.
- **Fréquence de paiement** choisie par l'utilisateur, **calculée automatiquement** :
  - Journalier : 2 000 FCFA/jour ; Hebdomadaire : montant semaine (auto) ; Mensuel : montant sur 6 mois (auto).
- **Suivi client leasing** (fiche) : Client, Moto, Montant contrat, Payé, Reste, **Progression %**, statut **« À jour » / « En retard »** affiché clairement.
  - Exemple : payé 200 000, reste 160 000, progression 56 %.

### MODULE 5 — Application mobile SAV (Flutter)
- Vue technicien : interventions du jour, infos client, actions à réaliser.
- **Liste interventions** + **fiche intervention** (Client, Moto, Problème, Statut).
- **Statuts** : Nouvelle / En traitement / Terminée.
- Le technicien peut : **ouvrir une intervention**, **ajouter un commentaire**, **changer le statut**.

### MODULE 6 — Qualité UX attendue (transverse, fil rouge)
- Ergonomie : simple, agréable, cohérente, rapide à comprendre.
- **Responsive** : ordinateur, tablette, téléphone.
- Expérience : opération rapide même avec peu de formation.
- Données & API : organisation des données, **gestion des chargements**, **gestion des erreurs**.

---

## 2. Modèle de données (Laravel — migrations & seeders)

| Table | Champs clés |
|---|---|
| `users` | id, name, email, password, **role** (`commercial`/`sav`/`manager`) |
| `clients` | id, nom, téléphone, email, adresse |
| `motos` | id, modèle, couleur, prix, image_url, **stock** (qté), cylindrée |
| `ventes` | id, client_id, moto_id, user_id, **mode** (`direct`/`leasing`), montant, date, statut |
| `contrats_leasing` | id, vente_id/client_id, moto_id, date_debut, **duree_jours**, **montant_journalier**, **montant_total**, **frequence** (`journalier`/`hebdo`/`mensuel`) |
| `paiements` | id, contrat_id, montant, date |
| `interventions` | id, client_id, moto_id, technicien_id, **probleme**, **statut** (`nouvelle`/`en_traitement`/`terminee`), date |
| `commentaires` | id, intervention_id, user_id, texte, date |

**Logique métier dérivée** (services Laravel) :
- Dashboard : agrégats CA, nb ventes, évolution mensuelle, stock dispo/vendu, alertes seuil, contrats actifs, encaissements, retards.
- Leasing : calcul `montant_total = duree_jours × montant_journalier`, montants hebdo/mensuel, `payé`/`reste`/`progression %`, statut à jour/en retard (selon échéancier vs paiements).
- Seeders : jeu de données réaliste (motos avec images, clients dont KOFFI Mensah, ventes, 1 contrat leasing exemple, interventions des 3 statuts) + **3 comptes de test** (un par rôle).

---

## 3. API REST (Laravel + Sanctum)

| Méthode | Endpoint | Usage |
|---|---|---|
| POST | `/api/login` · `/api/logout` · GET `/api/me` | Auth, profil + rôle (Module 1) |
| GET | `/api/dashboard` | Tous les indicateurs Direction (Module 2) |
| GET | `/api/motos` (`?search=&filtre=`) · `/api/motos/{id}` | Catalogue + détail (Module 3) |
| GET/POST | `/api/clients` | Enregistrer / lister clients (Module 3) |
| POST | `/api/ventes` | Lancer vente direct/leasing + résumé (Module 3) |
| GET/POST | `/api/leasing` · GET `/api/leasing/{id}` | Contrats + fiche suivi (Module 4) |
| POST | `/api/leasing/{id}/paiements` | Enregistrer paiement (Module 4) |
| GET | `/api/interventions` (`?date=today`) | Interventions du jour (Module 5) |
| POST | `/api/interventions` · PATCH `/api/interventions/{id}` | Créer / changer statut (Module 5) |
| POST | `/api/interventions/{id}/commentaires` | Ajouter commentaire (Module 5) |

Transverse : middleware rôle, **Form Requests** (validation → erreurs propres pour Module 6), **API Resources** (format JSON cohérent), CORS, pagination catalogue.

---

## 4. Front Web — Vue 3 + Vuetify (template fourni)

Intégration du template Vuetify fourni ; structure : `services/api` (Axios + intercepteurs token/erreurs),
store **Pinia** (auth + état), router avec **guards par rôle**, layout (menu personnalisé selon rôle).

**Écrans :**
1. **Login** — formulaire, loader, message d'erreur, redirection par rôle (Module 1).
2. **Dashboard Direction** — cartes indicateurs, graphiques (ECharts/ApexCharts ou composant du template), tableaux dynamiques, alertes stock/retard (Module 2).
3. **Catalogue motos** — grille avec image/modèle/couleur/prix/dispo, recherche rapide, filtres, page détail (Module 3).
4. **Nouvelle vente** — wizard : choix client (ou création) → choix moto → mode (direct/leasing) → résumé/validation (Module 3).
5. **Leasing** — liste contrats, création contrat (avec calculs auto temps réel des montants), **fiche suivi** (payé/reste/progression %/statut) (Module 4).
6. **Mes clients** — liste/recherche (Module 3).

Transverse : states de chargement (skeletons), gestion d'erreurs (snackbars), **responsive** desktop/tablette (Module 6).

---

## 5. Mobile — Flutter (Riverpod + Dio + GoRouter)

Architecture par features : `core/` (Dio + intercepteurs, thème, config), `features/auth`, `features/sav`,
`features/commercial` (optionnel mobile) ; modèles `freezed` + `json_serializable` ; providers Riverpod ;
routes GoRouter avec redirection auth/rôle.

**Écrans :**
1. **Login** mobile (Module 1) — même API, état/erreur/redirection.
2. **SAV — Interventions du jour** : liste (statut coloré), filtre du jour (Module 5).
3. **SAV — Fiche intervention** : client, moto, problème, statut ; ouvrir, **ajouter commentaire**, **changer statut** (Nouvelle/En traitement/Terminée) (Module 5).
4. **(Optionnel) Commercial mobile** : catalogue + nouvelle vente en version mobile (Module 3, bonus terrain).

Transverse : loaders, gestion erreurs (snackbars/retry), **responsive** téléphone/tablette (Module 6).

---

## 6. Planning sur 6 jours (15 → 21 juin)

| Jour | Objectif |
|---|---|
| **J1 (15-16)** | Cadrage, modèle de données, scaffolding **Laravel** + Sanctum + migrations + **seeders** (données + 3 comptes) ; backlog Agile (user stories). |
| **J2 (17)** | Endpoints API : auth, **dashboard**, motos (recherche/filtre), clients, ventes ; Form Requests + Resources ; tests Postman/Tinker. |
| **J3 (18)** | Endpoints **leasing** (calculs auto, paiements) + **SAV** ; intégration **template Vuetify**, login web + dashboard Direction. |
| **J4 (19)** | Web : **catalogue**, **nouvelle vente** (wizard), **leasing** (création + fiche suivi), responsive. |
| **J5 (20)** | **Flutter** : auth, SAV (liste/fiche/commentaire/statut), (commercial optionnel) ; intégration API. |
| **J6 (21)** | Polish UX, responsive (3 tailles), **tests**, **captures d'écran**, rédaction **rapport `.docx`** + **README**, dépôt SharePoint avant 23h59. |

---

## 7. Livrables (exactement ce qui est demandé)

### A. Code source (dépôt SharePoint)
- Tous les fichiers de fonctionnement (Laravel + Vue + Flutter).
- Fichiers de **configuration** nécessaires (.env.example pour chaque app).
- **Données de test** (seeders + éventuels fichiers).
- **README** : prérequis, installation environnement, dépendances, **commandes d'installation & de lancement** (les 3 apps), **comptes de test par rôle**.
- Code organisé, lisible, commenté si nécessaire, structuré pour évolutions futures.

### B. Rapport technique `.docx` — 11 sections imposées
1. **Présentation générale** : contexte, compréhension du besoin, objectifs, principaux écrans/modules, parcours couverts, fonctionnalités complémentaires.
2. **Environnement technique & choix technologiques** : langages/frameworks, outils, librairies UI, gestion d'état, communication API, services externes — chaque choix **justifié** (maintenabilité, performance, UX, évolutivité).
3. **Architecture Front-end / Mobile** : architecture globale, organisation des dossiers, structuration composants, séparation interfaces/composants réutilisables/services accès données/gestion états/règles d'affichage (+ schémas).
4. **Méthodologie de conception & approche Agile** : analyse besoins, profils, parcours, découpage en modules, **backlog produit**, user stories, dev par itérations, choix ergonomiques + diagrammes (cas d'utilisation, user flow, maquettes, workflows, diagramme front/mobile, séquence).
5. **Design, ergonomie & UX/UI** : logique de navigation, organisation des menus, charte graphique, composants, adaptation aux écrans ; comment l'UI permet prise en main rapide, moins d'actions, lisibilité mobile/desktop (+ maquettes avant/après).
6. **Gestion des données & intégration API** : récupération, structure, appels (réels), chargements, erreurs, synchronisation.
7. **Guide d'installation & de lancement** : prérequis, install env, dépendances, commandes install/lancement, comptes de test (repris dans README).
8. **Présentation fonctionnelle avec captures d'écran** : connexion, dashboard, navigation, gestion commerciale, catalogue, leasing, suivi SAV, vues mobiles, graphiques/indicateurs — chaque capture décrite.
9. **Tests réalisés** : scénarios utilisateurs, affichage multi-écrans, navigation, consommation données, résultats, limites identifiées.
10. **Code source** : rappel contenu/organisation du dépôt.
11. **Informations complémentaires** : améliorations futures, optimisations, fonctionnalités sup., difficultés, choix particuliers.

---

## 8. Mapping notation (100 pts) → où chaque point se gagne

| Critère | Pts | Couvert par |
|---|---|---|
| Qualité UX/UI et ergonomie | 25 | Template Vuetify soigné, Flutter cohérent, loaders/erreurs, parcours fluides (Modules 3-6) |
| Architecture Front-end / Mobile | 20 | Vue (Pinia/services/guards) + Flutter (Riverpod/features/freezed) propres + schémas rapport §3 |
| Responsive design & expérience mobile | 15 | Vuetify responsive (desktop/tablette) + Flutter (tél/tablette), tests 3 tailles |
| Gestion des données & intégration API | 15 | API Laravel réelle, Resources, chargements/erreurs, synchro (Module 6, rapport §6) |
| Dashboard & visualisation indicateurs | 10 | Module 2 complet (cartes + graphes + tableaux + alertes) |
| Qualité du code & maintenabilité | 10 | Conventions, organisation dossiers, commentaires, README |
| Documentation & présentation projet | 5 | Rapport `.docx` 11 sections + captures |

---

## 9. Vérification (comment valider en fin de projet)

- **Backend** : `php artisan migrate --seed` puis tester chaque endpoint (Postman/Tinker) ; auth Sanctum OK ; calculs leasing conformes à l'exemple (360 000 / 56 %).
- **Web** : `npm run dev`, login des 3 rôles → menus différents ; dashboard < 1 min de lecture ; parcours vente complet ; création + suivi leasing ; responsive (DevTools desktop/tablette).
- **Mobile** : `flutter run`, login SAV → interventions du jour → ouvrir/commenter/changer statut ; responsive tél/tablette.
- **Livrables** : README permet install + lancement des 3 apps par un évaluateur ; rapport `.docx` couvre les 11 sections ; captures présentes ; dépôt SharePoint avant **21 juin 23h59**.

---

## 10. Risques & dépendances

- **Bloquant** : le **template Vuetify** doit être fourni par l'utilisateur avant J3.
- Délai serré (6 j, 3 apps) → prioriser : Modules 1-2-3-4 (web) + 1-5 (mobile) ; commercial mobile = bonus.
- API réelle = plus de robustesse mais plus de temps → seeders solides dès J1 pour débloquer les fronts.
- Prévoir tôt les **captures d'écran** et le **rapport** (J6 chargé) — capturer au fil de l'eau dès J3.
