<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$pageTitle = 'About';
$pageIntroText = site_setting(
    $pdo,
    'about.banner_intro',
    'Learn how the brokerage model protects both employers and applicants during the hiring process.'
);
$modelPoints = site_items($pdo, 'about.model_points');
$privacyPoints = site_items($pdo, 'about.privacy_points');
$featureCards = site_items($pdo, 'about.feature_cards');

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container hero-copy">
        <span class="eyebrow"><?= ui_icon('shield', 'section-icon section-icon-inline') ?>About <?= e(APP_NAME) ?></span>
        <h1><?= e(site_setting($pdo, 'about.hero_title', 'Recruitment brokerage built around privacy and professional coordination.')) ?></h1>
        <p><?= e(site_setting($pdo, 'about.hero_body', 'This platform is designed for situations where a trusted admin must manage the relationship between job seekers and hiring clients. Applicants and employers use the same system, but direct contact details stay private until admin reviews and approves the next step.')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container split-layout">
        <article class="panel">
            <h2><?= ui_icon('briefcase', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'about.model_title', 'Our Model')) ?></h2>
            <p><?= e(site_setting($pdo, 'about.model_body', 'Public visitors can explore jobs, anonymous talent, and business information without logging in. Once a user wants to apply for a role, request a worker, or access sensitive information, login becomes mandatory and admin remains the central broker.')) ?></p>
            <div class="feature-list-grid">
                <?php foreach ($modelPoints as $item): ?>
                    <article class="feature-list-card">
                        <span class="icon-badge"><?= ui_icon((string) ($item['icon_name'] ?? 'spark'), 'card-inline-icon') ?></span>
                        <div>
                            <h3><?= e((string) ($item['title'] ?? '')) ?></h3>
                            <?php if (trim((string) ($item['body'] ?? '')) !== ''): ?>
                                <p><?= e((string) ($item['body'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="panel">
            <h2><?= ui_icon('lock', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'about.privacy_title', 'Privacy Promise')) ?></h2>
            <p><?= e(site_setting($pdo, 'about.privacy_body', 'Candidate public pages show anonymous codes, skill sets, location, education level, availability, and salary expectations. Employer-facing browsing is also limited to anonymous data until admin explicitly allows deeper sharing.')) ?></p>
            <div class="feature-list-grid">
                <?php foreach ($privacyPoints as $item): ?>
                    <article class="feature-list-card">
                        <span class="icon-badge"><?= ui_icon((string) ($item['icon_name'] ?? 'spark'), 'card-inline-icon') ?></span>
                        <div>
                            <h3><?= e((string) ($item['title'] ?? '')) ?></h3>
                            <?php if (trim((string) ($item['body'] ?? '')) !== ''): ?>
                                <p><?= e((string) ($item['body'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </div>
</section>

<section class="section">
    <div class="container cards-grid">
        <?php foreach ($featureCards as $item): ?>
            <article class="card">
                <span class="metric-icon"><?= ui_icon((string) ($item['icon_name'] ?? 'spark'), 'metric-icon-svg') ?></span>
                <h3><?= e((string) ($item['title'] ?? '')) ?></h3>
                <p><?= e((string) ($item['body'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
