<?php
declare(strict_types=1);

function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

function asset_url(string $path = ''): string
{
    return app_url('assets/' . ltrim($path, '/'));
}

function versioned_asset_url(string $path = ''): string
{
    $relativePath = ltrim($path, '/');
    $absolutePath = dirname(__DIR__) . '/assets/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (is_file($absolutePath)) {
        return asset_url($relativePath) . '?v=' . filemtime($absolutePath);
    }

    return asset_url($relativePath);
}

function upload_url(string $path = ''): string
{
    return app_url('uploads/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function redirect_to_dashboard(): void
{
    $user = current_user();

    if ($user === null) {
        redirect('login.php');
    }

    redirect(dashboard_path_for_role($user['role']));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $flashes;
}

function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function selected_option(string $currentValue, string $expectedValue): string
{
    return $currentValue === $expectedValue ? 'selected' : '';
}

function checked_option(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function active_class(array $needles): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    foreach ($needles as $needle) {
        if (str_contains($script, $needle)) {
            return 'is-active';
        }
    }

    return '';
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Admin',
        'applicant' => 'Applicant',
        'employer' => 'Employer',
        default => 'Visitor',
    };
}

function format_date(?string $date, string $format = 'M d, Y'): string
{
    if (empty($date)) {
        return 'Not available';
    }

    return date($format, strtotime($date));
}

function truncate_text(?string $text, int $length = 140): string
{
    $text = trim((string) $text);

    if ($text === '') {
        return '';
    }

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length - 3) . '...';
}

function salary_range_display(mixed $minimum, mixed $maximum): string
{
    $minimum = $minimum !== null && $minimum !== '' ? number_format((float) $minimum) : null;
    $maximum = $maximum !== null && $maximum !== '' ? number_format((float) $maximum) : null;

    if ($minimum !== null && $maximum !== null) {
        return '$' . $minimum . ' - $' . $maximum;
    }

    if ($minimum !== null) {
        return 'From $' . $minimum;
    }

    if ($maximum !== null) {
        return 'Up to $' . $maximum;
    }

    return 'Negotiable';
}

function status_badge_class(string $status): string
{
    $normalized = strtolower($status);

    return match ($normalized) {
        'approved', 'connected', 'published', 'active' => 'badge-success',
        'rejected', 'closed', 'inactive' => 'badge-danger',
        'reviewing', 'pending', 'draft' => 'badge-warning',
        default => 'badge-neutral',
    };
}

function normalize_status(string $status, array $allowed, string $fallback): string
{
    return in_array($status, $allowed, true) ? $status : $fallback;
}

function fetch_skill_categories(PDO $pdo): array
{
    $statement = $pdo->query('SELECT id, name, description FROM skill_categories WHERE is_active = 1 ORDER BY name');

    return $statement->fetchAll();
}

function create_notification(PDO $pdo, int $userId, string $title, string $message): void
{
    $statement = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, message, created_at) VALUES (:user_id, :title, :message, NOW())'
    );

    $statement->execute([
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
    ]);
}

function fetch_notifications(PDO $pdo, int $userId, int $limit = 5): array
{
    $statement = $pdo->prepare(
        'SELECT id, title, message, is_read, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT ' . (int) $limit
    );
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function unread_notification_count(PDO $pdo, int $userId): int
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
    $statement->execute(['user_id' => $userId]);

    return (int) $statement->fetchColumn();
}

function generate_candidate_code(PDO $pdo): string
{
    $max = (int) $pdo->query(
        "SELECT MAX(CAST(SUBSTRING(candidate_code, 6) AS UNSIGNED)) FROM candidate_profiles"
    )->fetchColumn();

    return 'CAND-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
}

function ensure_candidate_profile(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare('SELECT * FROM candidate_profiles WHERE user_id = :user_id LIMIT 1');
    $statement->execute(['user_id' => $userId]);
    $profile = $statement->fetch();

    if ($profile !== false) {
        return $profile;
    }

    $insert = $pdo->prepare(
        'INSERT INTO candidate_profiles (user_id, candidate_code, status, created_at, updated_at)
         VALUES (:user_id, :candidate_code, "Draft", NOW(), NOW())'
    );
    $insert->execute([
        'user_id' => $userId,
        'candidate_code' => generate_candidate_code($pdo),
    ]);

    $statement->execute(['user_id' => $userId]);

    return (array) $statement->fetch();
}

