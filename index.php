<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$pageTitle = 'Home';
$metaDescription = site_setting(
    $pdo,
    'home.meta_description',
    'Blue-toned recruitment brokerage platform with anonymous candidate visibility and admin-controlled hiring workflows.'
);

$latestJobs = $pdo->query(
    'SELECT j.*, sc.name AS category_name
     FROM jobs j
     LEFT JOIN skill_categories sc ON sc.id = j.category_id
     WHERE j.status = "Published"
     ORDER BY j.is_featured DESC, j.created_at DESC
     LIMIT 6'
)->fetchAll();

$featuredCandidates = $pdo->query(
    'SELECT cp.*,
            (
                SELECT cs.skill_name
                FROM candidate_skills cs
                WHERE cs.profile_id = cp.id
                ORDER BY cs.id ASC
                LIMIT 1
            ) AS primary_skill
     FROM candidate_profiles cp
     WHERE cp.status = "Approved"
     ORDER BY cp.is_featured DESC, cp.approved_at DESC, cp.created_at DESC
     LIMIT 6'
)->fetchAll();

$categories = $pdo->query(
    'SELECT id, name, description
     FROM skill_categories
     WHERE is_active = 1
     ORDER BY name
     LIMIT 8'
)->fetchAll();

$homeHowItems = site_items($pdo, 'home.how');
$homeWhyItems = site_items($pdo, 'home.why');
$headlineStats = [
    [
        'label' => site_setting($pdo, 'home.stats_jobs_label', 'Published Jobs'),
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM jobs WHERE status = "Published"')->fetchColumn(),
        'icon' => 'briefcase',
    ],
    [
        'label' => site_setting($pdo, 'home.stats_candidates_label', 'Approved Candidates'),
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM candidate_profiles WHERE status = "Approved"')->fetchColumn(),
        'icon' => 'user-check',
    ],
    [
        'label' => site_setting($pdo, 'home.stats_requests_label', 'Employer Requests'),
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM employer_requests')->fetchColumn(),
        'icon' => 'link',
    ],
    [
        'label' => site_setting($pdo, 'home.stats_brokerage_label', 'Brokered Approach'),
        'value' => '100%',
        'icon' => 'shield',
    ],
];

