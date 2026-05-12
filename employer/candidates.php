<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('employer');

$pageTitle = 'Browse Candidates';
$search = trim((string) ($_GET['q'] ?? ''));
$location = trim((string) ($_GET['location'] ?? ''));

$query = '
    SELECT cp.*,
           (
               SELECT cs.skill_name
               FROM candidate_skills cs
               WHERE cs.profile_id = cp.id
               ORDER BY cs.id ASC
               LIMIT 1
           ) AS primary_skill
    FROM candidate_profiles cp
    WHERE cp.status = "Approved"
';

$params = [];

if ($search !== '') {
    $query .= ' AND (cp.bio LIKE :search OR cp.headline LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($location !== '') {
    $query .= ' AND cp.location LIKE :location';
    $params['location'] = '%' . $location . '%';
}

$query .= ' ORDER BY cp.is_featured DESC, cp.approved_at DESC';

$statement = $pdo->prepare($query);
$statement->execute($params);
$candidates = $statement->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="form-card">
    <div class="section-head">
        <div>
            <h2>Anonymous Candidate Directory</h2>
            <p>Contact details, CVs, and identity stay hidden. Use admin-mediated requests only.</p>
        </div>
    </div>
    <form method="get" class="search-bar">
        <input type="text" name="q" placeholder="Search profile summary" value="<?= e($search) ?>">
        <input type="text" name="location" placeholder="Location" value="<?= e($location) ?>">
        <div></div>
        <button class="button button-dark" type="submit">Filter Directory</button>
    </form>
</section>

<section class="cards-grid">
    <?php foreach ($candidates as $candidate): ?>
        <?php $summary = candidate_public_summary($candidate); ?>
        <article class="card">
            <span class="candidate-code-badge"><?= e($summary['candidate_code']) ?></span>
            <h3><?= e($summary['primary_skill']) ?></h3>
            <p><?= e(truncate_text($summary['bio'], 140)) ?></p>
            <div class="inline-meta">
                <span><?= e($summary['experience_years']) ?> yrs</span>
                <span><?= e($summary['location']) ?></span>
                <span><?= e($summary['availability']) ?></span>
            </div>
            <div class="card-actions">
                <a class="ghost-button" href="<?= e(app_url('candidate-details.php?id=' . $candidate['id'])) ?>">Profile</a>
                <a class="button button-dark" href="<?= e(app_url('request-candidate.php?id=' . $candidate['id'])) ?>">Request Candidate</a>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if ($candidates === []): ?>
        <div class="empty-state">
            <h3>No matching candidates</h3>
            <p>Try a broader search or check again after more applicants are approved.</p>
        </div>
    <?php endif; ?>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
