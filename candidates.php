<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$pageTitle = 'Available Candidates';
$pageIntroText = site_setting(
    $pdo,
    'candidates.banner_intro',
    'Public and employer-visible candidate summaries remain anonymous until admin approves deeper access.'
);
$search = trim((string) ($_GET['q'] ?? ''));
$location = trim((string) ($_GET['location'] ?? ''));
$availability = trim((string) ($_GET['availability'] ?? ''));

$query = '
    SELECT cp.*,
           (
               SELECT cs.skill_name
               FROM candidate_skills cs
               WHERE cs.profile_id = cp.id
               ORDER BY cs.id ASC
               LIMIT 1
           ) AS primary_skill
    FROM candidate_profiles cp
    WHERE cp.status = "Approved"
';

$params = [];

if ($search !== '') {
    $query .= ' AND (cp.bio LIKE :search OR cp.headline LIKE :search OR cp.location LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($location !== '') {
    $query .= ' AND cp.location LIKE :location';
    $params['location'] = '%' . $location . '%';
}

if ($availability !== '') {
    $query .= ' AND cp.availability = :availability';
    $params['availability'] = $availability;
}

$query .= ' ORDER BY cp.is_featured DESC, cp.approved_at DESC, cp.created_at DESC';

$statement = $pdo->prepare($query);
$statement->execute($params);
$candidates = $statement->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h1><?= ui_icon('users', 'section-icon section-icon-inline') ?>Available Candidates</h1>
                <p><?= e($pageIntroText) ?></p>
            </div>
        </div>

        <form class="form-card search-bar" method="get">
            <input type="text" name="q" placeholder="Search summary or keyword" value="<?= e($search) ?>">
            <input type="text" name="location" placeholder="Location" value="<?= e($location) ?>">
            <select name="availability">
                <option value="">Any availability</option>
                <option value="Immediate" <?= selected_option($availability, 'Immediate') ?>>Immediate</option>
                <option value="2 Weeks" <?= selected_option($availability, '2 Weeks') ?>>2 Weeks</option>
                <option value="1 Month" <?= selected_option($availability, '1 Month') ?>>1 Month</option>
                <option value="Open" <?= selected_option($availability, 'Open') ?>>Open</option>
            </select>
            <button class="button button-dark" type="submit">
                <?= ui_icon('search', 'button-icon') ?>
                Filter Candidates
            </button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container cards-grid">
        <?php foreach ($candidates as $candidate): ?>
            <?php $summary = candidate_public_summary($candidate); ?>
            <article class="card">
                <span class="candidate-code-badge"><?= e($summary['candidate_code']) ?></span>
                <div class="card-title-row">
                    <span class="icon-badge"><?= ui_icon('user-check', 'card-inline-icon') ?></span>
                    <h3><?= e($summary['primary_skill']) ?></h3>
                </div>
                <p><?= e(truncate_text($summary['bio'], 160)) ?></p>
                <div class="inline-meta">
                    <span><?= ui_icon('chart', 'ui-icon') ?><?= e($summary['experience_years']) ?> yrs</span>
                    <span><?= ui_icon('document', 'ui-icon') ?><?= e($summary['education_level']) ?></span>
                    <span><?= ui_icon('map', 'ui-icon') ?><?= e($summary['location']) ?></span>
                    <span><?= ui_icon('check', 'ui-icon') ?><?= e($summary['availability']) ?></span>
                </div>
                <div class="card-actions">
                    <a class="button button-dark" href="<?= e(app_url('candidate-details.php?id=' . $candidate['id'])) ?>">
                        <?= ui_icon('document', 'button-icon') ?>
                        View Candidate
                    </a>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($candidates === []): ?>
            <div class="empty-state">
                <h3><?= ui_icon('users', 'section-icon section-icon-inline') ?>No approved candidates found</h3>
                <p>Once applicant profiles are approved by admin, they will appear here as anonymous worker cards.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
