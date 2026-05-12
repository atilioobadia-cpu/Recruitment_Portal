<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('employer');

$pageTitle = 'Employer Dashboard';
$currentUser = current_user();

$requestStatement = $pdo->prepare('SELECT COUNT(*) FROM employer_requests WHERE employer_user_id = :user_id');
$requestStatement->execute(['user_id' => $currentUser['id']]);
$requestCount = (int) $requestStatement->fetchColumn();

$candidateCount = (int) $pdo->query('SELECT COUNT(*) FROM candidate_profiles WHERE status = "Approved"')->fetchColumn();
$publishedJobs = (int) $pdo->query('SELECT COUNT(*) FROM jobs WHERE status = "Published"')->fetchColumn();

$profileStatement = $pdo->prepare('SELECT * FROM employer_profiles WHERE user_id = :user_id LIMIT 1');
$profileStatement->execute(['user_id' => $currentUser['id']]);
$employerProfile = $profileStatement->fetch();

$recentRequestsStatement = $pdo->prepare(
    'SELECT er.*, cp.candidate_code
     FROM employer_requests er
     LEFT JOIN candidate_profiles cp ON cp.id = er.candidate_profile_id
     WHERE er.employer_user_id = :user_id
     ORDER BY er.created_at DESC
     LIMIT 5'
);
$recentRequestsStatement->execute(['user_id' => $currentUser['id']]);
$recentRequests = $recentRequestsStatement->fetchAll();

$latestCandidates = $pdo->query(
    'SELECT cp.id, cp.candidate_code, cp.location, cp.experience_years, cp.availability,
            (
                SELECT cs.skill_name
                FROM candidate_skills cs
                WHERE cs.profile_id = cp.id
                ORDER BY cs.id ASC
                LIMIT 1
            ) AS primary_skill
     FROM candidate_profiles cp
     WHERE cp.status = "Approved"
     ORDER BY cp.is_featured DESC, cp.approved_at DESC
     LIMIT 4'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="dashboard-grid">
    <article class="metric-card">
        <span class="metric-icon">R</span>
        <div class="metric-value"><?= e((string) $requestCount) ?></div>
        <p>Total requests submitted</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">C</span>
        <div class="metric-value"><?= e((string) $candidateCount) ?></div>
        <p>Approved candidates available</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">J</span>
        <div class="metric-value"><?= e((string) $publishedJobs) ?></div>
        <p>Public jobs on the platform</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">P</span>
        <div class="metric-value"><?= e($currentUser['company_name'] ?: 'Client') ?></div>
        <p>Registered employer identity</p>
    </article>
</section>

<section class="two-column">
    <article class="panel">
        <div class="section-head">
            <div>
                <h2>Company Snapshot</h2>
                <p>Employer details are available to admin, while applicants remain protected from direct access.</p>
            </div>
        </div>
        <ul class="list-clean">
            <li><strong>Company:</strong> <?= e($employerProfile['company_name'] ?? 'Not added') ?></li>
            <li><strong>Industry:</strong> <?= e($employerProfile['industry'] ?? 'Not added') ?></li>
            <li><strong>Location:</strong> <?= e($employerProfile['location'] ?? 'Not added') ?></li>
            <li><strong>Contact Person:</strong> <?= e($employerProfile['contact_person'] ?? $currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></li>
        </ul>
    </article>

    <article class="panel">
        <div class="section-head">
            <div>
                <h2>Quick Actions</h2>
                <p>Submit structured requests instead of contacting candidates directly.</p>
            </div>
        </div>
        <div class="card-actions">
            <a class="button button-dark" href="<?= e(app_url('employer/candidates.php')) ?>">Browse Candidates</a>
            <a class="ghost-button" href="<?= e(app_url('employer/requests.php')) ?>">Post Hiring Request</a>
        </div>
    </article>
</section>

<section class="two-column">
    <article class="table-card">
        <div class="section-head">
            <div>
                <h2>Recent Requests</h2>
                <p>Status updates come from admin as each request moves through review.</p>
            </div>
            <a class="ghost-button" href="<?= e(app_url('employer/requests.php')) ?>">All Requests</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Candidate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentRequests as $request): ?>
                        <tr>
                            <td><?= e($request['requirement_title']) ?></td>
                            <td><?= e($request['candidate_code'] ?: 'General request') ?></td>
                            <td><span class="badge <?= e(status_badge_class($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentRequests === []): ?>
                        <tr>
                            <td colspan="3">No requests submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="table-card">
        <div class="section-head">
            <div>
                <h2>Latest Approved Candidates</h2>
                <p>Anonymous preview only. Full identity remains private unless admin approves a connection.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Skill</th>
                        <th>Experience</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestCandidates as $candidate): ?>
                        <tr>
                            <td><?= e($candidate['candidate_code']) ?></td>
                            <td><?= e($candidate['primary_skill'] ?: 'General Skills') ?></td>
                            <td><?= e((string) $candidate['experience_years']) ?> years</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($latestCandidates === []): ?>
                        <tr>
                            <td colspan="3">No approved candidates found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
