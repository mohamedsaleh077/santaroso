<?php

namespace Objects;

use Objects\Random;

class Session
{

    public function __construct()
    {
        $config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);

        session_set_cookie_params([
            'lifetime' => $config['host']['lifetime'],
            'domain' => $config['host']['host'],
            'path' => '/',
            'secure' => $config['host']['secure'],
            'httponly' => true
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateId();
        } else {
            $interval = 60 * 30;
            if (time() - $_SESSION['last_regeneration'] >= $interval) {
                $this->regenerateId();
            }
        }
    }

    private function regenerateId()
    {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
        try {
            $_SESSION["CSRF_TOKEN"] = bin2hex(random_bytes(32));
        } catch (Random\RandomException $e) {
            error_log("Failed to generate CSRF token: " . $e->getMessage());
        }
    }

    public function lastRequest(): void
    {
        $_SESSION['last_request'] = time();
    }

    public function isLastRequestWithinTimeframe(): bool
    {
        return isset($_SESSION['last_request']) && time() - $_SESSION['last_request'] < 60;
    }

    public function getRateLimitRemaining(): int
    {
        if (!isset($_SESSION['last_request'])) {
            return 0;
        }
        $elapsed = time() - ($_SESSION['last_request'] ?? 0);
        $remaining = 60 - $elapsed;
        return $remaining > 0 ? $remaining : 0;
    }

    public function getCsrfToken(): string
    {
        return $_SESSION['CSRF_TOKEN'] ?? '';
    }

    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    public function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function unsetSession($key)
    {
        unset($_SESSION[$key]);
    }

    public function getSession($key)
    {
        return $_SESSION[$key] ?? null;
    }
}