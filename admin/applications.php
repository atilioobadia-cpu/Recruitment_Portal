<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Manage Applications';
$statusOptions = ['Pending', 'Reviewing', 'Approved', 'Rejected', 'Connected', 'Closed'];

if (is_post()) {
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $status = normalize_status((string) ($_POST['status'] ?? ''), $statusOptions, 'Pending');
    $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));

    $statement = $pdo->prepare(
        'UPDATE job_applications
         SET status = :status,
             admin_notes = :admin_notes,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        'status' => $status,
        'admin_notes' => $adminNotes,
        'id' => $applicationId,
    ]);

    $applicantStatement = $pdo->prepare('SELECT applicant_user_id FROM job_applications WHERE id = :id LIMIT 1');
    $applicantStatement->execute(['id' => $applicationId]);
    $applicantUserId = $applicantStatement->fetchColumn();

    if ($applicantUserId !== false) {
        create_notification(
            $pdo,
            (int) $applicantUserId,
            'Application status update',
            'Admin updated one of your job applications to ' . $status . '. ' . $adminNotes
        );
    }

    set_flash('success', 'Job application updated.');
    redirect('admin/applications.php');
}

$applications = $pdo->query(
    'SELECT ja.*, j.title, cp.candidate_code, u.first_name, u.last_name
     FROM job_applications ja
     INNER JOIN jobs j ON j.id = ja.job_id
     INNER JOIN candidate_profiles cp ON cp.id = ja.profile_id
     INNER JOIN users u ON u.id = ja.applicant_user_id
     ORDER BY ja.created_at DESC'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="table-card">
    <div class="section-head">
        <div>
            <h2>Job Applications</h2>
            <p>Applications stay inside the admin-controlled brokerage workflow.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Candidate Code</th>
                    <th>Job</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td><?= e($application['first_name'] . ' ' . $application['last_name']) ?></td>
                        <td><?= e($application['candidate_code']) ?></td>
                        <td><?= e($application['title']) ?></td>
                        <td><?= e(truncate_text($application['cover_message'], 100)) ?></td>
                        <td><span class="badge <?= e(status_badge_class($application['status'])) ?>"><?= e($application['status']) ?></span></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="application_id" value="<?= e((string) $application['id']) ?>">
                                <div class="field">
                                    <select name="status">
                                        <?php foreach ($statusOptions as $status): ?>
                                            <option value="<?= e($status) ?>" <?= selected_option((string) $application['status'], $status) ?>><?= e($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field" style="margin-top: 0.5rem;">
                                    <textarea name="admin_notes"><?= e($application['admin_notes']) ?></textarea>
                                </div>
                                <button class="button button-dark" type="submit" style="margin-top: 0.5rem;">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($applications === []): ?>
                    <tr>
                        <td colspan="6">No job applications submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
