<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocationSeeder extends Seeder
{
    /**
     * Number of rows per upsert query.
     */
    private const CHUNK_SIZE = 500;

    /**
     * Tables to import (in dependency order) and their primary keys.
     *
     * @var array<string, string>
     */
    private const TABLES = [
        'provinces' => 'province_id',
        'cities' => 'city_id',
        'subdistricts' => 'subdistrict_id',
    ];

    /**
     * Import provinces, cities, and subdistricts from the RajaOngkir SQL dump.
     *
     * Only INSERT statements are parsed; table schemas are managed by migrations.
     * Rows are upserted, so the seeder is safe to run multiple times.
     */
    public function run(): void
    {
        $sql = $this->readDump();

        DB::transaction(function () use ($sql) {
            foreach (self::TABLES as $table => $primaryKey) {
                $rows = $this->parseInsertRows($sql, $table);

                foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                    DB::table($table)->upsert(
                        $chunk,
                        [$primaryKey],
                        array_values(array_diff(array_keys($chunk[0]), [$primaryKey])),
                    );
                }

                $this->command?->info(sprintf('Imported %d rows into [%s].', count($rows), $table));
            }
        });
    }

    /**
     * Get the absolute path to the SQL dump.
     */
    protected function dumpPath(): string
    {
        return database_path('seeders/data/db_rajaongkir.sql');
    }

    /**
     * Read the SQL dump contents.
     *
     * @throws RuntimeException
     */
    private function readDump(): string
    {
        $path = $this->dumpPath();

        if (! is_file($path)) {
            throw new RuntimeException("Location SQL dump not found at [{$path}].");
        }

        return (string) file_get_contents($path);
    }

    /**
     * Parse all rows of `INSERT INTO <table>` statements into associative arrays.
     *
     * A character scanner is used instead of a single regex because large INSERT
     * statements exceed the PCRE backtracking limit.
     *
     * @return list<array<string, string|int|null>>
     */
    private function parseInsertRows(string $sql, string $table): array
    {
        $headerPattern = '/INSERT INTO `'.preg_quote($table, '/').'` \(([^)]*)\) VALUES/';

        preg_match_all($headerPattern, $sql, $headers, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $rows = [];

        foreach ($headers as $header) {
            $columns = array_map(
                fn (string $column): string => trim($column, " `\t\r\n"),
                explode(',', $header[1][0]),
            );

            $offset = $header[0][1] + strlen($header[0][0]);

            foreach ($this->scanTuples($sql, $offset) as $values) {
                $rows[] = array_combine($columns, $values);
            }
        }

        return $rows;
    }

    /**
     * Scan value tuples starting at the given offset until the terminating semicolon.
     *
     * @return list<list<string|int|null>>
     */
    private function scanTuples(string $sql, int $offset): array
    {
        $escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0"];
        $length = strlen($sql);
        $tuples = [];
        $tuple = [];
        $value = '';
        $isQuoted = false;
        $inQuote = false;
        $depth = 0;

        $pushValue = function () use (&$tuple, &$value, &$isQuoted): void {
            $tuple[] = $isQuoted ? $value : $this->castValue($value);
            $value = '';
            $isQuoted = false;
        };

        for ($i = $offset; $i < $length; $i++) {
            $char = $sql[$i];

            if ($inQuote) {
                if ($char === '\\' && $i + 1 < $length) {
                    $next = $sql[++$i];
                    $value .= $escapes[$next] ?? $next;
                } elseif ($char === "'" && ($sql[$i + 1] ?? '') === "'") {
                    $value .= "'";
                    $i++;
                } elseif ($char === "'") {
                    $inQuote = false;
                } else {
                    $value .= $char;
                }

                continue;
            }

            if ($char === "'") {
                $inQuote = true;
                $isQuoted = true;
            } elseif ($char === '(') {
                $depth++;
                $tuple = [];
            } elseif ($char === ')') {
                $depth--;
                $pushValue();
                $tuples[] = $tuple;
            } elseif ($char === ',' && $depth === 1) {
                $pushValue();
            } elseif ($char === ';' && $depth === 0) {
                break;
            } elseif ($depth === 1 && ! ctype_space($char)) {
                $value .= $char;
            }
        }

        return $tuples;
    }

    /**
     * Convert a raw unquoted SQL literal into a PHP value.
     */
    private function castValue(string $value): int|string|null
    {
        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        return is_numeric($value) ? (int) $value : $value;
    }
}
