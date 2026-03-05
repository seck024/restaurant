<?php
class Database {
    private static $host = "localhost"; // Vérifie que c'est bien "localhost"
    private static $dbname = "bts_project"; // Nom exact de ta base de données
    private static $username = "root"; // Nom d'utilisateur MySQL
    private static $password = ""; //
    private static $connection = null;

    public static function connect() {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8",
                    self::$username,
                    self::$password,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$connection;
    }
}



