# git-to-fiche — Guide d'installation

Ce petit outil lit vos commits Git **sur votre propre ordinateur** et les transforme en tâches prêtes à coller dans fiche-temps. Vos fichiers et vos dépôts Git ne quittent jamais votre machine : seuls les messages de commit (du texte) sont envoyés au serveur.

Ce guide s'adresse à quelqu'un qui n'a jamais utilisé ce script — suivez les étapes dans l'ordre.

---

## 1. Ce dont vous avez besoin avant de commencer

Vérifiez que ces deux outils sont installés sur votre machine.

**PHP** — ouvrez un terminal (PowerShell sous Windows, Terminal sous Mac/Linux) et tapez :

```
php -v
```

Si une version s'affiche (ex: `PHP 8.4.23`), c'est bon. Si vous avez une erreur du type "commande introuvable", installez PHP (si vous utilisez déjà Herd, Laragon ou XAMPP, PHP est déjà installé — assurez-vous juste qu'il est accessible depuis le terminal).

**Git** — dans le même terminal :

```
git -v
```

Si une version s'affiche, c'est bon. Sinon, installez Git depuis [git-scm.com](https://git-scm.com).

Vous n'avez besoin de **rien d'autre** : pas de Composer, pas de base de données, pas de serveur local.

---

## 2. Récupérer le script

Le script vit dans le même dépôt que l'application fiche-temps. Deux cas possibles :

**Si vous avez déjà fiche-temps cloné sur votre machine** (ex: vous êtes développeur sur ce projet) : vous avez déjà le dossier `scripts/git-to-fiche/`, passez à l'étape 3.

**Sinon**, clonez le dépôt à l'endroit de votre choix :

```
git clone <URL-du-dépôt-GitLab> fiche-temps
cd fiche-temps/scripts/git-to-fiche
```

Vous n'avez pas besoin d'installer les dépendances de l'application (`composer install`, `npm install`) — seul ce dossier `scripts/git-to-fiche/` est utilisé.

---

## 3. Créer votre fichier de configuration

Dans le dossier `scripts/git-to-fiche/`, dupliquez le fichier `config.example.php` et renommez la copie en `config.php`.

- **Windows (PowerShell)** :
  ```
  Copy-Item config.example.php config.php
  ```
- **Mac/Linux** :
  ```
  cp config.example.php config.php
  ```

Ouvrez `config.php` dans un éditeur de texte. Vous allez voir 3 valeurs à remplir :

```php
return [
    'sites_dir' => 'C:\\Herd\\Sites',
    'api_url'   => 'https://fiche-temps.entreprise.com',
    'token'     => '',
];
```

On remplit ces trois valeurs dans les étapes suivantes.

---

## 4. Renseigner `sites_dir`

C'est le dossier **sur votre machine** qui contient tous vos projets Git (celui qui contient un sous-dossier par projet, chacun avec un dossier `.git` caché dedans).

Exemples :
- Windows avec Herd : `C:\\Herd\\Sites`
- Mac/Linux : `/Users/vous/sites` ou `/home/vous/projets`

Remplacez la valeur dans `config.php` :

```php
'sites_dir' => 'C:\\Herd\\Sites',
```

> Astuce Windows : dans un chemin PHP, chaque `\` s'écrit `\\` (double antislash).

---

## 5. Renseigner `api_url`

C'est l'adresse de l'application fiche-temps telle que vous la tapez dans votre navigateur (sans slash à la fin) :

```php
'api_url' => 'https://fiche-temps.entreprise.com',
```

---

## 6. Générer votre jeton personnel et renseigner `token`

Le jeton, c'est votre "clé" personnelle qui autorise le script à parler au serveur en votre nom.

1. Connectez-vous à fiche-temps dans votre navigateur.
2. Allez dans **Paramètres → Application**.
3. Descendez jusqu'à la section **"Jeton API (script Git)"**.
4. Cliquez sur **"Générer un jeton"**.
5. Un jeton s'affiche (une longue chaîne de caractères commençant par un chiffre suivi de `|`). **Copiez-le tout de suite** — il ne sera plus jamais réaffiché ensuite.
6. Collez-le dans `config.php` :

```php
'token' => '1|abcdEFGH1234567890...',
```

7. Enregistrez le fichier `config.php`.

> Si vous perdez votre jeton ou pensez qu'il a fuité, retournez sur cette page et cliquez sur **"Régénérer un jeton"** — l'ancien sera immédiatement invalidé.

---

## 7. Vérifier que tout est prêt

Votre `config.php` doit maintenant ressembler à ceci (avec vos propres valeurs) :

```php
<?php

return [
    'sites_dir' => 'C:\\Herd\\Sites',
    'api_url'   => 'https://fiche-temps.entreprise.com',
    'token'     => '1|abcdEFGH1234567890...',
];
```

**Important** : ne partagez jamais ce fichier, ne le committez jamais dans Git — il contient votre clé personnelle (ce fichier est déjà exclu automatiquement du dépôt, vous n'avez rien à faire de spécial).

---

## 8. Utiliser le script

Dans le terminal, toujours dans le dossier `scripts/git-to-fiche/`, lancez :

```
php generate-tasks.php --project=nom-du-dossier --date=2026-08-06
```

- `--project` : le nom du sous-dossier du projet (celui dans `sites_dir`), par exemple `fiche-temps`.
- `--date` : la date pour laquelle vous voulez générer les tâches, au format `AAAA-MM-JJ`.

### Exemple concret

```
php generate-tasks.php --project=fiche-temps --date=2026-08-06
```

Résultat affiché :

```
1. Corriger un dysfonctionnement empêchant la connexion des utilisateurs
2. Ajouter la possibilité de filtrer les résultats par date

Résumé : Correction d'un problème de connexion et amélioration du filtrage par date.

(Copié dans le presse-papier — collez-le dans le panneau "Liste" de fiche-temps.)
```

## 9. Récupérer le résultat dans fiche-temps

1. Ouvrez fiche-temps, allez sur le jour concerné.
2. Cliquez sur le bouton **"Liste"** (saisie rapide) dans le panneau du jour.
3. Collez (Ctrl+V) — le texte est déjà copié automatiquement par le script.
4. Cliquez sur **"Importer les tâches"**.

C'est terminé — vos tâches du jour sont remplies.

---

## Dépannage

**"Config introuvable"**
Vous n'avez pas encore créé `config.php` — retournez à l'étape 3.

**"sites_dir invalide dans config.php"**
Le chemin renseigné n'existe pas sur votre machine, ou contient une erreur de frappe. Vérifiez-le dans l'explorateur de fichiers.

**"Projet introuvable ou n'est pas un dépôt Git"**
Le nom passé à `--project` ne correspond à aucun sous-dossier de `sites_dir`, ou ce dossier ne contient pas de `.git`. Vérifiez l'orthographe exacte du nom du dossier.

**"api_url ou token manquant dans config.php"**
Une des deux valeurs est restée vide — revenez aux étapes 5 et 6.

**"Erreur API : Unauthenticated." ou HTTP 401**
Votre jeton est incorrect, expiré, ou a été révoqué. Régénérez-en un nouveau (étape 6) et remplacez-le dans `config.php`.

**"Aucun commit trouvé pour ... le ..."**
Vous n'avez fait aucun commit sur ce projet à cette date — rien d'anormal, il n'y a simplement rien à générer.

**"Aucune tâche générée (commits filtrés comme non pertinents)"**
Tous vos commits de la journée ont été jugés non pertinents (fusions de branches, corrections de style, etc.) — rien à importer pour ce jour-là.

**La copie automatique dans le presse-papier ne fonctionne pas**
Elle ne fonctionne que sous Windows. Sur Mac/Linux, copiez le texte affiché dans le terminal manuellement.

**Erreur réseau**
Vérifiez que `api_url` est correct et que votre machine a accès à internet / au serveur de l'entreprise.

---

## Questions fréquentes

**Est-ce que ce script envoie mes fichiers ou mon code au serveur ?**
Non. Il envoie uniquement les messages de commit (les phrases que vous écrivez avec `git commit -m "..."`), jamais le contenu des fichiers ni leurs chemins.

**Dois-je relancer le script à chaque fois ?**
Oui, une fois par jour et par projet pour lequel vous voulez générer des tâches.

**Puis-je l'utiliser pour plusieurs projets le même jour ?**
Oui, relancez simplement la commande avec un `--project` différent à chaque fois.

**Que faire si je change d'ordinateur ?**
Répétez les étapes 2 à 7 sur le nouvel ordinateur. Votre jeton reste valable, vous pouvez le réutiliser (ou en générer un nouveau si vous préférez).
