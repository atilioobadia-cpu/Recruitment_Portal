<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$metaDescription = $metaDescription ?? APP_TAGLINE;
$pageIntroText = $pageIntroText ?? APP_TAGLINE;
$currentUser = current_user();
$flashMessages = get_flashes();
$brandPrimary = site_setting($pdo, 'branding.primary_word', 'Black');
$brandAccent = site_setting($pdo, 'branding.accent_word', 'Bridge');
$brandTagline = site_setting($pdo, 'branding.tagline', 'Recruitment Brokerage');
$siteDisplayName = trim($brandPrimary . $brandAccent) !== '' ? trim($brandPrimary . $brandAccent) : APP_NAME;
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isHomePage = $currentScript === 'index.php';
$defaultBannerExclusions = ['index.php', 'login.php', 'register.php', 'apply-job.php', 'request-candidate.php'];
$showPageBanner = $showPageBanner ?? !in_array($currentScript, $defaultBannerExclusions, true);
$bodyClasses = trim(($bodyClass ?? '') . ' ' . ($isHomePage ? 'site-home' : 'site-page'));
$publicNavItems = [
    ['label' => 'Home', 'path' => 'index.php', 'active' => ['index.php']],
    ['label' => 'Jobs', 'path' => 'jobs.php', 'active' => ['jobs.php', 'job-details.php']],
    ['label' => 'Candidates', 'path' => 'candidates.php', 'active' => ['candidates.php', 'candidate-details.php']],
    ['label' => 'About', 'path' => 'about.php', 'active' => ['about.php']],
    ['label' => 'Contact', 'path' => 'contact.php', 'active' => ['contact.php']],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($siteDisplayName) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="stylesheet" href="<?= e(versioned_asset_url('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClasses) ?>">
    <header class="site-header">
        <div class="header-utility">
            <div class="container header-utility-inner">
                <div class="utility-links">
                    <?php if ($currentUser !== null): ?>
                        <a href="<?= e(app_url(dashboard_path_for_role($currentUser['role']))) ?>">Dashboard</a>
                        <a href="<?= e(app_url('logout.php')) ?>">Logout</a>
                    <?php else: ?>
                        <a href="<?= e(app_url('register.php')) ?>">Register</a>
                        <a href="<?= e(app_url('login.php')) ?>">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="container topbar">
            <a class="brand brand-logo" href="<?= e(app_url('index.php')) ?>">
                <span class="brand-copy">
                    <span class="brand-name">
                        <span class="brand-word brand-word-dark"><?= e($brandPrimary) ?></span><span class="brand-word brand-word-accent"><?= e($brandAccent) ?></span>
                    </span>
                    <small><?= e($brandTagline) ?></small>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation">
                <?= ui_icon('menu', 'button-icon') ?>
                Menu
            </button>

            <nav class="site-nav">
                <div class="nav-main-links">
                    <?php foreach ($publicNavItems as $item): ?>
                        <a class="<?= e(active_class($item['active'])) ?>" href="<?= e(app_url($item['path'])) ?>"><?= e($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="nav-actions">
                    <?php if ($currentUser !== null): ?>
                        <a class="button button-primary" href="<?= e(app_url(dashboard_path_for_role($currentUser['role']))) ?>"><?= ui_icon('users', 'button-icon') ?>Client Area</a>
                    <?php else: ?>
                        <a class="button button-primary" href="<?= e(app_url('login.php')) ?>"><?= ui_icon('users', 'button-icon') ?>Client Area</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <nav class="mobile-nav-drawer" data-nav-menu>
        <div class="mobile-nav-shell">
            <div class="mobile-nav-header">
                <a class="brand brand-logo mobile-nav-brand" href="<?= e(app_url('index.php')) ?>">
                    <span class="brand-copy">
                        <span class="brand-name">
                            <span class="brand-word brand-word-dark"><?= e($brandPrimary) ?></span><span class="brand-word brand-word-accent"><?= e($brandAccent) ?></span>
                        </span>
                        <small><?= e($brandTagline) ?></small>
                    </span>
                </a>
                <button class="mobile-nav-close" type="button" data-nav-close aria-label="Close navigation">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="nav-main-links">
                <?php foreach ($publicNavItems as $item): ?>
                    <a class="<?= e(active_class($item['active'])) ?>" href="<?= e(app_url($item['path'])) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </div>

            <div class="nav-actions">
                <?php if ($currentUser !== null): ?>
                    <a class="button button-primary" href="<?= e(app_url(dashboard_path_for_role($currentUser['role']))) ?>"><?= ui_icon('users', 'button-icon') ?>Client Area</a>
                <?php else: ?>
                    <a class="button button-primary" href="<?= e(app_url('login.php')) ?>"><?= ui_icon('users', 'button-icon') ?>Client Area</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($flashMessages !== []): ?>
        <div class="container flash-stack">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>" data-flash>
                    <?= e($flash['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($showPageBanner): ?>
        <section class="page-banner">
            <div class="container page-banner-inner">
                <div class="page-banner-copy">
                    <h1><?= e($pageTitle) ?></h1>
                    <p><?= e($pageIntroText) ?></p>
                </div>
                <div class="page-banner-cutout"><?= e($pageTitle) ?></div>
            </div>
        </section>
    <?php endif; ?>
