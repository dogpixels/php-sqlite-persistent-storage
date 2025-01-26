<?php declare(strict_types=1);
/**
 * Persistent Key-Value Storage 1.0
 * (c) 2025 draconigen@dogpixels.net
 * AGPL 3.0, see https://www.gnu.org/licenses/agpl-3.0.de.html
 * Provided "as is", without warranty of any kind.
 */

define('STORAGE_FILE', 'storage.db');
define('STORAGE_ENCRYPTION_KEY', ''); // empty disables encryption

/**
 * Static Class for persistent Key-Value storage.
 * Creates a database file (default: storage.sqlite) next to the script.
 */
class Storage {
    static private function init(): Sqlite3 {
        $flagInitDatabase = !file_exists(STORAGE_FILE);
        $db = new SQLite3(STORAGE_FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, STORAGE_ENCRYPTION_KEY);
        if ($flagInitDatabase) {
            $db->exec("CREATE TABLE cache (
                id    TEXT NOT NULL UNIQUE,
                mod   DATATIME DEFAULT (DATETIME('now', 'localtime')),
                data  TEXT,
                PRIMARY KEY(id)
            );");
        }
        return $db;
    }

    /**
     * Retrieve a single entry from database.
     * @param string $id Key of the entry to retrieve.
     * @return array|bool JSON-serializable array on success, otherwise false
     */
    static public function get(string $id): array | bool {
        $db = Storage::init();
        $stmt = $db->prepare("SELECT data FROM cache WHERE id=?;");
        if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_TEXT)) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        $cur = $stmt->execute();
        if (!$cur) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        while ($row = $cur->fetchArray()) {
            return json_decode($row['data'], true);
        }
        return [];
    }

    /**
     * Retrieves all entries from database.
     * @return array Assoc array of id => [data]
     */
    static public function getAll(): array {
        $db = Storage::init();
        $ret = [];
        $stmt = $db->prepare("SELECT id, data FROM cache;");
        if (!$stmt) {
            throw new Exception($db->lastErrorMsg());
            return $ret;
        }
        $cur = $stmt->execute();
        if (!$cur) {
            throw new Exception($db->lastErrorMsg());
            return $ret;
        }
        while ($row = $cur->fetchArray()) {
            $ret[$row['id']] = json_decode($row['data'], true);
        }
        return $ret;
    }

    /**
     * Inserts or updates data
     * @param string $id Key of the entry to insert/update.
     * @param array $data JSON-serializable data array to write to database.
     * @return bool Success indicator.
     */
    static public function set(string $id, array $data): bool {
        $db = Storage::init();        
        $jdata = json_encode($data);
        if ($jdata === false) {
            throw new Exception(json_last_error_msg());
            return false;
        }
        $stmt = $db->prepare("INSERT INTO cache (id, data) VALUES(?, ?) ON CONFLICT(id) DO UPDATE SET data=excluded.data, mod=excluded.mod;");        
        if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_TEXT) || !$stmt->bindValue(2, $jdata, SQLITE3_TEXT) || !$stmt->execute()) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Deletes a single entry.
     * @param string $id Key of entry to delete.
     * @return bool Success indicator.
     */
    static public function delete(string $id): bool {
        $db = Storage::init();
        $stmt = $db->prepare("DELETE FROM cache WHERE id=?;");
        if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_TEXT) || !$stmt->execute()) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Get the total count of rows in the database.
     * @return int Total number of rows.
     */
    static public function count(): int {
        $db = Storage::init();
        return $db->querySingle("SELECT COUNT(*) FROM cache;");
    }

    /**
     * Deletes all entries older than the given timespan.
     * @param string $age See https://www.sqlite.org/lang_datefunc.html for valid values.
     * @return bool Success indicator.
     */
    static public function prune(string $age): bool {
        $db = Storage::init();
        $stmt = $db->prepare("DELETE FROM cache WHERE mod <= DATETIME('now', 'localtime', ?);");        
        if (!$stmt || !$stmt->bindValue(1, $age, SQLITE3_TEXT) || !$stmt->execute()) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        return true;
    }
}
