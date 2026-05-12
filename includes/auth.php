<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $cachedUser = null;
    static $cachedUserId = null;

    $userId = $_SESSION['user_id'] ?? null;

    if ($userId === null) {
        return null;
    }

    if ($cachedUser !== null && $cachedUserId === (int) $userId) {
        return $cachedUser;
    }

    global $pdo;

    $statement = $pdo->prepare(
        'SELECT u.*, ep.company_name
         FROM users u
         LEFT JOIN employer_profiles ep ON ep.user_id = u.id
         WHERE u.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    if ($user === false) {
        unset($_SESSION['user_id']);

        return null;
    }

    $cachedUser = $user;
    $cachedUserId = (int) $userId;

    return $cachedUser;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = (int) $user['id'];
    session_regenerate_id(true);
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['intended_url']);
    session_regenerate_id(true);
}

function dashboard_path_for_role(string $role): string
{
    return match ($role) {
        'admin' => 'admin/dashboard.php',
        'applicant' => 'applicant/dashboard.php',
        'employer' => 'employer/dashboard.php',
        default => 'index.php',
    };
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? null;
    set_flash('error', 'Please login to continue.');
    redirect('login.php');
}

function require_role(array|string $roles): void
{
    require_login();

    $roles = (array) $roles;
    $user = current_user();

    if ($user !== null && in_array($user['role'], $roles, true)) {
        return;
    }

    set_flash('error', 'You are not authorized to access that page.');
    redirect(dashboard_path_for_role($user['role'] ?? ''));
}

function consume_intended_url(): ?string
{
    $intendedUrl = $_SESSION['intended_url'] ?? null;
    unset($_SESSION['intended_url']);

    if ($intendedUrl === null || !str_starts_with($intendedUrl, BASE_URL)) {
        return null;
    }

    return $intendedUrl;
}

function attempt_login(PDO $pdo, string $email, string $password): bool
{
    $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => strtolower(trim($email))]);
    $user = $statement->fetch();

    if ($user === false) {
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    if (($user['account_status'] ?? 'Active') !== 'Active') {
        return false;
    }

    login_user($user);

    return true;
}
