<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDatabase extends Command
{
    protected $signature = 'db:import {file : Path to SQL file}';
    protected $description = 'Import a SQL file into the database';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Reading SQL file: {$file}");
        $sql = file_get_contents($file);

        $lines = explode("\n", $sql);
        $statement = '';
        $inMultilineComment = false;
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Handle multiline comments
            if (strpos($line, '/*') !== false) {
                $inMultilineComment = true;
            }
            if (strpos($line, '*/') !== false) {
                $inMultilineComment = false;
                continue;
            }

            if ($inMultilineComment) {
                continue;
            }

            // Skip single-line comments and empty lines
            if (empty($line) || strpos($line, '--') === 0) {
                continue;
            }

            $statement .= $line . "\n";

            // Execute when we hit a semicolon
            if (substr(rtrim($line), -1) === ';') {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        DB::statement($statement);
                        $count++;
                        $this->line(".");
                    } catch (\Exception $e) {
                        $this->warn("\nError executing statement: " . substr($statement, 0, 50) . "...");
                        $this->warn("Error: " . $e->getMessage());
                    }
                }
                $statement = '';
            }
        }

        $this->info("\n\nDatabase import completed! Executed {$count} statements.");
        return 0;
    }
}
