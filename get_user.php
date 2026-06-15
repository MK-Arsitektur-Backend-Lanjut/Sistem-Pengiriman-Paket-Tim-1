<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$user = User::first();
if ($user) {
    echo "Email: " . $user->email . "\n";
    echo "Password: " . $user->password . "\n";
    echo "ID: " . $user->id . "\n";
} else {
    echo "No user found\n";
}
