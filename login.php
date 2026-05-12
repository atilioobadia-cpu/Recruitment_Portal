<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$pageTitle = 'Login';

if (is_post()) {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        set_flash('error', 'Email and password are required.');
    } elseif (attempt_login($pdo, $email, $password)) {
        $intendedUrl = consume_intended_url();

        if ($intendedUrl !== null) {
            header('Location: ' . $intendedUrl);
            exit;
        }

        set_flash('success', 'Welcome back.');
        redirect_to_dashboard();
    } else {
        set_flash('error', 'Invalid login details or inactive account.');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-wrapper">
    <div class="container auth-shell">
        <span class="eyebrow">Secure account access</span>
        <h1>Login to continue</h1>
        <p>Applicants, employers, and admin all use the same secure login with role-based redirection.</p>

        <form method="post">
            <div class="form-grid">
                <div class="field full">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>
                </div>
                <div class="field full">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
            </div>

            <div class="card-actions" style="margin-top: 1.2rem;">
                <button class="button button-dark" type="submit">Login</button>
                <a class="ghost-button" href="<?= e(app_url('register.php')) ?>">Create Account</a>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
