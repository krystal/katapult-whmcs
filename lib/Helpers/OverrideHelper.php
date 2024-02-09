<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use Illuminate\Support\Str;

class OverrideHelper
{
    public const OVERRIDES_DIR = 'overrides';

    protected static function path(string $file = null): string
    {
        $path = Str::finish(realpath(__DIR__ . '/../../'), '/');

        if ($file) {
            $path .= self::normaliseFile($file);
        }

        return $path;
    }

    protected static function overriddenPath(string $file = null): string
    {
        $path = self::path() . 'overrides/';

        if ($file) {
            $path .= self::normaliseFile($file);
        }

        return $path;
    }

    protected static function normaliseFile(string $file): string
    {
        if (strpos($file, '/') === 0) {
            $file = substr($file, 1);
        }

        return $file;
    }

    public static function asset(string $file): string
    {
        $file = 'assets/' . self::normaliseFile($file);

        return self::file($file);
    }

    public static function view(string $file): string
    {
        $file = 'views/' . self::normaliseFile($file);

        return self::file($file);
    }

    public static function file(string $file): string
    {
        $file = self::normaliseFile($file);

        if (file_exists(self::overriddenPath($file))) {
            $file = self::normaliseFile(
                Str::finish(self::OVERRIDES_DIR, '/') . $file
            );
        }

        return $file;
    }
}
