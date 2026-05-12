<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$candidateId = (int) ($_GET['id'] ?? 0);

$statement = $pdo->prepare(
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
$statement->execute(['id' => $candidateId]);
$candidate = $statement->fetch();

if ($candidate === false) {
    set_flash('error', 'That candidate profile is not available.');
    redirect('candidates.php');
}

$pageTitle = $candidate['candidate_code'];
$currentUser = current_user();
$summary = candidate_public_summary($candidate);

$skillsStatement = $pdo->prepare(
    'SELECT skill_name
     FROM candidate_skills
     WHERE profile_id = :profile_id
     ORDER BY skill_name'
);
$skillsStatement->execute(['profile_id' => $candidate['id']]);
$skills = $skillsStatement->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container detail-grid">
        <article class="panel">
            <span class="candidate-code-badge"><?= e($summary['candidate_code']) ?></span>
            <h1><?= e($summary['primary_skill']) ?> Candidate</h1>
            <p><?= nl2br(e($summary['bio'])) ?></p>

            <div class="inline-meta">
                <span><?= e($summary['experience_years']) ?> years experience</span>
                <span><?= e($summary['education_level']) ?></span>
                <span><?= e($summary['location']) ?></span>
                <span><?= e($summary['salary_range']) ?></span>
                <span><?= e($summary['availability']) ?></span>
            </div>
        </article>

        <article class="panel">
            <h2>Visible Information</h2>
            <ul class="list-clean">
                <li><strong>Candidate Code:</strong> <?= e($summary['candidate_code']) ?></li>
                <li><strong>Primary Skill:</strong> <?= e($summary['primary_skill']) ?></li>
                <li><strong>Experience:</strong> <?= e($summary['experience_years']) ?> years</li>
                <li><strong>Education:</strong> <?= e($summary['education_level']) ?></li>
                <li><strong>Location:</strong> <?= e($summary['location']) ?></li>
                <li><strong>Expected Salary:</strong> <?= e($summary['salary_range']) ?></li>
                <li><strong>Availability:</strong> <?= e($summary['availability']) ?></li>
            </ul>

            <?php if ($skills !== []): ?>
                <h3>Skills</h3>
                <div class="inline-meta">
                    <?php foreach ($skills as $skill): ?>
                        <span><?= e($skill['skill_name']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card-actions" style="margin-top: 1.2rem;">
                <?php if ($currentUser !== null && $currentUser['role'] === 'employer'): ?>
                    <a class="button button-dark" href="<?= e(app_url('request-candidate.php?id=' . $candidate['id'])) ?>">Request This Candidate</a>
                <?php else: ?>
                    <a class="button button-dark" href="<?= e(app_url('login.php')) ?>">Login to Request</a>
                <?php endif; ?>
                <a class="ghost-button" href="<?= e(app_url('candidates.php')) ?>">Back to Candidates</a>
            </div>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
