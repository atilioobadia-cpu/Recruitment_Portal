<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Manage Jobs';
$categories = fetch_skill_categories($pdo);

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_job') {
        $jobId = (int) ($_POST['job_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $employmentType = trim((string) ($_POST['employment_type'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $requirements = trim((string) ($_POST['requirements'] ?? ''));
        $status = normalize_status((string) ($_POST['status'] ?? ''), ['Draft', 'Published', 'Closed'], 'Draft');
        $categoryId = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
        $salaryMin = $_POST['salary_min'] !== '' ? (float) $_POST['salary_min'] : null;
        $salaryMax = $_POST['salary_max'] !== '' ? (float) $_POST['salary_max'] : null;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        if ($title === '' || $location === '' || $employmentType === '' || $summary === '' || $description === '') {
            set_flash('error', 'Please complete the required job fields.');
        } else {
            if ($jobId > 0) {
                $statement = $pdo->prepare(
                    'UPDATE jobs
                     SET title = :title,
                         category_id = :category_id,
                         location = :location,
                         employment_type = :employment_type,
                         salary_min = :salary_min,
                         salary_max = :salary_max,
                         summary = :summary,
                         description = :description,
                         requirements = :requirements,
                         status = :status,
                         is_featured = :is_featured,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute([
                    'title' => $title,
                    'category_id' => $categoryId,
                    'location' => $location,
                    'employment_type' => $employmentType,
                    'salary_min' => $salaryMin,
                    'salary_max' => $salaryMax,
                    'summary' => $summary,
                    'description' => $description,
                    'requirements' => $requirements,
                    'status' => $status,
                    'is_featured' => $isFeatured,
                    'id' => $jobId,
                ]);
                set_flash('success', 'Job post updated.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO jobs
                        (title, category_id, location, employment_type, salary_min, salary_max, summary, description, requirements, status, is_featured, created_by, created_at, updated_at)
                     VALUES
                        (:title, :category_id, :location, :employment_type, :salary_min, :salary_max, :summary, :description, :requirements, :status, :is_featured, :created_by, NOW(), NOW())'
                );
                $statement->execute([
                    'title' => $title,
                    'category_id' => $categoryId,
                    'location' => $location,
                    'employment_type' => $employmentType,
                    'salary_min' => $salaryMin,
                    'salary_max' => $salaryMax,
                    'summary' => $summary,
                    'description' => $description,
                    'requirements' => $requirements,
                    'status' => $status,
                    'is_featured' => $isFeatured,
                    'created_by' => current_user()['id'],
                ]);
                set_flash('success', 'Job post created.');
            }
        }

        redirect('admin/jobs.php');
    }

    if ($action === 'delete_job') {
        $statement = $pdo->prepare('DELETE FROM jobs WHERE id = :id');
        $statement->execute(['id' => (int) ($_POST['job_id'] ?? 0)]);
        set_flash('success', 'Job post deleted.');
        redirect('admin/jobs.php');
    }
}

$editJob = [
    'id' => '',
    'title' => '',
    'category_id' => '',
    'location' => '',
    'employment_type' => 'Full Time',
    'salary_min' => '',
    'salary_max' => '',
    'summary' => '',
    'description' => '',
    'requirements' => '',
    'status' => 'Draft',
    'is_featured' => 0,
];

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT * FROM jobs WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $editId]);
    $job = $statement->fetch();

    if ($job !== false) {
        $editJob = $job;
    }
}

