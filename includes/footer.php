<?php
declare(strict_types=1);

$brandPrimary = site_setting($pdo, 'branding.primary_word', 'Black');
$brandAccent = site_setting($pdo, 'branding.accent_word', 'Bridge');
$brandTagline = site_setting($pdo, 'branding.tagline', 'Recruitment Brokerage');
$siteDisplayName = trim($brandPrimary . $brandAccent) !== '' ? trim($brandPrimary . $brandAccent) : APP_NAME;
?>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3 class="footer-brand-mark">
                    <span class="footer-brand-name">
                        <span class="brand-word brand-word-light"><?= e($brandPrimary) ?></span><span class="brand-word brand-word-accent"><?= e($brandAccent) ?></span>
                    </span>
                </h3>
                <p><?= e($brandTagline) ?></p>
                <p><?= e(site_setting($pdo, 'footer.about_text', 'Professional recruitment brokerage with admin-controlled privacy between employers and applicants.')) ?></p>
            </div>
            <div>
                <h4><?= ui_icon('globe', 'section-icon section-icon-inline') ?>Pages</h4>
                <a href="<?= e(app_url('jobs.php')) ?>">Jobs</a>
                <a href="<?= e(app_url('candidates.php')) ?>">Candidates</a>
                <a href="<?= e(app_url('about.php')) ?>">About</a>
                <a href="<?= e(app_url('contact.php')) ?>">Contact</a>
            </div>
            <div>
                <h4><?= ui_icon('users', 'section-icon section-icon-inline') ?>Access</h4>
                <a href="<?= e(app_url('login.php')) ?>">Login</a>
                <a href="<?= e(app_url('register.php')) ?>">Register</a>
                <a href="<?= e(app_url('applicant/dashboard.php')) ?>">Applicant Area</a>
                <a href="<?= e(app_url('employer/dashboard.php')) ?>">Employer Area</a>
            </div>
            <div>
                <h4><?= ui_icon('shield', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'footer.privacy_title', 'Privacy First')) ?></h4>
                <p><?= e(site_setting($pdo, 'footer.privacy_text', 'Contact details stay protected until admin review, approval, and controlled connection.')) ?></p>
            </div>
        </div>
        <div class="container footer-bottom">
            <span>&copy; <?= e(date('Y')) ?> <?= e($siteDisplayName) ?></span>
            <span><?= e(site_setting($pdo, 'footer.bottom_text', 'Brokered recruitment platform')) ?></span>
        </div>
    </footer>

    <script src="<?= e(versioned_asset_url('js/app.js')) ?>"></script>
</body>
</html>
