<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_login();

$documentId = (int) ($_GET['id'] ?? 0);
$currentUser = current_user();

$statement = $pdo->prepare(
    'SELECT cd.*, cp.user_id
     FROM candidate_documents cd
     INNER JOIN candidate_profiles cp ON cp.id = cd.profile_id
     WHERE cd.id = :id
     LIMIT 1'
);
$statement->execute(['id' => $documentId]);
$document = $statement->fetch();

if ($document === false) {
    set_flash('error', 'Document not found.');
    redirect(dashboard_path_for_role($currentUser['role']));
}

$isAdmin = $currentUser['role'] === 'admin';
$isOwner = $currentUser['role'] === 'applicant' && (int) $document['user_id'] === (int) $currentUser['id'];

if (!$isAdmin && !$isOwner) {
    set_flash('error', 'You are not allowed to access this document.');
    redirect(dashboard_path_for_role($currentUser['role']));
}

$baseDirectory = $document['document_type'] === 'CV' ? CV_UPLOAD_DIR : CERTIFICATE_UPLOAD_DIR;
$filePath = $baseDirectory . DIRECTORY_SEPARATOR . $document['file_name'];

if (!is_file($filePath)) {
    set_flash('error', 'The requested file is missing from storage.');
    redirect(dashboard_path_for_role($currentUser['role']));
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $filePath) : 'application/octet-stream';
if ($finfo) {
    finfo_close($finfo);
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($filePath));
header('Content-Disposition: inline; filename="' . rawurlencode((string) $document['original_name']) . '"');

readfile($filePath);
exit;
