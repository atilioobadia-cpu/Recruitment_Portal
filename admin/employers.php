<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Manage Employers';
$accountStatuses = ['Active', 'Inactive'];

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_employer') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $industry = trim((string) ($_POST['industry'] ?? ''));
        $companySize = trim((string) ($_POST['company_size'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));
        $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $accountStatus = normalize_status((string) ($_POST['account_status'] ?? ''), $accountStatuses, 'Active');

        if (
            $firstName === ''
            || $lastName === ''
            || $email === ''
            || $phone === ''
            || $password === ''
            || $confirmPassword === ''
            || $companyName === ''
        ) {
            set_flash('error', 'Please complete all required employer account fields.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid employer email address.');
        } elseif (strlen($password) < 8) {
            set_flash('error', 'Employer password must be at least 8 characters long.');
        } elseif ($password !== $confirmPassword) {
            set_flash('error', 'Employer passwords do not match.');
        } else {
            $existingUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existingUser->execute(['email' => $email]);

            if ($existingUser->fetch() !== false) {
                set_flash('error', 'An employer account with that email already exists.');
            } else {
                $pdo->beginTransaction();

                try {
                    $userStatement = $pdo->prepare(
                        'INSERT INTO users
                            (role, first_name, last_name, email, phone, password_hash, account_status, created_at)
                         VALUES
                            ("employer", :first_name, :last_name, :email, :phone, :password_hash, :account_status, NOW())'
                    );
                    $userStatement->execute([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'account_status' => $accountStatus,
                    ]);

                    $userId = (int) $pdo->lastInsertId();
                    $profileStatement = $pdo->prepare(
                        'INSERT INTO employer_profiles
                            (
                                user_id, company_name, industry, company_size, location, address, website,
                                contact_person, description, created_at, updated_at
                            )
                         VALUES
                            (
                                :user_id, :company_name, :industry, :company_size, :location, :address, :website,
                                :contact_person, :description, NOW(), NOW()
                            )'
                    );
                    $profileStatement->execute([
                        'user_id' => $userId,
                        'company_name' => $companyName,
                        'industry' => $industry !== '' ? $industry : null,
                        'company_size' => $companySize !== '' ? $companySize : null,
                        'location' => $location !== '' ? $location : null,
                        'address' => $address !== '' ? $address : null,
                        'website' => $website !== '' ? $website : null,
                        'contact_person' => $contactPerson !== '' ? $contactPerson : $firstName . ' ' . $lastName,
                        'description' => $description !== '' ? $description : null,
                    ]);

                    create_notification(
                        $pdo,
                        $userId,
                        'Employer account created',
                        'Your employer account has been created by admin. You can log in and submit candidate requests or hiring requirements.'
                    );

                    $pdo->commit();

                    set_flash('success', 'Employer created successfully for ' . $companyName . '.');
                    redirect('admin/employers.php?view=' . $userId);
                } catch (Throwable $throwable) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    set_flash('error', 'The employer could not be created. Please try again.');
                }
            }
        }
    } elseif ($action === 'update_employer_status') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $accountStatus = normalize_status((string) ($_POST['account_status'] ?? ''), $accountStatuses, 'Active');

        $statement = $pdo->prepare('UPDATE users SET account_status = :account_status WHERE id = :id AND role = "employer"');
        $statement->execute([
            'account_status' => $accountStatus,
            'id' => $userId,
        ]);

        create_notification(
            $pdo,
            $userId,
            'Employer account update',
            'Your employer account status is now ' . $accountStatus . '.'
        );

        set_flash('success', 'Employer account status updated.');
        redirect('admin/employers.php?view=' . $userId);
    }
}

$employers = $pdo->query(
    'SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.account_status,
            ep.company_name, ep.industry, ep.location
     FROM users u
     LEFT JOIN employer_profiles ep ON ep.user_id = u.id
     WHERE u.role = "employer"
     ORDER BY u.created_at DESC'
)->fetchAll();

$selectedEmployer = null;
$selectedEmployerRequests = [];

