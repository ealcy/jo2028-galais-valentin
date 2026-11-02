<?php
// Paramètres de connexion à la base de données
// (à adapter lors de la mise en ligne : hôte, nom de la base, utilisateur, mot de passe)
$host = "localhost";
$dbname = "galais_valentin_jo2028";
$username = "root";
$password = "root";

try {
    // Création d'une nouvelle connexion PDO
    $connexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            // Génère des exceptions en cas d'erreur SQL
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Résultats sous forme de tableaux associatifs
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Vraies requêtes préparées côté serveur (meilleure protection contre les injections SQL)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // On ne montre pas le détail technique au visiteur
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    http_response_code(500);
    exit("Erreur de connexion à la base de données. Vérifiez le fichier database/database.php.");
}

// Afficher les erreurs en PHP (utile en local, à désactiver en production)
error_reporting(E_ALL);
ini_set("display_errors", 1);
