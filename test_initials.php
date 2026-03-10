<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(1);
echo "Before: " . json_encode($user->toArray()) . "\n";
$user->fill(['initials' => 'ABC']);
$user->save();
$user->refresh();
echo "After: " . json_encode($user->toArray()) . "\n";
