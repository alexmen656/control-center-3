<?php
namespace ControlCenter;

class MailerBootstrap
{
    private static bool $autoloadLoaded = false;
    private static ?object $resendMailer = null;

    public static function ensureAutoload(): void
    {
        if (self::$autoloadLoaded) {
            return;
        }

        if (class_exists('\\Resend\\Resend', false)) {
            self::$autoloadLoaded = true;
            return;
        }

        $autoloadFile = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            require_once $autoloadFile;
        }

        self::$autoloadLoaded = true;
    }

    public static function getMailer(): ResendMailer
    {
        if (self::$resendMailer === null) {
            self::ensureAutoload();
            require_once __DIR__ . '/ResendMailer.php';
            self::$resendMailer = new ResendMailer();
        }

        return self::$resendMailer;
    }
}
