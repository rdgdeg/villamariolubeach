<?php

class Backup
{
    public static function sqlDump(): string
    {
        $pdo = db()->pdo();
        $driver = db()->driver();
        $out = "-- Villa Mariolu Beach backup\n-- " . date('c') . "\n\n";
        $tables = self::tables($pdo, $driver);
        foreach ($tables as $table) {
            if (str_starts_with($table, 'sqlite_')) {
                continue;
            }
            $out .= "-- table $table\n";
            $out .= "DELETE FROM `$table`;\n";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(static fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
                $vals = array_map([self::class, 'sqlValue'], array_values($row));
                $out .= 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ");\n";
            }
            $out .= "\n";
        }
        return $out;
    }

    public static function downloadSql(): void
    {
        $sql = self::sqlDump();
        $name = 'villamariolu-db-' . date('Ymd-His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    public static function downloadFiles(): void
    {
        if (!class_exists('ZipArchive')) {
            flash('error', 'ZipArchive n’est pas disponible sur ce serveur.');
            redirect(base_url('admin/backup'));
        }
        $zipPath = sys_get_temp_dir() . '/vmb-files-' . bin2hex(random_bytes(6)) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            flash('error', 'Impossible de créer l’archive.');
            redirect(base_url('admin/backup'));
        }
        $root = ROOT . '/assets/img';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $local = 'assets/img/' . ltrim(substr($file->getPathname(), strlen($root)), '/\\');
            $zip->addFile($file->getPathname(), $local);
        }
        $sqlite = (string) config('sqlite_path', '');
        if ($sqlite !== '' && is_file($sqlite)) {
            $zip->addFile($sqlite, 'data/app.db');
        }
        $zip->close();
        $name = 'villamariolu-fichiers-' . date('Ymd-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    private static function tables(PDO $pdo, string $driver): array
    {
        if ($driver === 'mysql') {
            $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
            return array_map(static fn ($r) => (string) $r[0], $rows);
        }
        $rows = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
        return array_column($rows, 'name');
    }

    private static function sqlValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
