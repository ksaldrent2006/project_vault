<?php

class PVDatabase {
    private static $conn = null;

    private static function connect() {
        global $servername, $database, $username, $password, $port;

        if (!isset($port) || $port === null) {
            $port = 3306;
        }

        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=$servername;port=$port;dbname=$database;charset=utf8mb4",
                    $username,
                    $password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                http_response_code(500);
                exit("Database connection failed.");
            }
        }
    }

    public static function getDataFromSQL($sql, $params = null) {
        self::connect();
        $stmt = self::$conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function executeSQL($sql, $params = null, $returnID = false) {
        self::connect();
        $stmt = self::$conn->prepare($sql);
        $stmt->execute($params);
        return $returnID ? self::$conn->lastInsertId() : true;
    }

    public static function startTransaction() {
        self::connect();
        self::$conn->beginTransaction();
    }

    public static function commitTransaction() {
        self::$conn->commit();
    }

    public static function rollbackTransaction() {
        self::$conn->rollBack();
    }
}