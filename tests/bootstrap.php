<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('encrypt')) {
    function encrypt(string $text): string {
        return $text;
    }
}

if (!function_exists('decrypt')) {
    function decrypt(string $text): string {
        return $text;
    }
}

if (!function_exists('logActivity')) {
    function logActivity(string $text): void {
    }
}
