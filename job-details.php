<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$jobId = (int) ($_GET['id'] ?? 0);
$statement = $pdo->prepare(
    'SELECT j.*, sc.name AS category_name
     FROM jobs j
     LEFT JOIN skill_categories sc ON sc.id = j.category_id
     WHERE j.id = :id AND j.status = "Published"
     LIMIT 1'
);
$statement->execute(['id' => $jobId]);
$job = $statement->fetch();

if ($job === false) {
    set_flash('error', 'The requested job post could not be found.');
    redirect('jobs.php');
}

$pageTitle = $job['title'];
$currentUser = current_user();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container detail-grid">
        <article class="panel">
            <div class="stack-row">
                <span class="badge <?= e(status_badge_class($job['status'])) ?>"><?= e($job['status']) ?></span>
                <span class="badge badge-neutral"><?= e($job['employment_type']) ?></span>
                <span class="badge badge-neutral"><?= e($job['category_name'] ?: 'General') ?></span>
            </div>
            <h1><?= e($job['title']) ?></h1>
            <p><?= nl2br(e($job['description'])) ?></p>
        </article>

        <article class="panel">
            <h2>Role Summary</h2>
            <ul class="list-clean">
                <li><strong>Location:</strong> <?= e($job['location']) ?></li>
                <li><strong>Salary:</strong> <?= e(salary_range_display($job['salary_min'], $job['salary_max'])) ?></li>
                <li><strong>Published:</strong> <?= e(format_date($job['created_at'])) ?></li>
                <li><strong>Requirements:</strong><br><?= nl2br(e($job['requirements'])) ?></li>
            </ul>

            <div class="card-actions">
                <?php if ($currentUser !== null && $currentUser['role'] === 'applicant'): ?>
                    <a class="button button-dark" href="<?= e(app_url('apply-job.php?id=' . $job['id'])) ?>">Apply for This Job</a>
                <?php else: ?>
                    <a class="button button-dark" href="<?= e(app_url('login.php')) ?>">Login to Apply</a>
                <?php endif; ?>
                <a class="ghost-button" href="<?= e(app_url('jobs.php')) ?>">Back to Jobs</a>
            </div>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