function candidate_primary_skill(PDO $pdo, int $profileId): ?string
{
    $statement = $pdo->prepare(
        'SELECT skill_name
         FROM candidate_skills
         WHERE profile_id = :profile_id
         ORDER BY id ASC
         LIMIT 1'
    );
    $statement->execute(['profile_id' => $profileId]);

    return $statement->fetchColumn() ?: null;
}

function handle_upload(string $field, string $destinationDirectory, array $allowedExtensions): ?array
{
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported file format uploaded.');
    }

    $generatedName = uniqid('doc_', true) . '.' . $extension;
    $targetPath = rtrim($destinationDirectory, '/\\') . DIRECTORY_SEPARATOR . $generatedName;

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0777, true) && !is_dir($destinationDirectory)) {
        throw new RuntimeException('Upload directory could not be created.');
    }

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }

    return [
        'file_name' => $generatedName,
        'original_name' => (string) $file['name'],
    ];
}

function candidate_public_summary(array $candidate): array
{
    return [
        'candidate_code' => $candidate['candidate_code'] ?? 'Protected',
        'primary_skill' => $candidate['primary_skill'] ?? 'General Skills',
        'experience_years' => $candidate['experience_years'] ?? '0',
        'education_level' => $candidate['education_level'] ?? 'Not specified',
        'location' => $candidate['location'] ?? 'Undisclosed',
        'salary_range' => salary_range_display(
            $candidate['expected_salary_min'] ?? null,
            $candidate['expected_salary_max'] ?? null
        ),
        'availability' => $candidate['availability'] ?? 'Open',
        'bio' => $candidate['bio'] ?? '',
    ];
}

function icon_choices(): array
{
    return [
        'menu' => 'Menu',
        'dashboard' => 'Dashboard',
        'briefcase' => 'Briefcase',
        'users' => 'Users',
        'shield' => 'Shield',
        'document' => 'Document',
        'search' => 'Search',
        'mail' => 'Mail',
        'phone' => 'Phone',
        'map' => 'Map Pin',
        'building' => 'Building',
        'chart' => 'Chart',
        'star' => 'Star',
        'check' => 'Check',
        'lock' => 'Lock',
        'link' => 'Link',
        'globe' => 'Globe',
        'message' => 'Message',
        'spark' => 'Spark',
        'category' => 'Category',
        'clipboard' => 'Clipboard',
        'user-check' => 'User Check',
    ];
}

function ui_icon(string $name, string $class = 'ui-icon'): string
{
    $icons = [
        'menu' => '<path d="M3 6h18"></path><path d="M3 12h18"></path><path d="M3 18h18"></path>',
        'dashboard' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="4"></rect><rect x="14" y="10" width="7" height="11"></rect><rect x="3" y="13" width="7" height="8"></rect>',
        'briefcase' => '<path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path><path d="M4 7h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path><path d="M4 12h16"></path>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="3"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'shield' => '<path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z"></path><path d="m9.5 12 1.8 1.8 3.4-3.6"></path>',
        'document' => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path><path d="M14 2v5h5"></path><path d="M9 13h6"></path><path d="M9 17h6"></path><path d="M9 9h2"></path>',
        'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.35-4.35"></path>',
        'mail' => '<rect x="3" y="5" width="18" height="14"></rect><path d="m3 7 9 6 9-6"></path>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.62 2.62a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6.27 6.27l1.28-1.28a2 2 0 0 1 2.11-.45c.85.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92z"></path>',
        'map' => '<path d="M12 21s-6-4.35-6-10a6 6 0 1 1 12 0c0 5.65-6 10-6 10z"></path><circle cx="12" cy="11" r="2.5"></circle>',
        'building' => '<path d="M4 21V7l8-4 8 4v14"></path><path d="M9 21v-4h6v4"></path><path d="M8 11h.01"></path><path d="M12 11h.01"></path><path d="M16 11h.01"></path><path d="M8 15h.01"></path><path d="M12 15h.01"></path><path d="M16 15h.01"></path>',
        'chart' => '<path d="M3 3v18h18"></path><path d="m7 14 3-3 3 2 5-6"></path><path d="M18 7h3v3"></path>',
        'star' => '<path d="m12 3 2.8 5.68 6.27.91-4.53 4.42 1.07 6.24L12 17.27 6.39 20.25l1.07-6.24L2.93 9.59l6.27-.91z"></path>',
        'check' => '<path d="M20 6 9 17l-5-5"></path>',
        'lock' => '<rect x="4" y="11" width="16" height="10"></rect><path d="M8 11V7a4 4 0 1 1 8 0v4"></path>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11 4"></path><path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07L13 19"></path>',
        'globe' => '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a15 15 0 0 1 4 9 15 15 0 0 1-4 9 15 15 0 0 1-4-9 15 15 0 0 1 4-9z"></path>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><path d="M8 10h8"></path><path d="M8 7h5"></path>',
        'spark' => '<path d="M12 3 9.5 9.5 3 12l6.5 2.5L12 21l2.5-6.5L21 12l-6.5-2.5z"></path>',
        'category' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17"></rect><path d="M9 4h6v3H9z"></path><path d="M9 12h6"></path><path d="M9 16h6"></path>',
        'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="3"></circle><path d="m16 11 2 2 4-4"></path>',
    ];

    $markup = $icons[$name] ?? $icons['spark'];

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $markup . '</svg>';
}

