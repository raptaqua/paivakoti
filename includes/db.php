<?php
/**
 * db.php — SQLite database connection & initialization
 */

define('DB_PATH', __DIR__ . '/../data/paivakoti.db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');

        initSchema($pdo);
    }
    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'ohjaaja',  -- 'admin' | 'ohjaaja'
            reset_token TEXT,
            reset_expires INTEGER,
            created_at INTEGER DEFAULT (strftime('%s','now')),
            active INTEGER DEFAULT 1,
            must_change_password INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS groups_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            emoji TEXT NOT NULL DEFAULT '🐦',
            sort_order INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS children (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL REFERENCES groups_table(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            age INTEGER NOT NULL DEFAULT 3,
            long_absent INTEGER DEFAULT 0,
            created_at INTEGER DEFAULT (strftime('%s','now'))
        );

        CREATE TABLE IF NOT EXISTS daily_absence (
            child_id INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
            absence_date TEXT NOT NULL,
            PRIMARY KEY (child_id, absence_date)
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS age_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            min_age INTEGER NOT NULL,
            max_age INTEGER,            -- NULL = no upper limit
            factor REAL NOT NULL
        );
    ");
    if ($pdo->query("SELECT COUNT(*) FROM age_groups")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO age_groups (label, min_age, max_age, factor) VALUES
            ('Alle 3-vuotiaat', 0, 2, 1.75),
            ('3-vuotiaat ja vanhemmat', 3, NULL, 1.0)");
    }

    // Migrate older databases that lack the must_change_password column
    $cols = array_column($pdo->query("PRAGMA table_info(users)")->fetchAll(), 'name');
    if (!in_array('must_change_password', $cols, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN must_change_password INTEGER DEFAULT 0");
    }

    // Seed default admin if no users exist
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $hash = password_hash('admin1234', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, must_change_password) VALUES (?,?,?,?,1)")
            ->execute(['admin', $hash, 'Pääkäyttäjä', 'admin']);
    }

    // Seed default groups & children if empty
    $gc = $pdo->query("SELECT COUNT(*) FROM groups_table")->fetchColumn();
    if ($gc == 0) {
        $groups = [
            [1, 'Varpuset',    '🐦',   0],
            [2, 'Harakat',     '🐦‍⬛', 1],
            [3, 'Kottaraiset', '🐤',   2],
        ];
        foreach ($groups as $g) {
            $pdo->prepare("INSERT INTO groups_table (id, name, emoji, sort_order) VALUES (?,?,?,?)")->execute($g);
        }

        $children = [
            [1, 'Emma K.',     2],
            [1, 'Mikael R.',   2],
            [1, 'Liisa P.',    3],
            [1, 'Olli T.',     1],
            [1, 'Sofia H.',    2],
            [2, 'Anni M.',     4],
            [2, 'Petteri V.',  3],
            [2, 'Sara L.',     4],
            [2, 'Juhani A.',   3],
            [2, 'Helmi N.',    5],
            [3, 'Veikko S.',   5],
            [3, 'Maria O.',    6],
            [3, 'Eino J.',     5],
            [3, 'Kaisa R.',    6],
            [3, 'Taavi H.',    5],
        ];
        $stmt = $pdo->prepare("INSERT INTO children (group_id, name, age) VALUES (?,?,?)");
        foreach ($children as $c) $stmt->execute($c);
    }
}

/** All age groups, youngest first. */
function getAgeGroups(): array {
    return getDB()->query("SELECT * FROM age_groups ORDER BY min_age")->fetchAll();
}

/** Multiplier for a child's age; ages outside every age group count as 1.0. */
function ageFactor(int $age, array $ageGroups): float {
    foreach ($ageGroups as $g) {
        if ($age >= $g['min_age'] && ($g['max_age'] === null || $age <= $g['max_age'])) {
            return (float)$g['factor'];
        }
    }
    return 1.0;
}

/** Format a factor for display, e.g. 1.75 / 1.0 */
function formatFactor(float $f): string {
    $s = number_format($f, 2, '.', '');
    return substr($s, -1) === '0' ? substr($s, 0, -1) : $s;
}