$viewId = (int) ($_GET['view'] ?? 0);
if ($viewId > 0) {
    $statement = $pdo->prepare(
        'SELECT u.*, ep.*
         FROM users u
         LEFT JOIN employer_profiles ep ON ep.user_id = u.id
         WHERE u.id = :id AND u.role = "employer"
         LIMIT 1'
    );
    $statement->execute(['id' => $viewId]);
    $selectedEmployer = $statement->fetch();

    if ($selectedEmployer !== false) {
        $requestsStatement = $pdo->prepare(
            'SELECT er.*, cp.candidate_code
             FROM employer_requests er
             LEFT JOIN candidate_profiles cp ON cp.id = er.candidate_profile_id
             WHERE er.employer_user_id = :user_id
             ORDER BY er.created_at DESC
             LIMIT 10'
        );
        $requestsStatement->execute(['user_id' => $viewId]);
        $selectedEmployerRequests = $requestsStatement->fetchAll();
    }
}

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="form-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('building', 'section-icon section-icon-inline') ?>Add Employer</h2>
                <p>Create an employer account and company profile directly from admin.</p>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="create_employer">
            <div class="form-grid">
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
                <div class="field">
                    <label for="company_name">Company Name</label>
                    <input id="company_name" name="company_name" type="text" value="<?= e(old('company_name')) ?>" required>
                </div>
                <div class="field">
                    <label for="industry">Industry</label>
                    <input id="industry" name="industry" type="text" value="<?= e(old('industry')) ?>">
                </div>
                <div class="field">
                    <label for="company_size">Company Size</label>
                    <input id="company_size" name="company_size" type="text" value="<?= e(old('company_size')) ?>">
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" name="location" type="text" value="<?= e(old('location')) ?>">
                </div>
                <div class="field">
                    <label for="website">Website</label>
                    <input id="website" name="website" type="text" value="<?= e(old('website')) ?>">
                </div>
                <div class="field">
                    <label for="contact_person">Contact Person</label>
                    <input id="contact_person" name="contact_person" type="text" value="<?= e(old('contact_person')) ?>">
                </div>
                <div class="field">
                    <label for="create_account_status">Account Status</label>
                    <select id="create_account_status" name="account_status">
                        <?php foreach ($accountStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected_option(old('account_status', 'Active'), $status) ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?= e(old('address')) ?></textarea>
                </div>
                <div class="field full">
                    <label for="description">Company Description</label>
                    <textarea id="description" name="description"><?= e(old('description')) ?></textarea>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit"><?= ui_icon('building', 'button-icon') ?>Create Employer</button>
            </div>
        </form>
    </article>

    <article class="table-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('users', 'section-icon section-icon-inline') ?>Employer Directory</h2>
                <p>Admin sees full employer contact and company details. Applicants do not.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employers as $employer): ?>
                        <tr>
                            <td>
                                <strong><?= e($employer['company_name'] ?: 'No company name') ?></strong><br>
                                <?= e($employer['industry'] ?: 'No industry') ?>
                            </td>
                            <td><?= e($employer['first_name'] . ' ' . $employer['last_name']) ?></td>
                            <td><span class="badge <?= e(status_badge_class($employer['account_status'])) ?>"><?= e($employer['account_status']) ?></span></td>
                            <td><a class="ghost-button" href="<?= e(app_url('admin/employers.php?view=' . $employer['id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($employers === []): ?>
                        <tr>
                            <td colspan="4">No employers registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <?php if ($selectedEmployer): ?>
            <div class="section-head">
                <div>
                    <h2><?= ui_icon('shield', 'section-icon section-icon-inline') ?>Employer Review</h2>
                    <p>Private employer identity and requests are visible only to admin.</p>
                </div>
                <span class="badge <?= e(status_badge_class($selectedEmployer['account_status'])) ?>"><?= e($selectedEmployer['account_status']) ?></span>
            </div>
            <ul class="list-clean">
                <li><strong>Company:</strong> <?= e($selectedEmployer['company_name'] ?: 'Not set') ?></li>
                <li><strong>Contact Person:</strong> <?= e($selectedEmployer['contact_person'] ?: $selectedEmployer['first_name'] . ' ' . $selectedEmployer['last_name']) ?></li>
                <li><strong>Email:</strong> <?= e($selectedEmployer['email']) ?></li>
                <li><strong>Phone:</strong> <?= e($selectedEmployer['phone']) ?></li>
                <li><strong>Industry:</strong> <?= e($selectedEmployer['industry'] ?: 'Not set') ?></li>
                <li><strong>Location:</strong> <?= e($selectedEmployer['location'] ?: 'Not set') ?></li>
                <li><strong>Website:</strong> <?= e($selectedEmployer['website'] ?: 'Not set') ?></li>
                <li><strong>Address:</strong> <?= e($selectedEmployer['address'] ?: 'Not set') ?></li>
                <li><strong>Description:</strong><br><?= nl2br(e($selectedEmployer['description'] ?: 'Not provided')) ?></li>
            </ul>

            <form method="post" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="update_employer_status">
                <input type="hidden" name="user_id" value="<?= e((string) $selectedEmployer['id']) ?>">
                <div class="form-grid">
                    <div class="field">
                        <label for="review_account_status">Account Status</label>
                        <select id="review_account_status" name="account_status">
                            <?php foreach ($accountStatuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= selected_option((string) $selectedEmployer['account_status'], $status) ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-actions" style="margin-top: 1rem;">
                    <button class="button button-dark" type="submit">Save Status</button>
                </div>
            </form>
        <?php else: ?>
            <h2><?= ui_icon('document', 'section-icon section-icon-inline') ?>Select an employer</h2>
            <p>Choose a client record from the directory to view company information and recent requests.</p>
        <?php endif; ?>
    </article>
</section>

<?php if ($selectedEmployer): ?>
    <section class="table-card">
        <div class="section-head">
            <div>
                <h2>Recent Employer Requests</h2>
                <p>All brokerage and candidate matching actions stay visible to admin here.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Candidate</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedEmployerRequests as $request): ?>
                        <tr>
                            <td><?= e($request['requirement_title']) ?></td>
                            <td><?= e($request['candidate_code'] ?: 'General request') ?></td>
                            <td><span class="badge <?= e(status_badge_class($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                            <td><?= e(format_date($request['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($selectedEmployerRequests === []): ?>
                        <tr>
                            <td colspan="4">This employer has not submitted requests yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