function site_setting_definitions(): array
{
    return [
        'branding' => [
            'label' => 'Branding & Footer',
            'fields' => [
                'branding.primary_word' => ['label' => 'Brand First Word', 'type' => 'text', 'default' => 'Black'],
                'branding.accent_word' => ['label' => 'Brand Accent Word', 'type' => 'text', 'default' => 'Bridge'],
                'branding.tagline' => ['label' => 'Brand Tagline', 'type' => 'text', 'default' => 'Recruitment Brokerage'],
                'footer.about_text' => ['label' => 'Footer About Text', 'type' => 'textarea', 'default' => 'Professional recruitment brokerage with admin-controlled privacy between employers and applicants.'],
                'footer.privacy_title' => ['label' => 'Footer Privacy Title', 'type' => 'text', 'default' => 'Privacy First'],
                'footer.privacy_text' => ['label' => 'Footer Privacy Text', 'type' => 'textarea', 'default' => 'Contact details stay protected until admin review, approval, and controlled connection.'],
                'footer.bottom_text' => ['label' => 'Footer Bottom Text', 'type' => 'text', 'default' => 'Brokered recruitment platform'],
            ],
        ],
        'home' => [
            'label' => 'Home Page',
            'fields' => [
                'home.meta_description' => ['label' => 'Meta Description', 'type' => 'textarea', 'default' => 'Blue-toned recruitment brokerage platform with anonymous candidate visibility and admin-controlled hiring workflows.'],
                'home.eyebrow' => ['label' => 'Hero Eyebrow', 'type' => 'text', 'default' => 'Professional recruitment brokerage platform'],
                'home.title' => ['label' => 'Hero Title', 'type' => 'textarea', 'default' => 'Every Great Hire Starts With a Trusted Recruitment Broker'],
                'home.body' => ['label' => 'Hero Body', 'type' => 'textarea', 'default' => 'BlackBridge Recruitment helps applicants, employers, and admins work through a controlled recruitment process. Jobs stay public, candidate visibility stays anonymous, and every connection is reviewed by admin before any direct access is granted.'],
                'home.search_placeholder' => ['label' => 'Search Placeholder', 'type' => 'text', 'default' => 'Search jobs, skills or candidates'],
                'home.caption' => ['label' => 'Hero Caption', 'type' => 'textarea', 'default' => 'We make it easy to connect employers and talent through private admin-managed recruitment, protected profiles, approvals, and carefully controlled introductions.'],
                'home.stats_jobs_label' => ['label' => 'Jobs Stat Label', 'type' => 'text', 'default' => 'Published Jobs'],
                'home.stats_candidates_label' => ['label' => 'Candidates Stat Label', 'type' => 'text', 'default' => 'Approved Candidates'],
                'home.stats_requests_label' => ['label' => 'Requests Stat Label', 'type' => 'text', 'default' => 'Employer Requests'],
                'home.stats_brokerage_label' => ['label' => 'Brokerage Stat Label', 'type' => 'text', 'default' => 'Brokered Approach'],
                'home.jobs_title' => ['label' => 'Jobs Section Title', 'type' => 'text', 'default' => 'Latest Job Posts'],
                'home.jobs_body' => ['label' => 'Jobs Section Intro', 'type' => 'textarea', 'default' => 'Open roles published by admin for public viewing and applicant applications.'],
                'home.candidates_title' => ['label' => 'Candidates Section Title', 'type' => 'text', 'default' => 'Featured Anonymous Candidates'],
                'home.candidates_body' => ['label' => 'Candidates Section Intro', 'type' => 'textarea', 'default' => 'Only high-level public details are shown. Sensitive identity and documents remain hidden unless admin approves access.'],
                'home.categories_title' => ['label' => 'Categories Section Title', 'type' => 'text', 'default' => 'Skill Categories'],
                'home.categories_body' => ['label' => 'Categories Section Intro', 'type' => 'textarea', 'default' => 'Structured categories help admin organize candidates and public job opportunities.'],
                'home.how_title' => ['label' => 'How It Works Title', 'type' => 'text', 'default' => 'How It Works'],
                'home.how_body' => ['label' => 'How It Works Intro', 'type' => 'textarea', 'default' => 'A brokered flow designed to protect both sides until the right point in the process.'],
                'home.why_title' => ['label' => 'Why Choose Us Title', 'type' => 'text', 'default' => 'Why Choose Us'],
                'home.why_body' => ['label' => 'Why Choose Us Intro', 'type' => 'textarea', 'default' => 'Built for privacy, accountability, and smoother coordination.'],
                'home.contact_one_title' => ['label' => 'CTA Card One Title', 'type' => 'text', 'default' => 'Need a trusted recruitment broker?'],
                'home.contact_one_body' => ['label' => 'CTA Card One Body', 'type' => 'textarea', 'default' => 'Use the public contact page or create an employer account to submit a hiring request through admin.'],
                'home.contact_one_cta_label' => ['label' => 'CTA Card One Button Label', 'type' => 'text', 'default' => 'Contact Us'],
                'home.contact_one_cta_url' => ['label' => 'CTA Card One Button URL', 'type' => 'text', 'default' => 'contact.php'],
                'home.contact_two_title' => ['label' => 'CTA Card Two Title', 'type' => 'text', 'default' => 'Ready to be listed anonymously?'],
                'home.contact_two_body' => ['label' => 'CTA Card Two Body', 'type' => 'textarea', 'default' => 'Applicants can build complete profiles, upload CVs and certificates, and wait for admin approval before public listing.'],
                'home.contact_two_cta_label' => ['label' => 'CTA Card Two Button Label', 'type' => 'text', 'default' => 'Join as Applicant'],
                'home.contact_two_cta_url' => ['label' => 'CTA Card Two Button URL', 'type' => 'text', 'default' => 'register.php'],
            ],
        ],
        'about' => [
            'label' => 'About Page',
            'fields' => [
                'about.banner_intro' => ['label' => 'Page Banner Intro', 'type' => 'textarea', 'default' => 'Learn how the brokerage model protects both employers and applicants during the hiring process.'],
                'about.hero_title' => ['label' => 'About Hero Title', 'type' => 'textarea', 'default' => 'Recruitment brokerage built around privacy and professional coordination.'],
                'about.hero_body' => ['label' => 'About Hero Body', 'type' => 'textarea', 'default' => 'This platform is designed for situations where a trusted admin must manage the relationship between job seekers and hiring clients. Applicants and employers use the same system, but direct contact details stay private until admin reviews and approves the next step.'],
                'about.model_title' => ['label' => 'Model Section Title', 'type' => 'text', 'default' => 'Our Model'],
                'about.model_body' => ['label' => 'Model Section Body', 'type' => 'textarea', 'default' => 'Public visitors can explore jobs, anonymous talent, and business information without logging in. Once a user wants to apply for a role, request a worker, or access sensitive information, login becomes mandatory and admin remains the central broker.'],
                'about.privacy_title' => ['label' => 'Privacy Section Title', 'type' => 'text', 'default' => 'Privacy Promise'],
                'about.privacy_body' => ['label' => 'Privacy Section Body', 'type' => 'textarea', 'default' => 'Candidate public pages show anonymous codes, skill sets, location, education level, availability, and salary expectations. Employer-facing browsing is also limited to anonymous data until admin explicitly allows deeper sharing.'],
            ],
        ],
        'contact' => [
            'label' => 'Contact Page',
            'fields' => [
                'contact.banner_intro' => ['label' => 'Page Banner Intro', 'type' => 'textarea', 'default' => 'Reach the brokerage team for support, employer requests, or applicant onboarding questions.'],
                'contact.hero_title' => ['label' => 'Contact Intro Title', 'type' => 'text', 'default' => 'Contact The Brokerage Team'],
                'contact.hero_body' => ['label' => 'Contact Intro Body', 'type' => 'textarea', 'default' => 'Public visitors can ask questions here. Applicants and employers can also use their dashboards for structured requests and admin updates.'],
                'contact.form_title' => ['label' => 'Form Title', 'type' => 'text', 'default' => 'Send a Message'],
                'contact.info_email' => ['label' => 'Contact Email', 'type' => 'text', 'default' => 'info@blackbridge.test'],
                'contact.info_phone' => ['label' => 'Contact Phone', 'type' => 'text', 'default' => '+254 700 000 000'],
                'contact.info_office' => ['label' => 'Office Location', 'type' => 'text', 'default' => 'Nairobi, Kenya'],
            ],
        ],
        'jobs' => [
            'label' => 'Listing Page Intros',
            'fields' => [
                'jobs.banner_intro' => ['label' => 'Jobs Banner Intro', 'type' => 'textarea', 'default' => 'Publicly visible opportunities managed by admin. Login is required before an applicant can apply.'],
                'candidates.banner_intro' => ['label' => 'Candidates Banner Intro', 'type' => 'textarea', 'default' => 'Public and employer-visible candidate summaries remain anonymous until admin approves deeper access.'],
            ],
        ],
    ];
}

