<?php

/**
 * Permet de faire fonctionner le traçage.
 */
declare(ticks=1);

/**
 * Tracer - Un outil de traçage d'exécution "fait maison".
 *
 * Permet de générer un arbre d'appels de fonctions/méthodes pour une requête donnée,
 * avec filtrage et visualisation des arguments.
 */
class Tracer
{
    private static string $logFile;
    private static array $callStack = [];
    private static float $startTime;
    private static array $config = [];

    /**
     * Démarre le traçage.
     *
     * @param string $logFilePath Chemin vers le fichier de log.
     * @param array $config Options de configuration (ex: ['include_namespaces' => ['App\\']])
     */
    public static function start(string $logFilePath = 'trace.log', array $config = []): void
    {
        self::$logFile = $logFilePath;
        self::$startTime = microtime(true);
        self::$config = $config;
        
        // On s'assure que le fichier de log est vide pour cette nouvelle trace.
        file_put_contents(self::$logFile, ''); 
        
        // La magie : on enregistre notre "espion" qui sera appelé à chaque instruction.
        register_tick_function([self::class, 'tickHandler']);
    }

    /**
     * Arrête le traçage et écrit le résumé.
     */
    public static function stop(): void
    {
        unregister_tick_function([self::class, 'tickHandler']);
        $duration = microtime(true) - self::$startTime;
        self::log(sprintf("\n--- TRACE TERMINEE EN %.4f secondes ---", $duration));
    }

    /**
     * Le "Handler" appelé par PHP à chaque instruction (tick).
     * C'est le cœur du traceur.
     */
    public static function tickHandler(): void
    {
        // On récupère les informations de l'appelant (limite de 2 pour la performance).
        $backtrace = debug_backtrace(0, 2);
        
        // Si la pile d'appels est vide, on ne fait rien.
        if (!isset($backtrace[1])) {
            return;
        }

        $caller = $backtrace[1];
        $isMethod = isset($caller['class']);
        
        // --- FILTRAGE ---
        // On ne trace que ce qui nous intéresse pour éviter le bruit.
        if ($isMethod) {
            // Cas 1: C'est une méthode de classe (OOP)
            // On filtre par namespace si la configuration est fournie.
            if (isset(self::$config['include_namespaces'])) {
                $isIncluded = false;
                foreach (self::$config['include_namespaces'] as $ns) {
                    if (strpos($caller['class'], $ns) === 0) {
                        $isIncluded = true;
                        break;
                    }
                }
                if (!$isIncluded) { return; }
            }
        } else {
            // Cas 2: C'est une fonction (procédural)
            // On peut filtrer par nom de fichier si besoin.
            if (isset(self::$config['include_files'])) {
                $isFileIncluded = false;
                foreach (self::$config['include_files'] as $file) {
                    if (strpos($caller['file'], $file) !== false) {
                        $isFileIncluded = true;
                        break;
                    }
                }
                if (!$isFileIncluded) { return; }
            }
        }
        
        // --- CONSTRUCTION DE LA SIGNATURE DE L'APPEL ---
        $classPart = $isMethod ? $caller['class'] . $caller['type'] : '';
        
        // On évite de se tracer nous-mêmes, ce qui créerait une boucle infinie.
        if ($classPart === 'Tracer::') {
            return;
        }
        
        // On formate les arguments pour qu'ils soient lisibles.
        $argString = self::formatArgs($caller['args'] ?? []);
        $callSignature = $classPart . $caller['function'] . '(' . $argString . ')';
        
        // On logue seulement les NOUVEAUX appels, pas chaque instruction DANS la fonction.
        if (empty(self::$callStack) || end(self::$callStack) !== $callSignature) {
            self::$callStack[] = $callSignature;
            $indent = str_repeat('  ', count(self::$callStack) - 1);
            self::log($indent . '-> ' . $callSignature);
        }
    }

    /**
     * Formate les arguments d'une fonction pour un affichage concis.
     */
    private static function formatArgs(array $args): string
    {
        if (empty($args)) {
            return '';
        }

        $formatted = [];
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $formatted[] = 'object(' . get_class($arg) . ')';
            } elseif (is_array($arg)) {
                $formatted[] = 'array[' . count($arg) . ']';
            } elseif (is_string($arg)) {
                $str = strlen($arg) > 40 ? substr($arg, 0, 37) . '...' : $arg;
                $formatted[] = '"' . addslashes($str) . '"';
            } elseif (is_bool($arg)) {
                $formatted[] = $arg ? 'true' : 'false';
            } elseif (is_null($arg)) {
                $formatted[] = 'null';
            } else {
                $formatted[] = (string) $arg;
            }
        }

        return implode(', ', $formatted);
    }
    
    /**
     * Écrit un message dans le fichier de log.
     */
    private static function log(string $message): void
    {
        file_put_contents(self::$logFile, $message . PHP_EOL, FILE_APPEND);
    }
}