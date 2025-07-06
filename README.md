      
# PHP Simple Tracer

Un outil de traçage d'appels "fait maison" en pur PHP, sans dépendances, pour comprendre le flux d'exécution des applications PHP.

## Fonctionnalités

-   **Zéro Dépendance :** Fonctionne avec du PHP pur.
-   **Trace Automatique :** Pas besoin de modifier chaque méthode, il s'accroche à l'exécution de PHP.
-   **Vue en Arbre :** Affiche la pile d'appels de manière indentée et lisible.
-   **Arguments Visibles :** Montre les arguments passés aux fonctions/méthodes.
-   **Filtrage Simple :** Permet de se concentrer sur votre propre code (ex: le namespace `App\\`).

## Installation

1.  Copiez le fichier `src/Tracer.php` dans votre projet (par exemple, dans un dossier `src/Debug/`).
2.  Dans le point d'entrée de votre application (ex: `public/index.php`), ajoutez ces lignes **au tout début du fichier** :

```php
// public/index.php

// 1. Déclaration OBLIGATOIRE au tout début du script
declare(ticks=1);

// 2. Inclusion de la classe Tracer
require_once __DIR__ . '/../src/Debug/Tracer.php'; // Adaptez le chemin

// 3. Démarrage du traceur (uniquement en environnement de dev !)
if ($_SERVER['APP_ENV'] === 'dev') {
    Tracer::start(__DIR__ . '/../var/log/trace.log', [
        'include_namespaces' => ['App\\'] // Ne tracer que votre code
    ]);

    // 4. Arrêt propre du traceur, même en cas d'erreur
    register_shutdown_function(['Tracer', 'stop']);
}

// ... Reste de votre index.php

    