function site_content_area_definitions(): array
{
    return [
        'home.how' => [
            'label' => 'Home - How It Works',
            'defaults' => [
                ['item_key' => 'step_1', 'icon_name' => 'document', 'title' => 'Applicants create complete profiles', 'body' => 'Applicants add skills, qualifications, experience, and documents before submitting for review.', 'sort_order' => 1],
                ['item_key' => 'step_2', 'icon_name' => 'users', 'title' => 'Employers browse anonymous talent', 'body' => 'Employers review approved public candidate summaries without seeing direct identity or contact details.', 'sort_order' => 2],
                ['item_key' => 'step_3', 'icon_name' => 'search', 'title' => 'Admin reviews everything centrally', 'body' => 'Applications, candidate requests, and approval workflows are managed through the admin panel.', 'sort_order' => 3],
                ['item_key' => 'step_4', 'icon_name' => 'shield', 'title' => 'Connections happen only after approval', 'body' => 'Direct access and deeper disclosure happen only when admin approves the next stage.', 'sort_order' => 4],
            ],
        ],
        'home.why' => [
            'label' => 'Home - Why Choose Us',
            'defaults' => [
                ['item_key' => 'why_1', 'icon_name' => 'lock', 'title' => 'No direct contact leakage', 'body' => 'Applicants and employers stay protected until admin authorizes the relationship.', 'sort_order' => 1],
                ['item_key' => 'why_2', 'icon_name' => 'briefcase', 'title' => 'Structured hiring workflow', 'body' => 'Jobs, requests, applications, and approvals all stay under one controlled process.', 'sort_order' => 2],
                ['item_key' => 'why_3', 'icon_name' => 'dashboard', 'title' => 'Clear status visibility', 'body' => 'Each role sees clean dashboards with progress tracking, notes, and updates.', 'sort_order' => 3],
                ['item_key' => 'why_4', 'icon_name' => 'spark', 'title' => 'Professional presentation', 'body' => 'The platform supports a polished public image while staying operationally practical.', 'sort_order' => 4],
            ],
        ],
        'about.model_points' => [
            'label' => 'About - Model Points',
            'defaults' => [
                ['item_key' => 'model_1', 'icon_name' => 'briefcase', 'title' => 'Admin publishes jobs and manages status changes.', 'body' => '', 'sort_order' => 1],
                ['item_key' => 'model_2', 'icon_name' => 'document', 'title' => 'Applicants submit verified profiles and documents for approval.', 'body' => '', 'sort_order' => 2],
                ['item_key' => 'model_3', 'icon_name' => 'users', 'title' => 'Employers request candidates or submit hiring requirements.', 'body' => '', 'sort_order' => 3],
                ['item_key' => 'model_4', 'icon_name' => 'shield', 'title' => 'All communication and connection decisions stay under admin control.', 'body' => '', 'sort_order' => 4],
            ],
        ],
        'about.privacy_points' => [
            'label' => 'About - Privacy Points',
            'defaults' => [
                ['item_key' => 'privacy_1', 'icon_name' => 'lock', 'title' => 'Phone, email, address, CV files, and certificates stay private to admin.', 'body' => '', 'sort_order' => 1],
                ['item_key' => 'privacy_2', 'icon_name' => 'building', 'title' => 'Employer direct contact details stay hidden from applicants unless admin approves.', 'body' => '', 'sort_order' => 2],
                ['item_key' => 'privacy_3', 'icon_name' => 'clipboard', 'title' => 'Request tracking keeps every step visible inside the admin workflow.', 'body' => '', 'sort_order' => 3],
            ],
        ],
        'about.feature_cards' => [
            'label' => 'About - Feature Cards',
            'defaults' => [
                ['item_key' => 'feature_1', 'icon_name' => 'shield', 'title' => 'Admin Controlled', 'body' => 'The admin dashboard is the single point of approval for profiles, job applications, and candidate requests.', 'sort_order' => 1],
                ['item_key' => 'feature_2', 'icon_name' => 'users', 'title' => 'Role Based Access', 'body' => 'Applicant, employer, and admin dashboards are separated with focused tools and controlled data visibility.', 'sort_order' => 2],
                ['item_key' => 'feature_3', 'icon_name' => 'spark', 'title' => 'Professional Design', 'body' => 'Responsive public pages and dashboards stay consistent, modern, and practical for daily use.', 'sort_order' => 3],
            ],
        ],
        'contact.points' => [
            'label' => 'Contact - Support Points',
            'defaults' => [
                ['item_key' => 'contact_1', 'icon_name' => 'message', 'title' => 'General inquiries and support', 'body' => '', 'sort_order' => 1],
                ['item_key' => 'contact_2', 'icon_name' => 'briefcase', 'title' => 'Employer recruitment requests', 'body' => '', 'sort_order' => 2],
                ['item_key' => 'contact_3', 'icon_name' => 'user-check', 'title' => 'Applicant onboarding questions', 'body' => '', 'sort_order' => 3],
                ['item_key' => 'contact_4', 'icon_name' => 'shield', 'title' => 'Privacy and process clarification', 'body' => '', 'sort_order' => 4],
            ],
        ],
    ];
}

