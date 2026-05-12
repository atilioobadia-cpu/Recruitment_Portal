<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('applicant');

$pageTitle = 'My Applications';
$currentUser = current_user();

$statement = $pdo->prepare(
    'SELECT ja.*, j.title, j.location, j.employment_type
     FROM job_applications ja
     INNER JOIN jobs j ON j.id = ja.job_id
     WHERE ja.applicant_user_id = :user_id
     ORDER BY ja.created_at DESC'
);
$statement->execute(['user_id' => $currentUser['id']]);
$applications = $statement->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="table-card">
    <div class="section-head">
        <div>
            <h2>Job Applications</h2>
            <p>Your applications remain mediated by admin throughout the process.</p>
        </div>
        <a class="ghost-button" href="<?= e(app_url('jobs.php')) ?>">Find More Jobs</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Job</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Cover Message</th>
                    <th>Admin Notes</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= e($application['title']) ?></td>
                        <td><?= e($application['location']) ?> | <?= e($application['employment_type']) ?></td>
                        <td><span class="badge <?= e(status_badge_class($application['status'])) ?>"><?= e($application['status']) ?></span></td>
                        <td><?= e(truncate_text($application['cover_message'], 120)) ?></td>
                        <td><?= e($application['admin_notes'] ?: 'No notes yet') ?></td>
                        <td><?= e(format_date($application['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($applications === []): ?>
                    <tr>
                        <td colspan="6">You have not applied for any jobs yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
