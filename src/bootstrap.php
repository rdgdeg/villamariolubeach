<?php

define('ROOT', dirname(__DIR__));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require ROOT . '/src/Helpers.php';
require ROOT . '/src/Database.php';
require ROOT . '/src/Settings.php';
require ROOT . '/src/Csrf.php';
require ROOT . '/src/Auth.php';
require ROOT . '/src/I18n.php';
require ROOT . '/src/Pricing.php';
require ROOT . '/src/Availability.php';
require ROOT . '/src/BookingService.php';
require ROOT . '/src/StayCopy.php';
require ROOT . '/src/EmailTemplates.php';
require ROOT . '/src/Mailer.php';
require ROOT . '/src/Seo.php';
require ROOT . '/src/Backup.php';

db();