function default_site_setting_value(string $key, string $fallback = ''): string
{
    foreach (site_setting_definitions() as $group) {
        if (isset($group['fields'][$key])) {
            return (string) $group['fields'][$key]['default'];
        }
    }

    return $fallback;
}

function default_site_content_items(string $areaKey): array
{
    $definitions = site_content_area_definitions();

    return $definitions[$areaKey]['defaults'] ?? [];
}

function ensure_site_content_storage(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS site_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(150) NOT NULL UNIQUE,
            setting_value LONGTEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS site_content_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            area_key VARCHAR(120) NOT NULL,
            item_key VARCHAR(120) NOT NULL,
            icon_name VARCHAR(80) NULL,
            title VARCHAR(255) NULL,
            body TEXT NULL,
            link_label VARCHAR(150) NULL,
            link_url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_area_item (area_key, item_key)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $settingInsert = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_key = setting_key'
    );

    foreach (site_setting_definitions() as $group) {
        foreach ($group['fields'] as $key => $definition) {
            $settingInsert->execute([
                'setting_key' => $key,
                'setting_value' => (string) ($definition['default'] ?? ''),
            ]);
        }
    }

    $contentInsert = $pdo->prepare(
        'INSERT INTO site_content_items
            (area_key, item_key, icon_name, title, body, link_label, link_url, sort_order, is_active)
         VALUES
            (:area_key, :item_key, :icon_name, :title, :body, :link_label, :link_url, :sort_order, :is_active)
         ON DUPLICATE KEY UPDATE area_key = area_key'
    );

    foreach (site_content_area_definitions() as $areaKey => $definition) {
        foreach ($definition['defaults'] as $item) {
            $contentInsert->execute([
                'area_key' => $areaKey,
                'item_key' => $item['item_key'],
                'icon_name' => $item['icon_name'] ?? null,
                'title' => $item['title'] ?? '',
                'body' => $item['body'] ?? '',
                'link_label' => $item['link_label'] ?? null,
                'link_url' => $item['link_url'] ?? null,
                'sort_order' => $item['sort_order'] ?? 0,
                'is_active' => 1,
            ]);
        }
    }

    $initialized = true;
}

