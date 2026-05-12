<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Manage Categories';

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_category') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            set_flash('error', 'Category name is required.');
        } else {
            if ($categoryId > 0) {
                $statement = $pdo->prepare(
                    'UPDATE skill_categories
                     SET name = :name,
                         description = :description,
                         is_active = :is_active
                     WHERE id = :id'
                );
                $statement->execute([
                    'name' => $name,
                    'description' => $description,
                    'is_active' => $isActive,
                    'id' => $categoryId,
                ]);
                set_flash('success', 'Category updated.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO skill_categories (name, description, is_active, created_at)
                     VALUES (:name, :description, :is_active, NOW())'
                );
                $statement->execute([
                    'name' => $name,
                    'description' => $description,
                    'is_active' => $isActive,
                ]);
                set_flash('success', 'Category created.');
            }
        }

        redirect('admin/categories.php');
    }

    if ($action === 'delete_category') {
        $statement = $pdo->prepare('DELETE FROM skill_categories WHERE id = :id');
        $statement->execute(['id' => (int) ($_POST['category_id'] ?? 0)]);
        set_flash('success', 'Category deleted.');
        redirect('admin/categories.php');
    }
}

$editCategory = [
    'id' => '',
    'name' => '',
    'description' => '',
    'is_active' => 1,
];

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT * FROM skill_categories WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $editId]);
    $category = $statement->fetch();

    if ($category !== false) {
        $editCategory = $category;
    }
}

$categories = $pdo->query('SELECT * FROM skill_categories ORDER BY name')->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="form-card">
        <h2><?= $editId > 0 ? 'Edit Category' : 'Create Category' ?></h2>
        <form method="post">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="category_id" value="<?= e((string) $editCategory['id']) ?>">
            <div class="form-grid">
                <div class="field full">
                    <label for="name">Category Name</label>
                    <input id="name" name="name" type="text" value="<?= e($editCategory['name']) ?>" required>
                </div>
                <div class="field full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= e($editCategory['description']) ?></textarea>
                </div>
                <div class="field full">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?= checked_option((int) $editCategory['is_active'] === 1) ?>>
                        Show this category to users
                    </label>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit"><?= $editId > 0 ? 'Update Category' : 'Create Category' ?></button>
                <?php if ($editId > 0): ?>
                    <a class="ghost-button" href="<?= e(app_url('admin/categories.php')) ?>">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Category Usage</h2>
        <ul class="list-clean">
            <li>Job posts can be grouped for cleaner public browsing.</li>
            <li>Applicant skills can optionally be mapped to structured categories.</li>
            <li>Inactive categories are hidden from normal users while keeping historical records intact.</li>
        </ul>
    </article>
</section>

<section class="table-card">
    <div class="section-head">
        <div>
            <h2>All Categories</h2>
            <p>Manage the structured skill and job taxonomy of the platform.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['name']) ?></td>
                        <td><?= e(truncate_text($category['description'], 90)) ?></td>
                        <td><span class="badge <?= e((int) $category['is_active'] === 1 ? 'badge-success' : 'badge-danger') ?>"><?= e((int) $category['is_active'] === 1 ? 'Active' : 'Hidden') ?></span></td>
                        <td>
                            <div class="table-actions">
                                <a class="ghost-button" href="<?= e(app_url('admin/categories.php?edit=' . $category['id'])) ?>">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?= e((string) $category['id']) ?>">
                                    <button class="ghost-button" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?>
                    <tr>
                        <td colspan="4">No categories created yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
