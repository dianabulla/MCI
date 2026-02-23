<?php
/**
 * Clase Database - Singleton para conexión a la base de datos
 * Proporciona una conexión PDO reutilizable
 */

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?self $instance = null;
    private PDO $connection;

    /**
     * Constructor privado - Singleton
     */
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ':' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            
            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            if (APP_DEBUG) {
                log_error('Conexión a BD exitosa');
            }
        } catch (PDOException $e) {
            log_error('Error de conexión a BD: ' . $e->getMessage());
            throw new PDOException('No se pudo conectar a la base de datos', 0, $e);
        }
    }

    /**
     * Obtiene la instancia única de la base de datos (Singleton)
     * 
     * @return Database
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     * 
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta preparada
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement {
        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            log_error('Error en query: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene un registro
     * 
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Obtiene múltiples registros
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Inserta un registro
     * 
     * @param string $table
     * @param array $data
     * @return int ID del registro insertado
     */
    public function insert(string $table, array $data): int {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Actualiza registros
     * 
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereParams
     * @return int Número de filas afectadas
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = implode(',', array_map(fn($key) => "{$key}=?", array_keys($data)));
        $values = array_merge(array_values($data), $whereParams);
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        return $this->query($sql, $values)->rowCount();
    }

    /**
     * Elimina registros
     * 
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int Número de filas afectadas
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): void {
        $this->connection->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public function commit(): void {
        $this->connection->commit();
    }

    /**
     * Revierte una transacción
     */
    public function rollback(): void {
        $this->connection->rollBack();
    }

    /**
     * Previene clonar la instancia
     */
    private function __clone() {}

    /**
     * Previene deserializar la instancia
     */
    public function __wakeup() {
        throw new \Exception('No se puede deserializar una instancia de Database');
    }
}