$jobs = $pdo->query(
    'SELECT j.*, sc.name AS category_name
     FROM jobs j
     LEFT JOIN skill_categories sc ON sc.id = j.category_id
     ORDER BY j.created_at DESC'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="form-card">
        <div class="section-head">
            <div>
                <h2><?= $editId > 0 ? 'Edit Job Post' : 'Create Job Post' ?></h2>
                <p>Admin controls which opportunities appear publicly on the website.</p>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="save_job">
            <input type="hidden" name="job_id" value="<?= e((string) $editJob['id']) ?>">
            <div class="form-grid">
                <div class="field full">
                    <label for="title">Job Title</label>
                    <input id="title" name="title" type="text" value="<?= e($editJob['title']) ?>" required>
                </div>
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">General</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>" <?= selected_option((string) $editJob['category_id'], (string) $category['id']) ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="employment_type">Employment Type</label>
                    <select id="employment_type" name="employment_type">
                        <option value="Full Time" <?= selected_option((string) $editJob['employment_type'], 'Full Time') ?>>Full Time</option>
                        <option value="Part Time" <?= selected_option((string) $editJob['employment_type'], 'Part Time') ?>>Part Time</option>
                        <option value="Contract" <?= selected_option((string) $editJob['employment_type'], 'Contract') ?>>Contract</option>
                        <option value="Temporary" <?= selected_option((string) $editJob['employment_type'], 'Temporary') ?>>Temporary</option>
                    </select>
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" name="location" type="text" value="<?= e($editJob['location']) ?>" required>
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Draft" <?= selected_option((string) $editJob['status'], 'Draft') ?>>Draft</option>
                        <option value="Published" <?= selected_option((string) $editJob['status'], 'Published') ?>>Published</option>
                        <option value="Closed" <?= selected_option((string) $editJob['status'], 'Closed') ?>>Closed</option>
                    </select>
                </div>
                <div class="field">
                    <label for="salary_min">Salary Min</label>
                    <input id="salary_min" name="salary_min" type="number" min="0" step="0.01" value="<?= e((string) $editJob['salary_min']) ?>">
                </div>
                <div class="field">
                    <label for="salary_max">Salary Max</label>
                    <input id="salary_max" name="salary_max" type="number" min="0" step="0.01" value="<?= e((string) $editJob['salary_max']) ?>">
                </div>
                <div class="field full">
                    <label for="summary">Short Summary</label>
                    <textarea id="summary" name="summary" required><?= e($editJob['summary']) ?></textarea>
                </div>
                <div class="field full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?= e($editJob['description']) ?></textarea>
                </div>
                <div class="field full">
                    <label for="requirements">Requirements</label>
                    <textarea id="requirements" name="requirements"><?= e($editJob['requirements']) ?></textarea>
                </div>
                <div class="field full">
                    <label>
                        <input type="checkbox" name="is_featured" value="1" <?= checked_option((int) $editJob['is_featured'] === 1) ?>>
                        Feature this job on the homepage
                    </label>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit"><?= $editId > 0 ? 'Update Job' : 'Create Job' ?></button>
                <?php if ($editId > 0): ?>
                    <a class="ghost-button" href="<?= e(app_url('admin/jobs.php')) ?>">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Publishing Rule</h2>
        <ul class="list-clean">
            <li>Only jobs marked as <strong>Published</strong> appear on the public website.</li>
            <li>Applicants can view all public jobs but must login to apply.</li>
            <li>Job posts are brokered by admin, so employer contact details are not exposed publicly.</li>
        </ul>
    </article>
</section>

<section class="table-card">
    <div class="section-head">
        <div>
            <h2>All Job Posts</h2>
            <p>Full admin CRUD control for public recruitment opportunities.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td>
                            <strong><?= e($job['title']) ?></strong><br>
                            <?= e(truncate_text($job['summary'], 80)) ?>
                        </td>
                        <td><?= e($job['category_name'] ?: 'General') ?></td>
                        <td><span class="badge <?= e(status_badge_class($job['status'])) ?>"><?= e($job['status']) ?></span></td>
                        <td><?= e($job['employment_type']) ?></td>
                        <td><?= e(format_date($job['created_at'])) ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="ghost-button" href="<?= e(app_url('admin/jobs.php?edit=' . $job['id'])) ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_job">
                                    <input type="hidden" name="job_id" value="<?= e((string) $job['id']) ?>">
                                    <button class="ghost-button" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($jobs === []): ?>
                    <tr>
                        <td colspan="6">No jobs created yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
