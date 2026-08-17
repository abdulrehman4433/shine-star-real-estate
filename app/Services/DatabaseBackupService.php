<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Produces a portable, plain-SQL dump of the entire application database — every table, every row,
 * schema included — so a full site migration is just "run this .sql file on the new database" plus
 * copying the project files (see DatabaseBackupService::backupFilesDescription() and
 * MediaBackupService for the other half: uploaded files aren't in the database at all).
 *
 * Deliberately NOT a wrapper around the `mysqldump` binary (unlike spatie/laravel-backup and similar
 * packages) — a new system might not have `mysqldump` installed, on PATH, or even accessible (managed
 * hosting, a different OS, a stripped-down container). Shelling out to a binary path that's correct on
 * this XAMPP/Windows install would silently fail to be portable to exactly the "new system" this feature
 * exists for. Building the dump in pure PHP via DB::select()/SHOW CREATE TABLE means the only requirement
 * is a working PDO MySQL connection, which the app already needs to run at all.
 */
class DatabaseBackupService
{
    /**
     * Stream the full dump directly to disk (not built up as one giant string in memory first) — safe
     * even once this database has years of content, since only one table's rows are buffered at a time.
     */
    public function dumpToFile(string $path): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Could not open {$path} for writing.");
        }

        try {
            $connection = DB::connection();
            $databaseName = $connection->getDatabaseName();

            fwrite($handle, "-- Shine Star Marketing — full database backup\n");
            fwrite($handle, '-- Database: '.$databaseName."\n");
            fwrite($handle, '-- Generated: '.now()->toDateTimeString()."\n");
            fwrite($handle, "-- Restore with: mysql -u USER -p DATABASE_NAME < this_file.sql\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($this->tableNames() as $table) {
                $this->dumpTable($handle, $table);
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }
    }

    /** @return array<int, string> */
    private function tableNames(): array
    {
        $databaseName = DB::connection()->getDatabaseName();

        $rows = DB::select(
            'SELECT table_name AS name FROM information_schema.tables WHERE table_schema = ? AND table_type = ?',
            [$databaseName, 'BASE TABLE']
        );

        return array_map(fn ($row) => $row->name, $rows);
    }

    /** @param resource $handle */
    private function dumpTable($handle, string $table): void
    {
        $createRow = DB::select("SHOW CREATE TABLE `{$table}`")[0];
        // MySQL returns this as "Create Table" (spaces, not underscores) — grab it dynamically rather
        // than assuming property casing, since that's the one field name MySQL itself controls here.
        $createSql = $createRow->{'Create Table'} ?? array_values((array) $createRow)[1];

        fwrite($handle, "-- ----------------------------\n");
        fwrite($handle, "-- Table: {$table}\n");
        fwrite($handle, "-- ----------------------------\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $createSql.";\n\n");

        $pdo = DB::connection()->getPdo();
        $rowCount = 0;

        DB::table($table)->chunkById(500, function ($rows) use ($handle, $table, $pdo, &$rowCount) {
            foreach ($rows as $row) {
                $data = (array) $row;
                $columns = array_map(fn ($col) => "`{$col}`", array_keys($data));
                $values = array_map(function ($value) use ($pdo) {
                    if (is_null($value)) {
                        return 'NULL';
                    }

                    return $pdo->quote((string) $value);
                }, array_values($data));

                fwrite(
                    $handle,
                    "INSERT INTO `{$table}` (".implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n"
                );
                $rowCount++;
            }
        }, $this->primaryKeyOrFallback($table));

        if ($rowCount === 0) {
            fwrite($handle, "-- (no rows)\n");
        }

        fwrite($handle, "\n");
    }

    /** chunkById() needs a real unique/incrementing column — every table in this app has an `id` primary
     *  key except pivot tables, which chunkById() can still page through via any indexed column; falling
     *  back to the first column keeps this generic instead of hardcoding every pivot table's shape. */
    private function primaryKeyOrFallback(string $table): string
    {
        $hasId = DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [DB::connection()->getDatabaseName(), $table, 'id']
        );

        if (! empty($hasId)) {
            return 'id';
        }

        $columns = DB::select(
            'SELECT column_name AS name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position LIMIT 1',
            [DB::connection()->getDatabaseName(), $table]
        );

        return $columns[0]->name ?? 'id';
    }
}
