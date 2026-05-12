<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Admin Dashboard';

$stats = [
    [
        'label' => 'Pending Profiles',
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM candidate_profiles WHERE status = "Pending"')->fetchColumn(),
        'icon' => 'user-check',
    ],
    [
        'label' => 'Published Jobs',
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM jobs WHERE status = "Published"')->fetchColumn(),
        'icon' => 'briefcase',
    ],
    [
        'label' => 'Open Requests',
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM employer_requests WHERE status IN ("Pending", "Reviewing", "Approved")')->fetchColumn(),
        'icon' => 'link',
    ],
    [
        'label' => 'Applications',
        'value' => (string) $pdo->query('SELECT COUNT(*) FROM job_applications')->fetchColumn(),
        'icon' => 'document',
    ],
];

$pendingApplicants = $pdo->query(
    'SELECT cp.id, cp.candidate_code, cp.location, cp.submitted_at, u.first_name, u.last_name
     FROM candidate_profiles cp
     INNER JOIN users u ON u.id = cp.user_id
     WHERE cp.status = "Pending"
     ORDER BY cp.submitted_at DESC
     LIMIT 6'
)->fetchAll();

$recentRequests = $pdo->query(
    'SELECT er.id, er.requirement_title, er.status, er.created_at, ep.company_name, cp.candidate_code
     FROM employer_requests er
     INNER JOIN employer_profiles ep ON ep.user_id = er.employer_user_id
     LEFT JOIN candidate_profiles cp ON cp.id = er.candidate_profile_id
     ORDER BY er.created_at DESC
     LIMIT 6'
)->fetchAll();

$recentApplications = $pdo->query(
    'SELECT ja.id, ja.status, ja.created_at, j.title, u.first_name, u.last_name
     FROM job_applications ja
     INNER JOIN jobs j ON j.id = ja.job_id
     INNER JOIN users u ON u.id = ja.applicant_user_id
     ORDER BY ja.created_at DESC
     LIMIT 6'
)->fetchAll();

$contactMessages = $pdo->query(
    'SELECT name, subject, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 5'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="dashboard-grid">
    <?php foreach ($stats as $stat): ?>
        <article class="metric-card">
            <span class="metric-icon"><?= ui_icon($stat['icon'], 'metric-icon-svg') ?></span>
            <div class="metric-value"><?= e($stat['value']) ?></div>
            <p><?= e($stat['label']) ?></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="two-column">
    <article class="table-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('user-check', 'section-icon section-icon-inline') ?>Pending Applicant Profiles</h2>
                <p>Profiles waiting for admin review and approval.</p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('admin/applicants.php')) ?>">Manage Applicants</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingApplicants as $applicant): ?>
                        <tr>
                            <td><?= e($applicant['candidate_code']) ?></td>
                            <td><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td>
                            <td><?= e($applicant['location'] ?: 'Not set') ?></td>
                            <td><?= e($applicant['submitted_at'] ? format_date($applicant['submitted_at']) : 'Not submitted') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($pendingApplicants === []): ?>
                        <tr>
                            <td colspan="4">No pending applicant profiles right now.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="table-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('link', 'section-icon section-icon-inline') ?>Recent Employer Requests</h2>
                <p>Track active demand from employer clients.</p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('admin/requests.php')) ?>">Manage Requests</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Company</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRequests as $request): ?>
                        <tr>
                            <td>
                                <strong><?= e($request['requirement_title']) ?></strong><br>
                                <?= e($request['candidate_code'] ?: 'General request') ?>
                            </td>
                            <td><?= e($request['company_name']) ?></td>
                            <td><span class="badge <?= e(status_badge_class($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentRequests === []): ?>
                        <tr>
                            <td colspan="3">No employer requests yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="two-column">
    <article class="table-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('document', 'section-icon section-icon-inline') ?>Recent Job Applications</h2>
                <p>Applicants apply through admin-managed jobs, not directly to employers.</p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('admin/applications.php')) ?>">Manage Applications</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentApplications as $application): ?>
                        <tr>
                            <td><?= e($application['first_name'] . ' ' . $application['last_name']) ?></td>
                            <td><?= e($application['title']) ?></td>
                            <td><span class="badge <?= e(status_badge_class($application['status'])) ?>"><?= e($application['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentApplications === []): ?>
                        <tr>
                            <td colspan="3">No job applications yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="table-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('mail', 'section-icon section-icon-inline') ?>Latest Contact Messages</h2>
                <p>Public contact page submissions arrive here for admin follow-up.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactMessages as $message): ?>
                        <tr>
                            <td><?= e($message['name']) ?></td>
                            <td><?= e($message['subject']) ?></td>
                            <td><?= e(format_date($message['created_at'], 'M d, Y h:i A')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($contactMessages === []): ?>
                        <tr>
                            <td colspan="3">No public contact messages yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
