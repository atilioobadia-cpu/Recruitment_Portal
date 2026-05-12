<?php
declare(strict_types=1);

require_login();

$pageTitle = $pageTitle ?? 'Dashboard';
$currentUser = current_user();
$flashMessages = get_flashes();
$notificationCount = unread_notification_count($pdo, (int) $currentUser['id']);
$notifications = fetch_notifications($pdo, (int) $currentUser['id']);
$brandPrimary = site_setting($pdo, 'branding.primary_word', 'Black');
$brandAccent = site_setting($pdo, 'branding.accent_word', 'Bridge');
$brandTagline = site_setting($pdo, 'branding.tagline', 'Recruitment Brokerage');
$siteDisplayName = trim($brandPrimary . $brandAccent) !== '' ? trim($brandPrimary . $brandAccent) : APP_NAME;

$navigation = match ($currentUser['role']) {
    'admin' => [
        ['label' => 'Dashboard', 'path' => 'admin/dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Jobs', 'path' => 'admin/jobs.php', 'icon' => 'briefcase'],
        ['label' => 'Applicants', 'path' => 'admin/applicants.php', 'icon' => 'users'],
        ['label' => 'Employers', 'path' => 'admin/employers.php', 'icon' => 'building'],
        ['label' => 'Requests', 'path' => 'admin/requests.php', 'icon' => 'link'],
        ['label' => 'Applications', 'path' => 'admin/applications.php', 'icon' => 'document'],
        ['label' => 'Categories', 'path' => 'admin/categories.php', 'icon' => 'category'],
        ['label' => 'Content', 'path' => 'admin/content.php', 'icon' => 'spark'],
        ['label' => 'Reports', 'path' => 'admin/reports.php', 'icon' => 'chart'],
    ],
    'applicant' => [
        ['label' => 'Dashboard', 'path' => 'applicant/dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'My Profile', 'path' => 'applicant/profile.php', 'icon' => 'user-check'],
        ['label' => 'Applications', 'path' => 'applicant/applications.php', 'icon' => 'document'],
    ],
    default => [
        ['label' => 'Dashboard', 'path' => 'employer/dashboard.php', 'icon' => 'dashboard'],
        ['label' => 'Candidates', 'path' => 'employer/candidates.php', 'icon' => 'users'],
        ['label' => 'Requests', 'path' => 'employer/requests.php', 'icon' => 'link'],
    ],
};
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($siteDisplayName) ?></title>
    <link rel="stylesheet" href="<?= e(versioned_asset_url('css/style.css')) ?>">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="sidebar" data-sidebar>
            <div class="sidebar-brand">
                <a class="brand brand-logo dashboard-brand" href="<?= e(app_url('index.php')) ?>">
                    <span class="brand-copy">
                        <span class="brand-name">
                            <span class="brand-word brand-word-light"><?= e($brandPrimary) ?></span><span class="brand-word brand-word-accent"><?= e($brandAccent) ?></span>
                        </span>
                        <small><?= e($brandTagline) ?> | <?= e(role_label($currentUser['role'])) ?> Panel</small>
                    </span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($navigation as $item): ?>
                    <a class="<?= e(active_class([$item['path']])) ?>" href="<?= e(app_url($item['path'])) ?>">
                        <span class="nav-icon"><?= ui_icon($item['icon'] ?? 'spark', 'nav-icon-svg') ?></span>
                        <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-card">
                <strong>Privacy rule</strong>
                <p>Applicants and employers do not see each other's direct contacts until admin approval.</p>
            </div>
        </aside>

        <div class="dashboard-main">
            <header class="dashboard-topbar">
                <div class="dashboard-topbar-left">
                    <button class="nav-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">
                        <?= ui_icon('menu', 'button-icon') ?>
                        Menu
                    </button>
                    <div>
                        <h1><?= e($pageTitle) ?></h1>
                        <p><?= e(role_label($currentUser['role'])) ?> workspace</p>
                    </div>
                </div>

                <div class="dashboard-topbar-right">
                    <button type="button" class="ghost-button theme-toggle" data-theme-toggle>Theme</button>
                    <div class="notification-pill"><?= e((string) $notificationCount) ?> updates</div>
                    <a class="ghost-button" href="<?= e(app_url('index.php')) ?>"><?= ui_icon('globe', 'button-icon') ?>Public Site</a>
                    <a class="button button-dark" href="<?= e(app_url('logout.php')) ?>"><?= ui_icon('lock', 'button-icon') ?>Logout</a>
                </div>
            </header>

            <?php if ($flashMessages !== []): ?>
                <div class="flash-stack dashboard-flashes">
                    <?php foreach ($flashMessages as $flash): ?>
                        <div class="flash flash-<?= e($flash['type']) ?>" data-flash>
                            <?= e($flash['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($notifications !== []): ?>
                <section class="notification-bar">
                    <?php foreach ($notifications as $notification): ?>
                        <article class="notification-item">
                            <strong><?= e($notification['title']) ?></strong>
                            <p><?= e(truncate_text($notification['message'], 110)) ?></p>
                            <small><?= e(format_date($notification['created_at'], 'M d, Y h:i A')) ?></small>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <main class="dashboard-content">
