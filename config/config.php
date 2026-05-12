<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Nairobi');

define('APP_NAME', 'BlackBridge Recruitment');
define('APP_TAGLINE', 'Brokered hiring with privacy, structure, and trust.');
define('PROJECT_FOLDER', basename(dirname(__DIR__)));
define('BASE_URL', '/' . rawurlencode(PROJECT_FOLDER));
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('CV_UPLOAD_DIR', UPLOAD_DIR . '/cvs');
define('CERTIFICATE_UPLOAD_DIR', UPLOAD_DIR . '/certificates');

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pdo = get_database_connection();
ensure_site_content_storage($pdo);
