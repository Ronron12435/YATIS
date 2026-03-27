<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportOldDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Read the SQL file
        $sqlFile = base_path('Project_ojt/YATIS/database/yatis_db (1).sql');
        
        if (!file_exists($sqlFile)) {
            $this->command->error("SQL file not found at: {$sqlFile}");
            return;
        }

        $sql = file_get_contents($sqlFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--') && !str_starts_with($stmt, '/*')
        );

        foreach ($statements as $statement) {
            try {
                // Skip comments and empty lines
                if (empty(trim($statement)) || str_starts_with(trim($statement), '--')) {
                    continue;
                }
                
                DB::statement($statement);
            } catch (\Exception $e) {
                // Log errors but continue
                $this->command->warn("Error executing statement: " . substr($statement, 0, 100) . "...");
                $this->command->warn("Error: " . $e->getMessage());
            }
        }

        $this->command->info('Old database imported successfully!');
    }
}
