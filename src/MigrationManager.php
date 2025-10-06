<?php
class MigrationManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureLogTable();
        $this->ensureLogColumns();
    }

    public function runMigrations(): string
    {
        $migrationsDir = __DIR__ . '/../database/migrations/';
        $applied = $this->getAppliedMigrations();
        $files = glob($migrationsDir . '*.sql');
        sort($files);
        $messages = [];

        $batch = $this->getNextBatchNumber();

        foreach ($files as $file) {
            $filename = basename($file);

            if (!in_array($filename, $applied)) {
                $sql = file_get_contents($file);

                try {
                    $this->db->beginTransaction();

                    $this->db->exec($sql);
                    $this->markAsApplied($filename, $batch);

                    $this->db->commit();
                    $messages[] = "✅ Applied migration: {$filename}";
                } catch (PDOException $e) {
                    $this->db->rollBack();
                    $messages[] = "❌ Failed migration: {$filename} - " . $e->getMessage();
                    // stop here to prevent partial batch application
                    break;
                }
            }
        }

        $this->logAction('run', $batch, implode("\n", $messages));
        return implode("\n", $messages);
    }

    public function rollbackLastBatch(): string
    {
        $stmt = $this->db->query("SELECT batch, migration FROM migrations_log WHERE action='run' ORDER BY batch DESC, id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return "No migrations to rollback.";
        }

        $lastBatch = $rows[0]['batch'];
        $toRollback = array_filter($rows, fn($row) => $row['batch'] == $lastBatch);
        $messages = [];

        foreach (array_reverse($toRollback) as $row) {
            $file = __DIR__ . '/../database/migrations/' . $row['migration'];
            $rollbackFile = str_replace('.sql', '_down.sql', $file);

            if (file_exists($rollbackFile)) {
                try {
                    $this->db->beginTransaction();

                    $sql = file_get_contents($rollbackFile);
                    $this->db->exec($sql);
                    $this->unmarkMigration($row['migration']);

                    $this->db->commit();
                    $messages[] = "🔄 Rolled back: {$row['migration']}";
                } catch (PDOException $e) {
                    $this->db->rollBack();
                    $messages[] = "❌ Failed to rollback {$row['migration']} - " . $e->getMessage();
                    // stop rollback chain on first failure
                    break;
                }
            } else {
                $messages[] = "⚠ No rollback file for {$row['migration']}";
            }
        }

        $this->logAction('rollback', $lastBatch, implode("\n", $messages));
        return implode("\n", $messages);
    }

    private function ensureLogTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS migrations_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            action ENUM('run','rollback') NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY migration_name (migration)
        )");
    }

    private function ensureLogColumns(): void
    {
        $columns = $this->db->query("SHOW COLUMNS FROM migrations_log")->fetchAll(PDO::FETCH_COLUMN);
        $required = [
            'migration'  => "ALTER TABLE migrations_log ADD COLUMN migration VARCHAR(255) NOT NULL DEFAULT '(unknown)' AFTER id",
            'batch'      => "ALTER TABLE migrations_log ADD COLUMN batch INT NOT NULL DEFAULT 0",
            'action'     => "ALTER TABLE migrations_log ADD COLUMN action ENUM('run','rollback') NOT NULL DEFAULT 'run'",
            'details'    => "ALTER TABLE migrations_log ADD COLUMN details TEXT",
            'created_at' => "ALTER TABLE migrations_log ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        foreach ($required as $col => $sql) {
            if (!in_array($col, $columns)) {
                $this->db->exec($sql);
            }
        }
    }

    private function getAppliedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations_log WHERE action='run'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function markAsApplied(string $migration, int $batch): void
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO migrations_log (migration, batch, action, details) VALUES (?, ?, 'run', '')");
        $stmt->execute([$migration, $batch]);
    }

    private function unmarkMigration(string $migration): void
    {
        $stmt = $this->db->prepare("DELETE FROM migrations_log WHERE migration = ? AND action='run'");
        $stmt->execute([$migration]);
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations_log WHERE action='run'");
        return (int)$stmt->fetchColumn() + 1;
    }

    private function logAction(string $action, int $batch, string $details): void
    {
        $summaryName = sprintf('(batch-%d-%s-%d)', $batch, $action, time());

        $stmt = $this->db->prepare(
            "INSERT INTO migrations_log (migration, batch, action, details)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             batch = VALUES(batch), action = VALUES(action), details = VALUES(details), created_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$summaryName, $batch, $action, $details ?: '(no details)']);
    }
}
?>
