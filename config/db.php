<?php
/**
 * Database — PDO with prepared statements.
 * Tries MySQL first; if unavailable, uses SQLite file (zero-setup local run).
 */

declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'home_services');

/** 'auto' = try MySQL, then SQLite | 'mysql' | 'sqlite' */
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'auto');

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

define('WHATSAPP_BUSINESS_NUMBER', '9876543210');

/** @var 'mysql'|'sqlite' */
$GLOBALS['_db_kind'] = 'mysql';

/**
 * Shared PDO connection (singleton per request).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = DB_DRIVER;

    if ($driver === 'auto') {
        try {
            $pdo = create_mysql_pdo();
            $GLOBALS['_db_kind'] = 'mysql';
            return $pdo;
        } catch (Throwable $e) {
            $pdo = create_sqlite_pdo();
            $GLOBALS['_db_kind'] = 'sqlite';
            return $pdo;
        }
    }

    if ($driver === 'mysql') {
        $pdo = create_mysql_pdo();
        $GLOBALS['_db_kind'] = 'mysql';
        return $pdo;
    }

    $pdo = create_sqlite_pdo();
    $GLOBALS['_db_kind'] = 'sqlite';
    return $pdo;
}

function db_is_sqlite(): bool
{
    db(); // ensure init
    return ($GLOBALS['_db_kind'] ?? 'mysql') === 'sqlite';
}

function create_mysql_pdo(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function create_sqlite_pdo(): PDO
{
    $dir = dirname(__DIR__) . '/database';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = $dir . '/app.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    ensure_sqlite_schema($pdo);
    return $pdo;
}

/**
 * Creates tables + seed rows when using SQLite (first run).
 */
function ensure_sqlite_schema(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS technicians (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT NOT NULL,
  service_type TEXT NOT NULL,
  area TEXT NOT NULL,
  is_available INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS bookings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT NOT NULL,
  address TEXT NOT NULL,
  service TEXT NOT NULL,
  issue TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  technician_id INTEGER,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (status IN ('pending', 'assigned', 'completed'))
);
SQL);

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bookings_phone ON bookings(phone)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bookings_technician ON bookings(technician_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_technicians_service ON technicians(service_type)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_technicians_phone ON technicians(phone)');

    $n = (int) $pdo->query('SELECT COUNT(*) FROM technicians')->fetchColumn();
    if ($n === 0) {
        $ins = $pdo->prepare(
            'INSERT INTO technicians (name, phone, service_type, area, is_available) VALUES (?, ?, ?, ?, ?)'
        );
        $rows = [
            ['Ravi Kumar', '9876543210', 'electrician', 'Indiranagar, Bangalore', 1],
            ['Suresh Nair', '9876543211', 'ac_repair', 'Koramangala, Bangalore', 1],
            ['Amit Patil', '9876543212', 'plumber', 'Whitefield, Bangalore', 1],
        ];
        foreach ($rows as $r) {
            $ins->execute([$r[0], $r[1], $r[2], $r[3], $r[4]]);
        }
    }
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
