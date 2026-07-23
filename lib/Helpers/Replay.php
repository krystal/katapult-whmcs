<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use Illuminate\Support\Str;

class Replay
{
    private static function generateToken(): string
    {
        return sha1(Str::random() . microtime());
    }

    private static function currentToken(): ?string
    {
        return $_SESSION['knrp_token'] ?? null;
    }

    private static function issueToken(): string
    {
        $_SESSION['knrp_token'] = self::generateToken();

        return $_SESSION['knrp_token'];
    }

    public static function tokenIsValidForClientArea(string $token = null): ?bool
    {
        if (!defined('CLIENTAREA')) {
            return null;
        }

        if (!CLIENTAREA) {
            return null;
        }

        return self::tokenIsValid($token);
    }

    /**
     * Checks if a token is valid and consumes it, issuing a new one, if it was.
     *
     * WHMCS bootstraps the client area on 404, which means a 404 could rotate
     * the token if we issue it on request vs clearing it when checked/consumed.
     *
     * This'd also be true for users loading other tabs in between attempting to
     * run an action against this module.
     */
    public static function tokenIsValid(string $token = null): bool
    {
        if ($token === null) {
            $requestToken = $_REQUEST['knrp'] ?? '';

            // If a user supplies an array for the token, reject it.
            if (is_array($requestToken)) {
                return false;
            }

            $token = trim($requestToken);
        }

        if (!$token) {
            return false;
        }

        if ($token !== self::currentToken()) {
            return false;
        }

        // Issuing a new token consumes the current one. The next request will
        // use the newly issued token.
        self::issueToken();

        return true;
    }

    /**
     * The current token if there was one, or issue a new one and return it.
     */
    public static function getToken(): string
    {
        return self::currentToken() ?? self::issueToken();
    }
}
