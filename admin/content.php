<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Site Content';

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        save_site_settings($pdo, $_POST['settings'] ?? []);
        set_flash('success', 'Site settings updated successfully.');
        redirect('admin/content.php');
    }

    if ($action === 'save_items') {
        save_site_content_areas($pdo, $_POST['items'] ?? []);
        set_flash('success', 'Content blocks updated successfully.');
        redirect('admin/content.php');
    }
}

$settingGroups = site_setting_definitions();
$contentAreas = site_content_area_definitions();
$settings = site_settings_map($pdo);
$icons = icon_choices();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="panel">
        <div class="section-head">
            <div>
                <h2>Admin Managed Public Content</h2>
                <p>Update the public-facing text and section blocks for Home, About, Contact, branding, and footer areas from here.</p>
            </div>
        </div>
        <ul class="list-clean">
            <li>Jobs, candidates, applications, requests, and categories already update public pages dynamically.</li>
            <li>This screen covers the page copy, headlines, helper text, feature cards, and support blocks.</li>
            <li>Icons are reusable across the site for smoother, more consistent presentation.</li>
        </ul>
    </article>

    <article class="panel">
        <div class="section-head">
            <div>
                <h2>Icon Library</h2>
                <p>Use these icons for feature cards, workflows, contact points, and admin-managed blocks.</p>
            </div>
        </div>
        <div class="icon-library">
            <?php foreach ($icons as $iconKey => $iconLabel): ?>
                <div class="icon-library-item">
                    <span class="icon-badge"><?= ui_icon($iconKey) ?></span>
                    <strong><?= e($iconLabel) ?></strong>
                    <small><?= e($iconKey) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="form-card">
    <div class="section-head">
        <div>
            <h2>Text Settings</h2>
            <p>These values are used directly in shared headers, footers, banners, and public page content.</p>
        </div>
    </div>

    <form method="post">
        <input type="hidden" name="action" value="save_settings">

        <?php foreach ($settingGroups as $groupKey => $group): ?>
            <div class="settings-group">
                <h3><?= e($group['label']) ?></h3>
                <div class="form-grid">
                    <?php foreach ($group['fields'] as $fieldKey => $field): ?>
                        <?php $isTextarea = ($field['type'] ?? 'text') === 'textarea'; ?>
                        <div class="field <?= $isTextarea ? 'full' : '' ?>">
                            <label for="<?= e(str_replace('.', '_', $fieldKey)) ?>"><?= e($field['label']) ?></label>
                            <?php if ($isTextarea): ?>
                                <textarea id="<?= e(str_replace('.', '_', $fieldKey)) ?>" name="settings[<?= e($fieldKey) ?>]"><?= e((string) ($settings[$fieldKey] ?? $field['default'])) ?></textarea>
                            <?php else: ?>
                                <input id="<?= e(str_replace('.', '_', $fieldKey)) ?>" name="settings[<?= e($fieldKey) ?>]" type="text" value="<?= e((string) ($settings[$fieldKey] ?? $field['default'])) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card-actions" style="margin-top: 1rem;">
            <button class="button button-primary" type="submit">Save Text Settings</button>
        </div>
    </form>
</section>

<?php foreach ($contentAreas as $areaKey => $area): ?>
    <?php $items = site_items($pdo, $areaKey, false); ?>
    <section class="table-card">
        <div class="section-head">
            <div>
                <h2><?= e($area['label']) ?></h2>
                <p>Each row controls one public block item with icon, title, body text, ordering, and visibility.</p>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="save_items">
            <div class="content-item-grid">
                <?php foreach ($items as $index => $item): ?>
                    <article class="content-item-card">
                        <input type="hidden" name="items[<?= e($areaKey) ?>][<?= e((string) $index) ?>][item_key]" value="<?= e((string) ($item['item_key'] ?? '')) ?>">
                        <div class="field">
                            <label>Icon</label>
                            <select name="items[<?= e($areaKey) ?>][<?= e((string) $index) ?>][icon_name]">
                                <?php foreach ($icons as $iconKey => $iconLabel): ?>
                                    <option value="<?= e($iconKey) ?>" <?= selected_option((string) ($item['icon_name'] ?? ''), $iconKey) ?>><?= e($iconLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Title</label>
                            <input type="text" name="items[<?= e($areaKey) ?>][<?= e((string) $index) ?>][title]" value="<?= e((string) ($item['title'] ?? '')) ?>">
                        </div>
                        <div class="field">
                            <label>Body</label>
                            <textarea name="items[<?= e($areaKey) ?>][<?= e((string) $index) ?>][body]"><?= e((string) ($item['body'] ?? '')) ?></textarea>
                        </div>
                        <div class="field">
                            <label>Sort Order</label>
                            <input type="number" name="items[<?= e($areaKey) ?>][<?= e((string) $index) ?>][sort_order]" value="<?= e((string) ($item['sort_order'] ?? $index + 1)) ?>">
                        </div>
                        <div class="field">
                            <label>
                                <input type="checkbox" name="items[<?= e($areaKey) ?>][<?= e((string) $index) ?>][is_active]" value="1" <?= checked_option(!isset($item['is_active']) || (int) $item['is_active'] === 1) ?>>
                                Show this item
                            </label>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-primary" type="submit">Save <?= e($area['label']) ?></button>
            </div>
        </form>
    </section>
<?php endforeach; ?>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
