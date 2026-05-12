<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$pageTitle = 'Jobs';
$pageIntroText = site_setting(
    $pdo,
    'jobs.banner_intro',
    'Publicly visible opportunities managed by admin. Login is required before an applicant can apply.'
);
$search = trim((string) ($_GET['q'] ?? ''));
$location = trim((string) ($_GET['location'] ?? ''));
$employmentType = trim((string) ($_GET['type'] ?? ''));

$query = '
    SELECT j.*, sc.name AS category_name
    FROM jobs j
    LEFT JOIN skill_categories sc ON sc.id = j.category_id
    WHERE j.status = "Published"
';

$params = [];

if ($search !== '') {
    $query .= ' AND (j.title LIKE :search OR j.summary LIKE :search OR j.description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($location !== '') {
    $query .= ' AND j.location LIKE :location';
    $params['location'] = '%' . $location . '%';
}

if ($employmentType !== '') {
    $query .= ' AND j.employment_type = :employment_type';
    $params['employment_type'] = $employmentType;
}

$query .= ' ORDER BY j.is_featured DESC, j.created_at DESC';

$statement = $pdo->prepare($query);
$statement->execute($params);
$jobs = $statement->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h1><?= ui_icon('briefcase', 'section-icon section-icon-inline') ?>Open Jobs</h1>
                <p><?= e($pageIntroText) ?></p>
            </div>
        </div>

        <form class="form-card search-bar" method="get">
            <input type="text" name="q" placeholder="Search job title or keyword" value="<?= e($search) ?>">
            <input type="text" name="location" placeholder="Location" value="<?= e($location) ?>">
            <select name="type">
                <option value="">All types</option>
                <option value="Full Time" <?= selected_option($employmentType, 'Full Time') ?>>Full Time</option>
                <option value="Part Time" <?= selected_option($employmentType, 'Part Time') ?>>Part Time</option>
                <option value="Contract" <?= selected_option($employmentType, 'Contract') ?>>Contract</option>
                <option value="Temporary" <?= selected_option($employmentType, 'Temporary') ?>>Temporary</option>
            </select>
            <button class="button button-dark" type="submit">
                <?= ui_icon('search', 'button-icon') ?>
                Filter Jobs
            </button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container cards-grid">
        <?php foreach ($jobs as $job): ?>
            <article class="card">
                <div class="stack-row">
                    <span class="badge <?= e(status_badge_class($job['status'])) ?>"><?= e($job['status']) ?></span>
                    <span class="badge badge-neutral"><?= e($job['employment_type']) ?></span>
                </div>
                <div class="card-title-row">
                    <span class="icon-badge"><?= ui_icon('briefcase', 'card-inline-icon') ?></span>
                    <h3><?= e($job['title']) ?></h3>
                </div>
                <p><?= e(truncate_text($job['summary'], 160)) ?></p>
                <div class="inline-meta">
                    <span><?= ui_icon('map', 'ui-icon') ?><?= e($job['location']) ?></span>
                    <span><?= ui_icon('category', 'ui-icon') ?><?= e($job['category_name'] ?: 'General') ?></span>
                    <span><?= ui_icon('chart', 'ui-icon') ?><?= e(salary_range_display($job['salary_min'], $job['salary_max'])) ?></span>
                </div>
                <div class="card-actions">
                    <a class="button button-dark" href="<?= e(app_url('job-details.php?id=' . $job['id'])) ?>">
                        <?= ui_icon('document', 'button-icon') ?>
                        Job Details
                    </a>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($jobs === []): ?>
            <div class="empty-state">
                <h3><?= ui_icon('briefcase', 'section-icon section-icon-inline') ?>No jobs matched your filters</h3>
                <p>Try adjusting your search or check back after admin publishes more roles.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
