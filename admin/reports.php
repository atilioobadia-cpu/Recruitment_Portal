<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Reports';

$jobStatusReport = $pdo->query('SELECT status, COUNT(*) AS total FROM jobs GROUP BY status ORDER BY status')->fetchAll();
$profileStatusReport = $pdo->query('SELECT status, COUNT(*) AS total FROM candidate_profiles GROUP BY status ORDER BY status')->fetchAll();
$applicationStatusReport = $pdo->query('SELECT status, COUNT(*) AS total FROM job_applications GROUP BY status ORDER BY status')->fetchAll();
$requestStatusReport = $pdo->query('SELECT status, COUNT(*) AS total FROM employer_requests GROUP BY status ORDER BY status')->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="dashboard-grid">
    <article class="metric-card">
        <span class="metric-icon">J</span>
        <div class="metric-value"><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['total'], $jobStatusReport))) ?></div>
        <p>Total jobs</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">P</span>
        <div class="metric-value"><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['total'], $profileStatusReport))) ?></div>
        <p>Total applicant profiles</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">A</span>
        <div class="metric-value"><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['total'], $applicationStatusReport))) ?></div>
        <p>Total applications</p>
    </article>
    <article class="metric-card">
        <span class="metric-icon">R</span>
        <div class="metric-value"><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['total'], $requestStatusReport))) ?></div>
        <p>Total employer requests</p>
    </article>
</section>

<section class="two-column">
    <article class="table-card">
        <h2>Jobs by Status</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobStatusReport as $row): ?>
                        <tr>
                            <td><?= e($row['status']) ?></td>
                            <td><?= e((string) $row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="table-card">
        <h2>Profiles by Status</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profileStatusReport as $row): ?>
                        <tr>
                            <td><?= e($row['status']) ?></td>
                            <td><?= e((string) $row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="two-column">
    <article class="table-card">
        <h2>Applications by Status</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applicationStatusReport as $row): ?>
                        <tr>
                            <td><?= e($row['status']) ?></td>
                            <td><?= e((string) $row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="table-card">
        <h2>Requests by Status</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requestStatusReport as $row): ?>
                        <tr>
                            <td><?= e($row['status']) ?></td>
                            <td><?= e((string) $row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
