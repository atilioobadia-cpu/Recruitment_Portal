<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Manage Requests';
$statusOptions = ['Pending', 'Reviewing', 'Approved', 'Rejected', 'Connected', 'Closed'];

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $status = normalize_status((string) ($_POST['status'] ?? ''), $statusOptions, 'Pending');
        $candidateProfileId = $_POST['candidate_profile_id'] !== '' ? (int) $_POST['candidate_profile_id'] : null;
        $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));

        $requestStatement = $pdo->prepare(
            'SELECT employer_user_id
             FROM employer_requests
             WHERE id = :request_id
             LIMIT 1'
        );
        $requestStatement->execute(['request_id' => $requestId]);
        $requestInfo = $requestStatement->fetch();

        $candidateUserId = null;
        if ($candidateProfileId !== null) {
            $candidateStatement = $pdo->prepare(
                'SELECT user_id
                 FROM candidate_profiles
                 WHERE id = :candidate_profile_id
                 LIMIT 1'
            );
            $candidateStatement->execute(['candidate_profile_id' => $candidateProfileId]);
            $candidateUserId = $candidateStatement->fetchColumn() ?: null;
        }

        $statement = $pdo->prepare(
            'UPDATE employer_requests
             SET candidate_profile_id = :candidate_profile_id,
                 status = :status,
                 admin_notes = :admin_notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'candidate_profile_id' => $candidateProfileId,
            'status' => $status,
            'admin_notes' => $adminNotes,
            'id' => $requestId,
        ]);

        if ($requestInfo !== false) {
            create_notification(
                $pdo,
                (int) $requestInfo['employer_user_id'],
                'Employer request update',
                'Your request status is now ' . $status . '. ' . $adminNotes
            );

            if ($candidateUserId !== null) {
                create_notification(
                    $pdo,
                    (int) $candidateUserId,
                    'Candidate interest update',
                    'Admin updated a brokered employer request linked to your profile. Status: ' . $status . '.'
                );
            }
        }

        set_flash('success', 'Employer request updated.');
        redirect('admin/requests.php?view=' . $requestId);
    }
}

$requests = $pdo->query(
    'SELECT er.id, er.requirement_title, er.status, er.created_at,
            ep.company_name, cp.candidate_code
     FROM employer_requests er
     INNER JOIN employer_profiles ep ON ep.user_id = er.employer_user_id
     LEFT JOIN candidate_profiles cp ON cp.id = er.candidate_profile_id
     ORDER BY er.created_at DESC'
)->fetchAll();

$approvedCandidates = $pdo->query(
    'SELECT cp.id, cp.candidate_code,
            (
                SELECT cs.skill_name
                FROM candidate_skills cs
                WHERE cs.profile_id = cp.id
                ORDER BY cs.id ASC
                LIMIT 1
            ) AS primary_skill
     FROM candidate_profiles cp
     WHERE cp.status = "Approved"
     ORDER BY cp.candidate_code ASC'
)->fetchAll();

$selectedRequest = null;
$viewId = (int) ($_GET['view'] ?? 0);
if ($viewId > 0) {
    $statement = $pdo->prepare(
        'SELECT er.*, u.first_name, u.last_name, u.email, u.phone, ep.company_name, ep.industry,
                cp.candidate_code
         FROM employer_requests er
         INNER JOIN users u ON u.id = er.employer_user_id
         LEFT JOIN employer_profiles ep ON ep.user_id = er.employer_user_id
         LEFT JOIN candidate_profiles cp ON cp.id = er.candidate_profile_id
         WHERE er.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $viewId]);
    $selectedRequest = $statement->fetch();
}

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="table-card">
        <div class="section-head">
            <div>
                <h2>Employer Requests</h2>
                <p>Every worker request is reviewed internally before any connection is made.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>
                                <strong><?= e($request['requirement_title']) ?></strong><br>
                                <?= e($request['candidate_code'] ?: 'General request') ?>
                            </td>
                            <td><?= e($request['company_name']) ?></td>
                            <td><span class="badge <?= e(status_badge_class($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                            <td><a class="ghost-button" href="<?= e(app_url('admin/requests.php?view=' . $request['id'])) ?>">Manage</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($requests === []): ?>
                        <tr>
                            <td colspan="4">No employer requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <?php if ($selectedRequest): ?>
            <div class="section-head">
                <div>
                    <h2>Request Review</h2>
                    <p>Keep the employer and candidate separated while you coordinate next steps.</p>
                </div>
                <span class="badge <?= e(status_badge_class($selectedRequest['status'])) ?>"><?= e($selectedRequest['status']) ?></span>
            </div>

            <ul class="list-clean">
                <li><strong>Company:</strong> <?= e($selectedRequest['company_name'] ?: 'Not set') ?></li>
                <li><strong>Employer Contact:</strong> <?= e($selectedRequest['first_name'] . ' ' . $selectedRequest['last_name']) ?></li>
                <li><strong>Email:</strong> <?= e($selectedRequest['email']) ?></li>
                <li><strong>Phone:</strong> <?= e($selectedRequest['phone']) ?></li>
                <li><strong>Requirement:</strong> <?= e($selectedRequest['requirement_title']) ?></li>
                <li><strong>Details:</strong><br><?= nl2br(e($selectedRequest['requirement_details'])) ?></li>
                <li><strong>Message to Admin:</strong><br><?= nl2br(e($selectedRequest['message_to_admin'])) ?></li>
            </ul>

            <form method="post" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="update_request">
                <input type="hidden" name="request_id" value="<?= e((string) $selectedRequest['id']) ?>">
                <div class="form-grid">
                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= e($status) ?>" <?= selected_option((string) $selectedRequest['status'], $status) ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="candidate_profile_id">Assign Candidate</label>
                        <select id="candidate_profile_id" name="candidate_profile_id">
                            <option value="">No candidate assigned</option>
                            <?php foreach ($approvedCandidates as $candidate): ?>
                                <option value="<?= e((string) $candidate['id']) ?>" <?= selected_option((string) $selectedRequest['candidate_profile_id'], (string) $candidate['id']) ?>>
                                    <?= e($candidate['candidate_code'] . ' - ' . ($candidate['primary_skill'] ?: 'General Skills')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="admin_notes">Admin Notes</label>
                        <textarea id="admin_notes" name="admin_notes"><?= e($selectedRequest['admin_notes']) ?></textarea>
                    </div>
                </div>
                <div class="card-actions" style="margin-top: 1rem;">
                    <button class="button button-dark" type="submit">Save Request Update</button>
                </div>
            </form>
        <?php else: ?>
            <h2>Select a request</h2>
            <p>Choose a request to review employer details, assign a candidate internally, and update brokered status.</p>
        <?php endif; ?>
    </article>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
