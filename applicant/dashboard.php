<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('applicant');

$pageTitle = 'Applicant Dashboard';
$currentUser = current_user();
$profile = ensure_candidate_profile($pdo, (int) $currentUser['id']);

$applicationStatement = $pdo->prepare('SELECT COUNT(*) FROM job_applications WHERE applicant_user_id = :user_id');
$applicationStatement->execute(['user_id' => $currentUser['id']]);
$applicationCount = (int) $applicationStatement->fetchColumn();

$openJobs = (int) $pdo->query('SELECT COUNT(*) FROM jobs WHERE status = "Published"')->fetchColumn();
$approvedCandidates = (int) $pdo->query('SELECT COUNT(*) FROM candidate_profiles WHERE status = "Approved"')->fetchColumn();

$recentApplicationsStatement = $pdo->prepare(
    'SELECT ja.*, j.title
     FROM job_applications ja
     INNER JOIN jobs j ON j.id = ja.job_id
     WHERE ja.applicant_user_id = :user_id
     ORDER BY ja.created_at DESC
     LIMIT 5'
);
$recentApplicationsStatement->execute(['user_id' => $currentUser['id']]);
$recentApplications = $recentApplicationsStatement->fetchAll();

$latestJobs = $pdo->query(
    'SELECT id, title, location, employment_type, created_at
     FROM jobs
     WHERE status = "Published"
     ORDER BY created_at DESC
     LIMIT 4'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="dashboard-grid">
    <article class="metric-card">
        <span class="metric-icon">P</span>
        <div class="metric-value"><?= e($profile['status']) ?></div>
        <p>Profile approval status</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">A</span>
        <div class="metric-value"><?= e((string) $applicationCount) ?></div>
        <p>Applications submitted</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">J</span>
        <div class="metric-value"><?= e((string) $openJobs) ?></div>
        <p>Open public jobs</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">C</span>
        <div class="metric-value"><?= e((string) $approvedCandidates) ?></div>
        <p>Approved candidates listed</p>
    </article>
</section>

<section class="two-column">
    <article class="panel">
        <div class="section-head">
            <div>
                <h2>Profile Snapshot</h2>
                <p>Keep your profile complete and submitted so admin can review it.</p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('applicant/profile.php')) ?>">Edit Profile</a>
        </div>
        <ul class="list-clean">
            <li><strong>Candidate Code:</strong> <?= e($profile['candidate_code']) ?></li>
            <li><strong>Status:</strong> <span class="badge <?= e(status_badge_class($profile['status'])) ?>"><?= e($profile['status']) ?></span></li>
            <li><strong>Location:</strong> <?= e($profile['location'] ?: 'Not added yet') ?></li>
            <li><strong>Availability:</strong> <?= e($profile['availability'] ?: 'Not added yet') ?></li>
            <li><strong>Submitted:</strong> <?= e($profile['submitted_at'] ? format_date($profile['submitted_at']) : 'Not yet submitted') ?></li>
        </ul>
    </article>

    <article class="panel">
        <div class="section-head">
            <div>
                <h2>Latest Jobs</h2>
                <p>Public jobs you can review and apply for through the protected flow.</p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('jobs.php')) ?>">View All Jobs</a>
        </div>
        <ul class="list-clean">
            <?php foreach ($latestJobs as $job): ?>
                <li>
                    <strong><?= e($job['title']) ?></strong><br>
                    <span class="muted"><?= e($job['location']) ?> | <?= e($job['employment_type']) ?> | <?= e(format_date($job['created_at'])) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if ($latestJobs === []): ?>
                <li>No jobs are published right now.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<section class="table-card">
    <div class="section-head">
        <div>
            <h2>Recent Applications</h2>
            <p>Admin reviews these applications before any employer-side progression happens.</p>
        </div>
        <a class="ghost-button" href="<?= e(app_url('applicant/applications.php')) ?>">All Applications</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Job</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Admin Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentApplications as $application): ?>
                    <tr>
                        <td><?= e($application['title']) ?></td>
                        <td><span class="badge <?= e(status_badge_class($application['status'])) ?>"><?= e($application['status']) ?></span></td>
                        <td><?= e(format_date($application['created_at'])) ?></td>
                        <td><?= e($application['admin_notes'] ?: 'No notes yet') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recentApplications === []): ?>
                    <tr>
                        <td colspan="4">You have not submitted any applications yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
