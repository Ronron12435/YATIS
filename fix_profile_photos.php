<?php
// Fix profile photo paths in database
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update all profile photos with old path to new path
DB::table('users')
    ->where('profile_photo', 'like', 'uploads/profile_photos/%')
    ->update([
        'profile_photo' => DB::raw("REPLACE(profile_photo, 'uploads/profile_photos/', 'storage/profile_photos/')")
    ]);

echo "Profile photo paths updated successfully!\n";
?>
