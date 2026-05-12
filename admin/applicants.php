<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('admin');

$pageTitle = 'Manage Applicants';
$statusOptions = ['Draft', 'Pending', 'Approved', 'Rejected'];
$skillCategories = fetch_skill_categories($pdo);

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_applicant') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $headline = trim((string) ($_POST['headline'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $educationLevel = trim((string) ($_POST['education_level'] ?? ''));
        $experienceYears = max(0, (int) ($_POST['experience_years'] ?? 0));
        $expectedSalaryMinRaw = trim((string) ($_POST['expected_salary_min'] ?? ''));
        $expectedSalaryMaxRaw = trim((string) ($_POST['expected_salary_max'] ?? ''));
        $availability = trim((string) ($_POST['availability'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $primarySkill = trim((string) ($_POST['primary_skill'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $profileStatus = normalize_status((string) ($_POST['status'] ?? ''), $statusOptions, 'Draft');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        $expectedSalaryMin = $expectedSalaryMinRaw !== '' ? (float) $expectedSalaryMinRaw : null;
        $expectedSalaryMax = $expectedSalaryMaxRaw !== '' ? (float) $expectedSalaryMaxRaw : null;

        if (
            $firstName === ''
            || $lastName === ''
            || $email === ''
            || $phone === ''
            || $password === ''
            || $confirmPassword === ''
        ) {
            set_flash('error', 'Please complete all required applicant account fields.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid applicant email address.');
        } elseif (strlen($password) < 8) {
            set_flash('error', 'Applicant password must be at least 8 characters long.');
        } elseif ($password !== $confirmPassword) {
            set_flash('error', 'Applicant passwords do not match.');
        } elseif ($expectedSalaryMinRaw !== '' && !is_numeric($expectedSalaryMinRaw)) {
            set_flash('error', 'Minimum salary must be numeric if provided.');
        } elseif ($expectedSalaryMaxRaw !== '' && !is_numeric($expectedSalaryMaxRaw)) {
            set_flash('error', 'Maximum salary must be numeric if provided.');
        } else {
            $existingUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existingUser->execute(['email' => $email]);

            if ($existingUser->fetch() !== false) {
                set_flash('error', 'An applicant account with that email already exists.');
            } else {
                $pdo->beginTransaction();

                try {
                    $userStatement = $pdo->prepare(
                        'INSERT INTO users
                            (role, first_name, last_name, email, phone, password_hash, account_status, created_at)
                         VALUES
                            ("applicant", :first_name, :last_name, :email, :phone, :password_hash, "Active", NOW())'
                    );
                    $userStatement->execute([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ]);

                    $userId = (int) $pdo->lastInsertId();
                    $candidateCode = generate_candidate_code($pdo);
                    $submittedAt = $profileStatus !== 'Draft' ? date('Y-m-d H:i:s') : null;
                    $approvedAt = $profileStatus === 'Approved' ? date('Y-m-d H:i:s') : null;

                    $profileStatement = $pdo->prepare(
                        'INSERT INTO candidate_profiles
                            (
                                user_id, candidate_code, headline, bio, experience_years, education_level, location,
                                expected_salary_min, expected_salary_max, availability, address, status, is_featured,
                                submitted_at, approved_at, created_at, updated_at
                            )
                         VALUES
                            (
                                :user_id, :candidate_code, :headline, :bio, :experience_years, :education_level, :location,
                                :expected_salary_min, :expected_salary_max, :availability, :address, :status, :is_featured,
                                :submitted_at, :approved_at, NOW(), NOW()
                            )'
                    );
                    $profileStatement->execute([
                        'user_id' => $userId,
                        'candidate_code' => $candidateCode,
                        'headline' => $headline !== '' ? $headline : null,
                        'bio' => $bio !== '' ? $bio : null,
                        'experience_years' => $experienceYears,
                        'education_level' => $educationLevel !== '' ? $educationLevel : null,
                        'location' => $location !== '' ? $location : null,
                        'expected_salary_min' => $expectedSalaryMin,
                        'expected_salary_max' => $expectedSalaryMax,
                        'availability' => $availability !== '' ? $availability : null,
                        'address' => $address !== '' ? $address : null,
                        'status' => $profileStatus,
                        'is_featured' => $isFeatured,
                        'submitted_at' => $submittedAt,
                        'approved_at' => $approvedAt,
                    ]);

                    $profileId = (int) $pdo->lastInsertId();

                    if ($primarySkill !== '') {
                        $skillStatement = $pdo->prepare(
                            'INSERT INTO candidate_skills (profile_id, category_id, skill_name)
                             VALUES (:profile_id, :category_id, :skill_name)'
                        );
                        $skillStatement->execute([
                            'profile_id' => $profileId,
                            'category_id' => $categoryId > 0 ? $categoryId : null,
                            'skill_name' => $primarySkill,
                        ]);
                    }

                    create_notification(
                        $pdo,
                        $userId,
                        'Applicant account created',
                        'Your applicant account has been created by admin. You can log in and continue updating your profile.'
                    );

                    $pdo->commit();

                    set_flash('success', 'Applicant created successfully with code ' . $candidateCode . '.');
                    redirect('admin/applicants.php?view=' . $profileId);
                } catch (Throwable $throwable) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    set_flash('error', 'The applicant could not be created. Please try again.');
                }
            }
        }
    } elseif ($action === 'update_profile_status') {
        $profileId = (int) ($_POST['profile_id'] ?? 0);
        $status = normalize_status((string) ($_POST['status'] ?? ''), $statusOptions, 'Pending');
        $approvalNotes = trim((string) ($_POST['approval_notes'] ?? ''));

        $profileStatement = $pdo->prepare('SELECT user_id, candidate_code FROM candidate_profiles WHERE id = :id LIMIT 1');
        $profileStatement->execute(['id' => $profileId]);
        $profile = $profileStatement->fetch();

        if ($profile !== false) {
            $statement = $pdo->prepare(
                'UPDATE candidate_profiles
                 SET status = :status,
                     approval_notes = :approval_notes,
                     approved_at = :approved_at,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'status' => $status,
                'approval_notes' => $approvalNotes,
                'approved_at' => $status === 'Approved' ? date('Y-m-d H:i:s') : null,
                'id' => $profileId,
            ]);

            create_notification(
                $pdo,
                (int) $profile['user_id'],
                'Applicant profile update',
                'Your profile ' . $profile['candidate_code'] . ' is now marked as ' . $status . '. ' . $approvalNotes
            );

            set_flash('success', 'Applicant profile updated.');
        }

        redirect('admin/applicants.php?view=' . $profileId);
    }
}

$applicants = $pdo->query(
    'SELECT cp.id, cp.candidate_code, cp.status, cp.location, cp.submitted_at, cp.approved_at,
            u.first_name, u.last_name, u.email, u.phone,
            (
                SELECT cs.skill_name
                FROM candidate_skills cs
                WHERE cs.profile_id = cp.id
                ORDER BY cs.id ASC
                LIMIT 1
            ) AS primary_skill
     FROM candidate_profiles cp
     INNER JOIN users u ON u.id = cp.user_id
     ORDER BY cp.created_at DESC'
)->fetchAll();

$selectedProfile = null;
$selectedSkills = [];
$selectedQualifications = [];
$selectedExperiences = [];
$selectedDocuments = [];

$viewId = (int) ($_GET['view'] ?? 0);
if ($viewId > 0) {
    $statement = $pdo->prepare(
        'SELECT cp.*, u.first_name, u.last_name, u.email, u.phone
         FROM candidate_profiles cp
         INNER JOIN users u ON u.id = cp.user_id
         WHERE cp.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $viewId]);
    $selectedProfile = $statement->fetch();

    if ($selectedProfile !== false) {
        $skillsStatement = $pdo->prepare('SELECT * FROM candidate_skills WHERE profile_id = :profile_id ORDER BY id DESC');
        $skillsStatement->execute(['profile_id' => $viewId]);
        $selectedSkills = $skillsStatement->fetchAll();

        $qualificationsStatement = $pdo->prepare(
            'SELECT * FROM candidate_qualifications WHERE profile_id = :profile_id ORDER BY id DESC'
        );
        $qualificationsStatement->execute(['profile_id' => $viewId]);
        $selectedQualifications = $qualificationsStatement->fetchAll();

        $experiencesStatement = $pdo->prepare(
            'SELECT * FROM candidate_experiences WHERE profile_id = :profile_id ORDER BY id DESC'
        );
        $experiencesStatement->execute(['profile_id' => $viewId]);
        $selectedExperiences = $experiencesStatement->fetchAll();

        $documentsStatement = $pdo->prepare(
            'SELECT * FROM candidate_documents WHERE profile_id = :profile_id ORDER BY id DESC'
        );
        $documentsStatement->execute(['profile_id' => $viewId]);
        $selectedDocuments = $documentsStatement->fetchAll();
    }
}

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="form-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('user-check', 'section-icon section-icon-inline') ?>Add Applicant</h2>
                <p>Create an applicant account and starter candidate profile directly from admin.</p>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="create_applicant">
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
                <div class="field full">
                    <label for="headline">Professional Headline</label>
                    <input id="headline" name="headline" type="text" value="<?= e(old('headline')) ?>">
                </div>
                <div class="field full">
                    <label for="bio">Short Bio</label>
                    <textarea id="bio" name="bio"><?= e(old('bio')) ?></textarea>
                </div>
                <div class="field">
                    <label for="primary_skill">Primary Skill</label>
                    <input id="primary_skill" name="primary_skill" type="text" value="<?= e(old('primary_skill')) ?>">
                </div>
                <div class="field">
                    <label for="category_id">Skill Category</label>
                    <select id="category_id" name="category_id">
                        <option value="0">Select category</option>
                        <?php foreach ($skillCategories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>" <?= selected_option(old('category_id'), (string) $category['id']) ?>><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" name="location" type="text" value="<?= e(old('location')) ?>">
                </div>
                <div class="field">
                    <label for="availability">Availability</label>
                    <input id="availability" name="availability" type="text" value="<?= e(old('availability')) ?>">
                </div>
                <div class="field">
                    <label for="education_level">Education Level</label>
                    <input id="education_level" name="education_level" type="text" value="<?= e(old('education_level')) ?>">
                </div>
                <div class="field">
                    <label for="experience_years">Experience Years</label>
                    <input id="experience_years" name="experience_years" type="number" min="0" value="<?= e(old('experience_years', '0')) ?>">
                </div>
                <div class="field">
                    <label for="expected_salary_min">Expected Salary Min</label>
                    <input id="expected_salary_min" name="expected_salary_min" type="number" step="0.01" value="<?= e(old('expected_salary_min')) ?>">
                </div>
                <div class="field">
                    <label for="expected_salary_max">Expected Salary Max</label>
                    <input id="expected_salary_max" name="expected_salary_max" type="number" step="0.01" value="<?= e(old('expected_salary_max')) ?>">
                </div>
                <div class="field">
                    <label for="create_status">Profile Status</label>
                    <select id="create_status" name="status">
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected_option(old('status', 'Draft'), $status) ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>
                        <input name="is_featured" type="checkbox" value="1" <?= checked_option(isset($_POST['is_featured'])) ?>>
                        Feature on public candidate listings
                    </label>
                </div>
                <div class="field full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?= e(old('address')) ?></textarea>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit"><?= ui_icon('user-check', 'button-icon') ?>Create Applicant</button>
            </div>
        </form>
    </article>

    <article class="table-card">
        <div class="section-head">
            <div>
                <h2><?= ui_icon('users', 'section-icon section-icon-inline') ?>Applicant Directory</h2>
                <p>Admin has full access to private identity, documents, and approval controls.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Skill</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applicants as $applicant): ?>
                        <tr>
                            <td><?= e($applicant['candidate_code']) ?></td>
                            <td><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td>
                            <td><?= e($applicant['primary_skill'] ?: 'Not added') ?></td>
                            <td><span class="badge <?= e(status_badge_class($applicant['status'])) ?>"><?= e($applicant['status']) ?></span></td>
                            <td><a class="ghost-button" href="<?= e(app_url('admin/applicants.php?view=' . $applicant['id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($applicants === []): ?>
                        <tr>
                            <td colspan="5">No applicants registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <?php if ($selectedProfile): ?>
            <div class="section-head">
                <div>
                    <h2><?= ui_icon('shield', 'section-icon section-icon-inline') ?>Applicant Review</h2>
                    <p>Private details are visible here only for admin use.</p>
                </div>
                <span class="badge <?= e(status_badge_class($selectedProfile['status'])) ?>"><?= e($selectedProfile['status']) ?></span>
            </div>

            <ul class="list-clean">
                <li><strong>Code:</strong> <?= e($selectedProfile['candidate_code']) ?></li>
                <li><strong>Name:</strong> <?= e($selectedProfile['first_name'] . ' ' . $selectedProfile['last_name']) ?></li>
                <li><strong>Email:</strong> <?= e($selectedProfile['email']) ?></li>
                <li><strong>Phone:</strong> <?= e($selectedProfile['phone']) ?></li>
                <li><strong>Address:</strong> <?= e($selectedProfile['address'] ?: 'Not provided') ?></li>
                <li><strong>Headline:</strong> <?= e($selectedProfile['headline'] ?: 'Not provided') ?></li>
                <li><strong>Bio:</strong><br><?= nl2br(e($selectedProfile['bio'] ?: 'Not provided')) ?></li>
            </ul>

            <form method="post" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="update_profile_status">
                <input type="hidden" name="profile_id" value="<?= e((string) $selectedProfile['id']) ?>">
                <div class="form-grid">
                    <div class="field">
                        <label for="review_status">Approval Status</label>
                        <select id="review_status" name="status">
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= e($status) ?>" <?= selected_option((string) $selectedProfile['status'], $status) ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="approval_notes">Admin Notes</label>
                        <textarea id="approval_notes" name="approval_notes"><?= e($selectedProfile['approval_notes']) ?></textarea>
                    </div>
                </div>
                <div class="card-actions" style="margin-top: 1rem;">
                    <button class="button button-dark" type="submit">Save Decision</button>
                </div>
            </form>
        <?php else: ?>
            <h2><?= ui_icon('document', 'section-icon section-icon-inline') ?>Select an applicant</h2>
            <p>Choose a profile from the directory to review full details, uploaded documents, and approval status.</p>
        <?php endif; ?>
    </article>
</section>

<?php if ($selectedProfile): ?>
    <section class="two-column">
        <article class="table-card">
            <h2>Skills & Qualifications</h2>
            <div class="inline-meta">
                <?php foreach ($selectedSkills as $skill): ?>
                    <span><?= e($skill['skill_name']) ?></span>
                <?php endforeach; ?>
                <?php if ($selectedSkills === []): ?>
                    <span>No skills added</span>
                <?php endif; ?>
            </div>
            <div class="table-wrap" style="margin-top: 1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Qualification</th>
                            <th>Institution</th>
                            <th>Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectedQualifications as $qualification): ?>
                            <tr>
                                <td><?= e($qualification['qualification_title']) ?></td>
                                <td><?= e($qualification['institution_name']) ?></td>
                                <td><?= e($qualification['year_completed']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($selectedQualifications === []): ?>
                            <tr>
                                <td colspan="3">No qualifications added.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="table-card">
            <h2>Experience & Documents</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Experience</th>
                            <th>Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectedExperiences as $experience): ?>
                            <tr>
                                <td>
                                    <strong><?= e($experience['job_title']) ?></strong><br>
                                    <?= e($experience['company_name']) ?>
                                </td>
                                <td><?= e(format_date($experience['start_date'])) ?> - <?= e($experience['end_date'] ? format_date($experience['end_date']) : 'Present') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($selectedExperiences === []): ?>
                            <tr>
                                <td colspan="2">No work experience added.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="inline-meta" style="margin-top: 1rem;">
                <?php foreach ($selectedDocuments as $document): ?>
                    <a class="ghost-button" href="<?= e(app_url('download-document.php?id=' . $document['id'])) ?>" target="_blank" rel="noopener">
                        <?= e($document['document_type']) ?>: <?= e($document['original_name']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if ($selectedDocuments === []): ?>
                    <span>No documents uploaded</span>
                <?php endif; ?>
            </div>
        </article>
    </section>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
