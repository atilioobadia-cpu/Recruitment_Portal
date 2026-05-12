<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_role('applicant');

$currentUser = current_user();
$jobId = (int) ($_GET['id'] ?? $_POST['job_id'] ?? 0);

$jobStatement = $pdo->prepare('SELECT * FROM jobs WHERE id = :id AND status = "Published" LIMIT 1');
$jobStatement->execute(['id' => $jobId]);
$job = $jobStatement->fetch();

if ($job === false) {
    set_flash('error', 'That job post is no longer available.');
    redirect('jobs.php');
}

$profile = ensure_candidate_profile($pdo, (int) $currentUser['id']);

if (in_array($profile['status'], ['Draft', 'Rejected'], true)) {
    set_flash('error', 'Please complete and submit your profile for review before applying.');
    redirect('applicant/profile.php');
}

$duplicateCheck = $pdo->prepare(
    'SELECT id
     FROM job_applications
     WHERE job_id = :job_id AND applicant_user_id = :applicant_user_id
     LIMIT 1'
);
$duplicateCheck->execute([
    'job_id' => $jobId,
    'applicant_user_id' => $currentUser['id'],
]);

if ($duplicateCheck->fetch() !== false) {
    set_flash('warning', 'You already applied for this job.');
    redirect('applicant/applications.php');
}

$pageTitle = 'Apply for Job';

if (is_post()) {
    $coverMessage = trim((string) ($_POST['cover_message'] ?? ''));

    if ($coverMessage === '') {
        set_flash('error', 'Please add a short message to support your application.');
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO job_applications
                (job_id, applicant_user_id, profile_id, cover_message, status, created_at, updated_at)
             VALUES
                (:job_id, :applicant_user_id, :profile_id, :cover_message, "Pending", NOW(), NOW())'
        );
        $statement->execute([
            'job_id' => $jobId,
            'applicant_user_id' => $currentUser['id'],
            'profile_id' => $profile['id'],
            'cover_message' => $coverMessage,
        ]);

        create_notification(
            $pdo,
            (int) $currentUser['id'],
            'Job application submitted',
            'Your application for "' . $job['title'] . '" has been submitted to admin for review.'
        );

        set_flash('success', 'Your job application has been submitted.');
        redirect('applicant/applications.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container auth-shell">
        <span class="eyebrow">Applicant action</span>
        <h1>Apply for <?= e($job['title']) ?></h1>
        <p>Your application is submitted to admin. Employer contact details stay hidden unless admin approves the next stage.</p>

        <form method="post">
            <input type="hidden" name="job_id" value="<?= e((string) $job['id']) ?>">
            <div class="form-grid">
                <div class="field full">
                    <label for="cover_message">Why are you a good fit?</label>
                    <textarea id="cover_message" name="cover_message" required><?= e(old('cover_message')) ?></textarea>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1.2rem;">
                <button class="button button-dark" type="submit">Submit Application</button>
                <a class="ghost-button" href="<?= e(app_url('job-details.php?id=' . $job['id'])) ?>">Back to Job</a>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
