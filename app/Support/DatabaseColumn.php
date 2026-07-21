<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DatabaseColumn
{
    public static function has(string $table, string $column): bool
    {
        try {
            $database = DB::getDatabaseName();

            return DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Backward-compatible alias for code that calls DatabaseColumn::exists().
     */
    public static function exists(string $table, string $column): bool
    {
        return self::has($table, $column);
    }
}