function site_settings_map(PDO $pdo, bool $forceRefresh = false): array
{
    static $cache = null;

    if ($cache !== null && !$forceRefresh) {
        return $cache;
    }

    $cache = [];

    try {
        $statement = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
        foreach ($statement->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable) {
        $cache = [];
    }

    foreach (site_setting_definitions() as $group) {
        foreach ($group['fields'] as $key => $definition) {
            if (!array_key_exists($key, $cache)) {
                $cache[$key] = (string) ($definition['default'] ?? '');
            }
        }
    }

    return $cache;
}

function site_setting(PDO $pdo, string $key, string $fallback = ''): string
{
    $settings = site_settings_map($pdo);

    if (array_key_exists($key, $settings)) {
        return (string) $settings[$key];
    }

    return default_site_setting_value($key, $fallback);
}

function site_items(PDO $pdo, string $areaKey, bool $activeOnly = true): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT *
             FROM site_content_items
             WHERE area_key = :area_key
             ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['area_key' => $areaKey]);
        $items = $statement->fetchAll();
    } catch (Throwable) {
        $items = [];
    }

    if ($items === []) {
        $items = default_site_content_items($areaKey);
    }

    if ($activeOnly) {
        $items = array_values(array_filter(
            $items,
            static fn(array $item): bool => !isset($item['is_active']) || (int) $item['is_active'] === 1
        ));
    }

    return $items;
}

