<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('employer');

$pageTitle = 'My Requests';
$currentUser = current_user();

if (is_post()) {
    $requirementTitle = trim((string) ($_POST['requirement_title'] ?? ''));
    $requirementDetails = trim((string) ($_POST['requirement_details'] ?? ''));
    $messageToAdmin = trim((string) ($_POST['message_to_admin'] ?? ''));

    if ($requirementTitle === '' || $requirementDetails === '' || $messageToAdmin === '') {
        set_flash('error', 'Please complete the hiring request form.');
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO employer_requests
                (employer_user_id, candidate_profile_id, requirement_title, requirement_details, message_to_admin, status, created_at, updated_at)
             VALUES
                (:employer_user_id, NULL, :requirement_title, :requirement_details, :message_to_admin, "Pending", NOW(), NOW())'
        );
        $statement->execute([
            'employer_user_id' => $currentUser['id'],
            'requirement_title' => $requirementTitle,
            'requirement_details' => $requirementDetails,
            'message_to_admin' => $messageToAdmin,
        ]);

        create_notification(
            $pdo,
            (int) $currentUser['id'],
            'Hiring request submitted',
            'Your general hiring requirement has been sent to admin for review.'
        );

        set_flash('success', 'Hiring request submitted.');
        redirect('employer/requests.php');
    }
}

$statement = $pdo->prepare(
    'SELECT er.*, cp.candidate_code
     FROM employer_requests er
     LEFT JOIN candidate_profiles cp ON cp.id = er.candidate_profile_id
     WHERE er.employer_user_id = :user_id
     ORDER BY er.created_at DESC'
);
$statement->execute(['user_id' => $currentUser['id']]);
$requests = $statement->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="form-card">
        <div class="section-head">
            <div>
                <h2>Post Hiring Requirement</h2>
                <p>Submit your talent need to admin. Candidate matching and communication remain brokered.</p>
            </div>
        </div>
        <form method="post">
            <div class="form-grid">
                <div class="field full">
                    <label for="requirement_title">Requirement Title</label>
                    <input id="requirement_title" name="requirement_title" type="text" required>
                </div>
                <div class="field full">
                    <label for="requirement_details">Requirement Details</label>
                    <textarea id="requirement_details" name="requirement_details" required></textarea>
                </div>
                <div class="field full">
                    <label for="message_to_admin">Message to Admin</label>
                    <textarea id="message_to_admin" name="message_to_admin" required></textarea>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit">Submit Requirement</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Privacy Workflow</h2>
        <ul class="list-clean">
            <li>Browse anonymous candidates without seeing direct personal identity.</li>
            <li>Request a specific candidate or submit a general hiring need to admin.</li>
            <li>Admin reviews, coordinates separately, and decides whether to connect both sides.</li>
            <li>Status updates appear here and in your dashboard notifications.</li>
        </ul>
    </article>
</section>

<section class="table-card">
    <div class="section-head">
        <div>
            <h2>Request History</h2>
            <p>Track every employer request through admin review and connection stages.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Candidate</th>
                    <th>Status</th>
                    <th>Admin Notes</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>
                            <strong><?= e($request['requirement_title']) ?></strong><br>
                            <?= e(truncate_text($request['requirement_details'], 90)) ?>
                        </td>
                        <td><?= e($request['candidate_code'] ?: 'General request') ?></td>
                        <td><span class="badge <?= e(status_badge_class($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                        <td><?= e($request['admin_notes'] ?: 'No notes yet') ?></td>
                        <td><?= e(format_date($request['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($requests === []): ?>
                    <tr>
                        <td colspan="5">No requests have been submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
