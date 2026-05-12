<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$pageTitle = 'Register';

if (is_post()) {
    $role = normalize_status((string) ($_POST['role'] ?? ''), ['applicant', 'employer'], 'applicant');
    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $companyName = trim((string) ($_POST['company_name'] ?? ''));
    $industry = trim((string) ($_POST['industry'] ?? ''));
    $companyDescription = trim((string) ($_POST['company_description'] ?? ''));

    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        set_flash('error', 'Please complete all required fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please provide a valid email address.');
    } elseif (strlen($password) < 8) {
        set_flash('error', 'Password must be at least 8 characters long.');
    } elseif ($password !== $confirmPassword) {
        set_flash('error', 'Passwords do not match.');
    } elseif ($role === 'employer' && $companyName === '') {
        set_flash('error', 'Company name is required for employer registration.');
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch() !== false) {
            set_flash('error', 'An account with that email already exists.');
        } else {
            $pdo->beginTransaction();

            try {
                $statement = $pdo->prepare(
                    'INSERT INTO users
                        (role, first_name, last_name, email, phone, password_hash, account_status, created_at)
                     VALUES
                        (:role, :first_name, :last_name, :email, :phone, :password_hash, "Active", NOW())'
                );
                $statement->execute([
                    'role' => $role,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $userId = (int) $pdo->lastInsertId();

                if ($role === 'applicant') {
                    ensure_candidate_profile($pdo, $userId);
                } else {
                    $employerInsert = $pdo->prepare(
                        'INSERT INTO employer_profiles
                            (user_id, company_name, industry, contact_person, description, created_at, updated_at)
                         VALUES
                            (:user_id, :company_name, :industry, :contact_person, :description, NOW(), NOW())'
                    );
                    $employerInsert->execute([
                        'user_id' => $userId,
                        'company_name' => $companyName,
                        'industry' => $industry,
                        'contact_person' => $firstName . ' ' . $lastName,
                        'description' => $companyDescription,
                    ]);
                }

                $pdo->commit();

                $newUser = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
                $newUser->execute(['id' => $userId]);
                $user = $newUser->fetch();

                if ($user !== false) {
                    login_user($user);
                }

                set_flash('success', 'Your account has been created successfully.');
                redirect_to_dashboard();
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                set_flash('error', 'Registration could not be completed. Please try again.');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-wrapper">
    <div class="container auth-shell">
        <span class="eyebrow">Create your account</span>
        <h1>Register as an applicant or employer</h1>
        <p>Public viewing is open, but actions such as applying for jobs or requesting a worker require an account.</p>

        <form method="post">
            <div class="form-grid">
                <div class="field full">
                    <label for="role">Register As</label>
                    <select id="role" name="role" data-role-select>
                        <option value="applicant" <?= selected_option(old('role', 'applicant'), 'applicant') ?>>Applicant / Job Seeker</option>
                        <option value="employer" <?= selected_option(old('role', 'applicant'), 'employer') ?>>Employer / Client</option>
                    </select>
                </div>
                <div class="field">
                    <label for="first_name">First Name</label>
                    <input id="first_name" name="first_name" type="text" value="<?= e(old('first_name')) ?>" required>
                </div>
                <div class="field">
                    <label for="last_name">Last Name</label>
                    <input id="last_name" name="last_name" type="text" value="<?= e(old('last_name')) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="text" value="<?= e(old('phone')) ?>" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required>
                </div>
            </div>

            <div class="form-card hidden" data-employer-fields style="margin-top: 1rem;">
                <h3>Employer Information</h3>
                <div class="form-grid">
                    <div class="field">
                        <label for="company_name">Company Name</label>
                        <input id="company_name" name="company_name" type="text" value="<?= e(old('company_name')) ?>">
                    </div>
                    <div class="field">
                        <label for="industry">Industry</label>
                        <input id="industry" name="industry" type="text" value="<?= e(old('industry')) ?>">
                    </div>
                    <div class="field full">
                        <label for="company_description">Company Description</label>
                        <textarea id="company_description" name="company_description"><?= e(old('company_description')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card-actions" style="margin-top: 1.2rem;">
                <button class="button button-dark" type="submit">Create Account</button>
                <a class="ghost-button" href="<?= e(app_url('login.php')) ?>">Already have an account?</a>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
