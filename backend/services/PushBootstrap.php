<?php
namespace ControlCenter;

class PushBootstrap
{
    private static bool $autoloadLoaded = false;
    private static ?PushService $instance = null;

    public static function ensureAutoload(): void
    {
        if (self::$autoloadLoaded) {
            return;
        }

        $autoloadFile = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            require_once $autoloadFile;
        }

        require_once __DIR__ . '/PushService.php';
        self::$autoloadLoaded = true;
    }

    public static function getService(): PushService
    {
        if (self::$instance === null) {
            self::ensureAutoload();
            self::$instance = new PushService();
        }

        return self::$instance;
    }
}