function save_site_settings(PDO $pdo, array $submittedSettings): void
{
    $definitions = site_setting_definitions();
    $statement = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    foreach ($definitions as $group) {
        foreach ($group['fields'] as $key => $definition) {
            $value = trim((string) ($submittedSettings[$key] ?? $definition['default'] ?? ''));
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }

    site_settings_map($pdo, true);
}

function save_site_content_areas(PDO $pdo, array $submittedAreas): void
{
    $definitions = site_content_area_definitions();
    $statement = $pdo->prepare(
        'INSERT INTO site_content_items
            (area_key, item_key, icon_name, title, body, link_label, link_url, sort_order, is_active)
         VALUES
            (:area_key, :item_key, :icon_name, :title, :body, :link_label, :link_url, :sort_order, :is_active)
         ON DUPLICATE KEY UPDATE
            icon_name = VALUES(icon_name),
            title = VALUES(title),
            body = VALUES(body),
            link_label = VALUES(link_label),
            link_url = VALUES(link_url),
            sort_order = VALUES(sort_order),
            is_active = VALUES(is_active)'
    );

    foreach ($definitions as $areaKey => $definition) {
        $items = $submittedAreas[$areaKey] ?? [];

        foreach ($items as $item) {
            $itemKey = trim((string) ($item['item_key'] ?? ''));
            if ($itemKey === '') {
                continue;
            }

            $statement->execute([
                'area_key' => $areaKey,
                'item_key' => $itemKey,
                'icon_name' => trim((string) ($item['icon_name'] ?? 'spark')) ?: 'spark',
                'title' => trim((string) ($item['title'] ?? '')),
                'body' => trim((string) ($item['body'] ?? '')),
                'link_label' => trim((string) ($item['link_label'] ?? '')),
                'link_url' => trim((string) ($item['link_url'] ?? '')),
                'sort_order' => (int) ($item['sort_order'] ?? 0),
                'is_active' => isset($item['is_active']) ? 1 : 0,
            ]);
        }
    }
}
