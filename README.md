# Fiche de temps

Application web de gestion des fiches de temps pour développeurs. Saisie quotidienne des tâches, export Excel, et génération automatique des tâches depuis les commits Git via l'IA.

## Fonctionnalités

- **Fiches de temps** — Créer et gérer des fiches par période (ex. 20 avril → 19 mai)
- **Saisie quotidienne** — Sélectionner un jour dans le calendrier et renseigner les tâches réalisées
- **Git → Fiche** — Choisir un dépôt local, sélectionner une date : les commits sont envoyés à Claude (Anthropic) qui génère une liste de tâches professionnelles en français, prêtes à être injectées dans la fiche
- **Vue cellule Excel** — Afficher les tâches d'un jour formatées pour coller directement dans une cellule Excel
- **Export Excel** — Exporter une fiche complète au format `.xlsx` avec mise en forme (largeurs de colonnes, retours à la ligne, en-tête stylisé)
- **Thème clair / sombre** — Bascule intégrée avec détection des préférences système

## Stack

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Vue 3, TypeScript, Inertia.js |
| Style | Tailwind CSS |
| Base de données | SQLite |
| Export | Maatwebsite Excel (PhpSpreadsheet) |
| IA | Anthropic Claude API |

## Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- [Laravel Herd](https://herd.laravel.com/) ou tout autre serveur PHP local
- Git installé et accessible dans le PATH
- Une clé API Anthropic (pour la fonctionnalité Git → Fiche)

## Installation

```bash
git clone https://github.com/<votre-username>/fiche-temps.git
cd fiche-temps

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configurer `.env` :

```env
APP_URL=http://fiche-temps.test

ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-sonnet-4-5
```

Créer la base et lancer :

```bash
php artisan migrate
composer dev        # Lance Laravel + queue + Vite en parallèle
```

## Utilisation

### Créer une fiche
1. Se connecter, puis cliquer sur **Nouvelle fiche**
2. Renseigner le projet, la business unit et la période
3. Cliquer sur un jour dans le calendrier pour ouvrir le panneau de saisie

### Générer les tâches depuis Git
1. Dans le panneau d'un jour, cliquer sur **Git** dans la barre des tâches
2. Choisir le dépôt parmi les projets détectés dans le répertoire parent
3. Cliquer sur **Générer les tâches** — les tâches sont injectées directement dans les inputs

### Copier pour Excel
1. Cliquer sur **Excel** dans la barre des tâches
2. Les tâches sont affichées en liste numérotée dans un champ texte
3. Double-cliquer dans une cellule Excel, puis coller (Ctrl+V)

### Exporter
Depuis la liste des fiches, cliquer sur **Exporter .xlsx** sur la fiche souhaitée.

## Licence

MIT
