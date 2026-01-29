<?php
namespace ControlCenter;

/**
 * Mailer Bootstrap
 * 
 * Lädt das Resend SDK sicher ohne Konflikte
 */
class MailerBootstrap
{
    private static bool $autoloadLoaded = false;
    private static ?object $resendMailer = null;

    /**
     * Sicher das Resend SDK laden
     */
    public static function ensureAutoload(): void
    {
        if (self::$autoloadLoaded) {
            return;
        }

        // Prüfen ob Resend bereits geladen ist
        if (class_exists('\\Resend\\Resend', false)) {
            self::$autoloadLoaded = true;
            return;
        }

        // Autoload versuchen
        $autoloadFile = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            require_once $autoloadFile;
        }

        self::$autoloadLoaded = true;
    }

    /**
     * Get shared ResendMailer instance
     */
    public static function getMailer(): ResendMailer
    {
        if (self::$resendMailer === null) {
            self::ensureAutoload();
            self::$resendMailer = new ResendMailer();
        }

        return self::$resendMailer;
    }
}