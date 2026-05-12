<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$pageTitle = 'Contact';
$pageIntroText = site_setting(
    $pdo,
    'contact.banner_intro',
    'Reach the brokerage team for support, employer requests, or applicant onboarding questions.'
);
$contactPoints = site_items($pdo, 'contact.points');

if (is_post()) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        set_flash('error', 'Please complete all contact fields.');
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, subject, message, created_at)
             VALUES (:name, :email, :subject, :message, NOW())'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

        set_flash('success', 'Your message has been sent to admin.');
        redirect('contact.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container split-layout">
        <article class="contact-card">
            <h1><?= ui_icon('mail', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'contact.hero_title', 'Contact The Brokerage Team')) ?></h1>
            <p><?= e(site_setting($pdo, 'contact.hero_body', 'Public visitors can ask questions here. Applicants and employers can also use their dashboards for structured requests and admin updates.')) ?></p>
            <div class="feature-list-grid">
                <?php foreach ($contactPoints as $item): ?>
                    <article class="feature-list-card">
                        <span class="icon-badge"><?= ui_icon((string) ($item['icon_name'] ?? 'spark'), 'card-inline-icon') ?></span>
                        <div>
                            <h3><?= e((string) ($item['title'] ?? '')) ?></h3>
                            <?php if (trim((string) ($item['body'] ?? '')) !== ''): ?>
                                <p><?= e((string) ($item['body'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="contact-info-list">
                <div class="info-line">
                    <span class="icon-badge"><?= ui_icon('mail', 'card-inline-icon') ?></span>
                    <div>
                        <strong>Email</strong>
                        <p><?= e(site_setting($pdo, 'contact.info_email', 'info@blackbridge.test')) ?></p>
                    </div>
                </div>
                <div class="info-line">
                    <span class="icon-badge"><?= ui_icon('phone', 'card-inline-icon') ?></span>
                    <div>
                        <strong>Phone</strong>
                        <p><?= e(site_setting($pdo, 'contact.info_phone', '+254 700 000 000')) ?></p>
                    </div>
                </div>
                <div class="info-line">
                    <span class="icon-badge"><?= ui_icon('map', 'card-inline-icon') ?></span>
                    <div>
                        <strong>Office</strong>
                        <p><?= e(site_setting($pdo, 'contact.info_office', 'Nairobi, Kenya')) ?></p>
                    </div>
                </div>
            </div>
        </article>

        <article class="form-card">
            <h3><?= ui_icon('message', 'section-icon section-icon-inline') ?><?= e(site_setting($pdo, 'contact.form_title', 'Send a Message')) ?></h3>
            <form method="post">
                <div class="form-grid">
                    <div class="field">
                        <label for="name">Full Name</label>
                        <input id="name" name="name" type="text" value="<?= e(old('name')) ?>" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>
                    </div>
                    <div class="field full">
                        <label for="subject">Subject</label>
                        <input id="subject" name="subject" type="text" value="<?= e(old('subject')) ?>" required>
                    </div>
                    <div class="field full">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required><?= e(old('message')) ?></textarea>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="button button-dark" type="submit">
                        <?= ui_icon('mail', 'button-icon') ?>
                        Send Message
                    </button>
                </div>
            </form>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
