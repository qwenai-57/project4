<?php

declare(strict_types=1);

/**
 * Database Connection Class - Singleton pattern for DB connection
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support\Database;

use PDO;
use PDOException;

class DB
{
    protected static ?DB $instance = null;
    protected PDO $connection;
    protected string $tablePrefix;

    /**
     * Constructor - private for singleton pattern
     *
     * @throws PDOException
     */
    private function __construct()
    {
        $config = $this->loadConfig();
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }

        $this->tablePrefix = $config['prefix'] ?? 'hos_';
    }

    /**
     * Get singleton instance
     *
     * @return DB
     */
    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning
     *
     * @return void
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     *
     * @return void
     */
    public function __wakeup()
    {
        throw new \LogicException("Cannot unserialize singleton");
    }

    /**
     * Get PDO connection
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Get table name with prefix
     *
     * @param string $table
     * @return string
     */
    public function getTableName(string $table): string
    {
        // If table already has prefix, don't add it again
        if (str_starts_with($table, $this->tablePrefix)) {
            return $table;
        }
        return $this->tablePrefix . $table;
    }

    /**
     * Start a query builder
     *
     * @param string $table
     * @return Query
     */
    public function table(string $table): Query
    {
        return new Query($this, $table);
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Check if in transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Execute raw SQL
     *
     * @param string $sql
     * @param array<string, mixed> $params
     * @return \PDOStatement|false
     */
    public function execute(string $sql, array $params = []): \PDOStatement|false
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get last insert ID
     *
     * @param string|null $name
     * @return string|false
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * Load database configuration
     *
     * @return array{host: string, port: int, database: string, username: string, password: string, prefix: string}
     */
    protected function loadConfig(): array
    {
        $configFile = dirname(__DIR__, 2) . '/config.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            return [
                'host' => $config['database']['host'] ?? 'localhost',
                'port' => $config['database']['port'] ?? 3306,
                'database' => $config['database']['database'] ?? 'project_komplace',
                'username' => $config['database']['username'] ?? 'root',
                'password' => $config['database']['password'] ?? '',
                'prefix' => $config['database']['prefix'] ?? 'hos_',
            ];
        }

        // Fallback to environment variables
        return [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_DATABASE') ?: 'project_komplace',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'prefix' => getenv('DB_PREFIX') ?: 'hos_',
        ];
    }
}
