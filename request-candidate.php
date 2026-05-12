<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_role('employer');

$currentUser = current_user();
$candidateId = (int) ($_GET['id'] ?? $_POST['candidate_id'] ?? 0);
$candidate = null;

if ($candidateId > 0) {
    $candidateStatement = $pdo->prepare(
        'SELECT cp.*,
                (
                    SELECT cs.skill_name
                    FROM candidate_skills cs
                    WHERE cs.profile_id = cp.id
                    ORDER BY cs.id ASC
                    LIMIT 1
                ) AS primary_skill
         FROM candidate_profiles cp
         WHERE cp.id = :id AND cp.status = "Approved"
         LIMIT 1'
    );
    $candidateStatement->execute(['id' => $candidateId]);
    $candidate = $candidateStatement->fetch();

    if ($candidate === false) {
        set_flash('error', 'That candidate is not currently available.');
        redirect('employer/candidates.php');
    }
}

$pageTitle = 'Request Candidate';

if (is_post()) {
    $requirementTitle = trim((string) ($_POST['requirement_title'] ?? ''));
    $requirementDetails = trim((string) ($_POST['requirement_details'] ?? ''));
    $messageToAdmin = trim((string) ($_POST['message_to_admin'] ?? ''));

    if ($requirementTitle === '' || $requirementDetails === '' || $messageToAdmin === '') {
        set_flash('error', 'Please complete the request form.');
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO employer_requests
                (employer_user_id, candidate_profile_id, requirement_title, requirement_details, message_to_admin, status, created_at, updated_at)
             VALUES
                (:employer_user_id, :candidate_profile_id, :requirement_title, :requirement_details, :message_to_admin, "Pending", NOW(), NOW())'
        );
        $statement->execute([
            'employer_user_id' => $currentUser['id'],
            'candidate_profile_id' => $candidate['id'] ?? null,
            'requirement_title' => $requirementTitle,
            'requirement_details' => $requirementDetails,
            'message_to_admin' => $messageToAdmin,
        ]);

        create_notification(
            $pdo,
            (int) $currentUser['id'],
            'Candidate request submitted',
            'Your request has been sent to admin for review. Direct candidate contact remains restricted.'
        );

        set_flash('success', 'Your request has been sent to admin.');
        redirect('employer/requests.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container auth-shell">
        <span class="eyebrow">Employer request</span>
        <h1><?= $candidate ? 'Request ' . e($candidate['candidate_code']) : 'Submit a Hiring Request' ?></h1>
        <p>All candidate and employer connections are reviewed by admin before any direct disclosure can happen.</p>

        <?php if ($candidate): ?>
            <?php $summary = candidate_public_summary($candidate); ?>
            <article class="panel" style="margin-bottom: 1rem;">
                <span class="candidate-code-badge"><?= e($summary['candidate_code']) ?></span>
                <h3><?= e($summary['primary_skill']) ?> Candidate</h3>
                <p><?= e(truncate_text($summary['bio'], 150)) ?></p>
                <div class="inline-meta">
                    <span><?= e($summary['location']) ?></span>
                    <span><?= e($summary['experience_years']) ?> yrs</span>
                    <span><?= e($summary['availability']) ?></span>
                </div>
            </article>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="candidate_id" value="<?= e((string) $candidateId) ?>">
            <div class="form-grid">
                <div class="field full">
                    <label for="requirement_title">Requirement Title</label>
                    <input id="requirement_title" name="requirement_title" type="text" value="<?= e(old('requirement_title')) ?>" required>
                </div>
                <div class="field full">
                    <label for="requirement_details">Requirement Details</label>
                    <textarea id="requirement_details" name="requirement_details" required><?= e(old('requirement_details')) ?></textarea>
                </div>
                <div class="field full">
                    <label for="message_to_admin">Message to Admin</label>
                    <textarea id="message_to_admin" name="message_to_admin" required><?= e(old('message_to_admin')) ?></textarea>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1.2rem;">
                <button class="button button-dark" type="submit">Submit Request</button>
                <a class="ghost-button" href="<?= e(app_url('employer/requests.php')) ?>">View My Requests</a>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