$contactOneUrl = trim(site_setting($pdo, 'home.contact_one_cta_url', 'contact.php')) ?: 'contact.php';
$contactTwoUrl = trim(site_setting($pdo, 'home.contact_two_cta_url', 'register.php')) ?: 'register.php';

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero hero-home">
    <div class="container hero-home-inner">
        <span class="eyebrow hero-eyebrow">
            <?= ui_icon('shield', 'section-icon section-icon-inline') ?>
            <?= e(site_setting($pdo, 'home.eyebrow', 'Professional recruitment brokerage platform')) ?>
        </span>
        <h1><?= nl2br(e(site_setting($pdo, 'home.title', 'Every Great Hire Starts With a Trusted Recruitment Broker'))) ?></h1>
        <p>
            <?= e(site_setting($pdo, 'home.body', APP_NAME . ' helps applicants, employers, and admins work through a controlled recruitment process. Jobs stay public, candidate visibility stays anonymous, and every connection is reviewed by admin before any direct access is granted.')) ?>
        </p>

        <form class="hero-search" action="<?= e(app_url('jobs.php')) ?>" method="get">
            <div class="hero-search-field">
                <input type="text" name="q" placeholder="<?= e(site_setting($pdo, 'home.search_placeholder', 'Search jobs, skills or candidates')) ?>">
            </div>
            <button class="button button-primary hero-search-action" type="submit">
                <?= ui_icon('search', 'button-icon') ?>
                Search
            </button>
        </form>

        <div class="hero-caption">
            <?= ui_icon('spark', 'section-icon section-icon-inline') ?>
            <?= e(site_setting($pdo, 'home.caption', 'We make it easy to connect employers and talent through private admin-managed recruitment, protected profiles, approvals, and carefully controlled introductions.')) ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="reference-strip">
            <?php foreach ($headlineStats as $stat): ?>
                <article class="reference-strip-item">
                    <span class="reference-strip-icon"><?= ui_icon($stat['icon'], 'strip-icon-svg') ?></span>
                    <div>
                        <strong><?= e($stat['value']) ?></strong>
                        <span><?= e($stat['label']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="section-head">
            <div>
                <h2><?= ui_icon('briefcase', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.jobs_title', 'Latest Job Posts')) ?></h2>
                <p><?= e(site_setting($pdo, 'home.jobs_body', 'Open roles published by admin for public viewing and applicant applications.')) ?></p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('jobs.php')) ?>"><?= ui_icon('search', 'button-icon') ?>All Jobs</a>
        </div>

        <div class="cards-grid">
            <?php foreach ($latestJobs as $job): ?>
                <article class="card">
                    <div class="stack-row">
                        <span class="badge <?= e(status_badge_class($job['status'])) ?>"><?= e($job['status']) ?></span>
                        <?php if ((int) $job['is_featured'] === 1): ?>
                            <span class="badge badge-neutral">Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-title-row">
                        <span class="icon-badge"><?= ui_icon('briefcase', 'card-inline-icon') ?></span>
                        <h3><?= e($job['title']) ?></h3>
                    </div>
                    <p><?= e(truncate_text($job['summary'], 150)) ?></p>
                    <div class="inline-meta">
                        <span><?= ui_icon('map', 'ui-icon') ?><?= e($job['location']) ?></span>
                        <span><?= ui_icon('clipboard', 'ui-icon') ?><?= e($job['employment_type']) ?></span>
                        <span><?= ui_icon('category', 'ui-icon') ?><?= e($job['category_name'] ?: 'General') ?></span>
                    </div>
                    <div class="card-actions">
                        <a class="button button-dark" href="<?= e(app_url('job-details.php?id=' . $job['id'])) ?>">
                            <?= ui_icon('document', 'button-icon') ?>
                            View Details
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($latestJobs === []): ?>
                <div class="empty-state">
                    <h3><?= ui_icon('briefcase', 'section-icon section-icon-inline') ?>No jobs published yet</h3>
                    <p>Admin can add roles from the dashboard and they will appear here automatically.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('users', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.candidates_title', 'Featured Anonymous Candidates')) ?></h2>
                <p><?= e(site_setting($pdo, 'home.candidates_body', 'Only high-level public details are shown. Sensitive identity and documents remain hidden unless admin approves access.')) ?></p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('candidates.php')) ?>"><?= ui_icon('users', 'button-icon') ?>Browse Candidates</a>
        </div>

        <div class="cards-grid">
            <?php foreach ($featuredCandidates as $candidate): ?>
                <?php $summary = candidate_public_summary($candidate); ?>
                <article class="card">
                    <span class="candidate-code-badge"><?= e($summary['candidate_code']) ?></span>
                    <div class="card-title-row">
                        <span class="icon-badge"><?= ui_icon('user-check', 'card-inline-icon') ?></span>
                        <h3><?= e($summary['primary_skill']) ?></h3>
                    </div>
                    <p><?= e(truncate_text($summary['bio'], 130)) ?></p>
                    <div class="inline-meta">
                        <span><?= ui_icon('chart', 'ui-icon') ?><?= e($summary['experience_years']) ?> yrs</span>
                        <span><?= ui_icon('document', 'ui-icon') ?><?= e($summary['education_level']) ?></span>
                        <span><?= ui_icon('map', 'ui-icon') ?><?= e($summary['location']) ?></span>
                    </div>
                    <div class="card-actions">
                        <a class="ghost-button" href="<?= e(app_url('candidate-details.php?id=' . $candidate['id'])) ?>">
                            <?= ui_icon('document', 'button-icon') ?>
                            View Profile
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if ($featuredCandidates === []): ?>
                <div class="empty-state">
                    <h3><?= ui_icon('users', 'section-icon section-icon-inline') ?>No candidates approved yet</h3>
                    <p>Approved applicant profiles will appear here anonymously after admin review.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('category', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.categories_title', 'Skill Categories')) ?></h2>
                <p><?= e(site_setting($pdo, 'home.categories_body', 'Structured categories help admin organize candidates and public job opportunities.')) ?></p>
            </div>
        </div>

        <div class="cards-grid">
            <?php foreach ($categories as $category): ?>
                <article class="card">
                    <span class="metric-icon"><?= ui_icon('category', 'metric-icon-svg') ?></span>
                    <h3><?= e($category['name']) ?></h3>
                    <p><?= e($category['description'] ?: 'Curated recruitment category for targeted hiring.') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split-layout">
        <article class="panel">
            <div class="section-head">
                <div>
                    <h2><?= ui_icon('shield', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.how_title', 'How It Works')) ?></h2>
                    <p><?= e(site_setting($pdo, 'home.how_body', 'A brokered flow designed to protect both sides until the right point in the process.')) ?></p>
                </div>
            </div>
            <div class="timeline">
                <?php foreach ($homeHowItems as $item): ?>
                    <article class="timeline-item">
                        <span class="timeline-icon"><?= ui_icon((string) ($item['icon_name'] ?? 'spark'), 'metric-icon-svg') ?></span>
                        <div>
                            <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
                            <?php if (trim((string) ($item['body'] ?? '')) !== ''): ?>
                                <p><?= e((string) ($item['body'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="panel">
            <div class="section-head">
                <div>
                    <h2><?= ui_icon('star', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.why_title', 'Why Choose Us')) ?></h2>
                    <p><?= e(site_setting($pdo, 'home.why_body', 'Built for privacy, accountability, and smoother coordination.')) ?></p>
                </div>
            </div>
            <div class="feature-list-grid">
                <?php foreach ($homeWhyItems as $item): ?>
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

<section class="section" id="contact-section">
    <div class="container two-column">
        <article class="contact-card">
            <h3><?= ui_icon('mail', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.contact_one_title', 'Need a trusted recruitment broker?')) ?></h3>
            <p><?= e(site_setting($pdo, 'home.contact_one_body', 'Use the public contact page or create an employer account to submit a hiring request through admin.')) ?></p>
            <div class="card-actions">
                <a class="button button-dark" href="<?= e(app_url($contactOneUrl)) ?>">
                    <?= ui_icon('mail', 'button-icon') ?>
                    <?= e(site_setting($pdo, 'home.contact_one_cta_label', 'Contact Us')) ?>
                </a>
                <a class="ghost-button" href="<?= e(app_url('register.php')) ?>">
                    <?= ui_icon('users', 'button-icon') ?>
                    Register Now
                </a>
            </div>
        </article>
        <article class="contact-card">
            <h3><?= ui_icon('user-check', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'home.contact_two_title', 'Ready to be listed anonymously?')) ?></h3>
            <p><?= e(site_setting($pdo, 'home.contact_two_body', 'Applicants can build complete profiles, upload CVs and certificates, and wait for admin approval before public listing.')) ?></p>
            <div class="card-actions">
                <a class="button button-dark" href="<?= e(app_url($contactTwoUrl)) ?>">
                    <?= ui_icon('user-check', 'button-icon') ?>
                    <?= e(site_setting($pdo, 'home.contact_two_cta_label', 'Join as Applicant')) ?>
                </a>
                <a class="ghost-button" href="<?= e(app_url('about.php')) ?>">
                    <?= ui_icon('shield', 'button-icon') ?>
                    Learn More
                </a>
            </div>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
