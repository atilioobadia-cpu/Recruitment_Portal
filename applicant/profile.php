<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_role('applicant');

$pageTitle = 'My Profile';
$currentUser = current_user();
$profile = ensure_candidate_profile($pdo, (int) $currentUser['id']);

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if (in_array($action, ['save_profile', 'submit_profile'], true)) {
            $profilePayload = [
                'headline' => trim((string) ($_POST['headline'] ?? '')),
                'bio' => trim((string) ($_POST['bio'] ?? '')),
                'experience_years' => (int) ($_POST['experience_years'] ?? 0),
                'education_level' => trim((string) ($_POST['education_level'] ?? '')),
                'location' => trim((string) ($_POST['location'] ?? '')),
                'expected_salary_min' => ($_POST['expected_salary_min'] ?? '') !== '' ? (float) $_POST['expected_salary_min'] : null,
                'expected_salary_max' => ($_POST['expected_salary_max'] ?? '') !== '' ? (float) $_POST['expected_salary_max'] : null,
                'availability' => trim((string) ($_POST['availability'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
            ];

            $statement = $pdo->prepare(
                'UPDATE candidate_profiles
                 SET headline = :headline,
                     bio = :bio,
                     experience_years = :experience_years,
                     education_level = :education_level,
                     location = :location,
                     expected_salary_min = :expected_salary_min,
                     expected_salary_max = :expected_salary_max,
                     availability = :availability,
                     address = :address,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute($profilePayload + ['id' => $profile['id']]);

            $profile = array_merge($profile, $profilePayload);

            if ($action === 'save_profile') {
                set_flash('success', 'Profile details updated.');
            }
        }

        if ($action === 'add_skill') {
            $skillName = trim((string) ($_POST['skill_name'] ?? ''));

            if ($skillName === '') {
                set_flash('error', 'Skill name is required.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO candidate_skills (profile_id, category_id, skill_name)
                     VALUES (:profile_id, :category_id, :skill_name)'
                );
                $statement->execute([
                    'profile_id' => $profile['id'],
                    'category_id' => $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
                    'skill_name' => $skillName,
                ]);
                set_flash('success', 'Skill added.');
            }
        }

        if ($action === 'delete_skill') {
            $statement = $pdo->prepare('DELETE FROM candidate_skills WHERE id = :id AND profile_id = :profile_id');
            $statement->execute([
                'id' => (int) ($_POST['skill_id'] ?? 0),
                'profile_id' => $profile['id'],
            ]);
            set_flash('success', 'Skill removed.');
        }

        if ($action === 'add_qualification') {
            $title = trim((string) ($_POST['qualification_title'] ?? ''));
            $institution = trim((string) ($_POST['institution_name'] ?? ''));

            if ($title === '' || $institution === '') {
                set_flash('error', 'Qualification title and institution are required.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO candidate_qualifications
                        (profile_id, qualification_title, institution_name, year_completed)
                     VALUES
                        (:profile_id, :qualification_title, :institution_name, :year_completed)'
                );
                $statement->execute([
                    'profile_id' => $profile['id'],
                    'qualification_title' => $title,
                    'institution_name' => $institution,
                    'year_completed' => trim((string) ($_POST['year_completed'] ?? '')),
                ]);
                set_flash('success', 'Qualification added.');
            }
        }

        if ($action === 'delete_qualification') {
            $statement = $pdo->prepare('DELETE FROM candidate_qualifications WHERE id = :id AND profile_id = :profile_id');
            $statement->execute([
                'id' => (int) ($_POST['qualification_id'] ?? 0),
                'profile_id' => $profile['id'],
            ]);
            set_flash('success', 'Qualification removed.');
        }

        if ($action === 'add_experience') {
            $jobTitle = trim((string) ($_POST['job_title'] ?? ''));
            $companyName = trim((string) ($_POST['company_name'] ?? ''));

            if ($jobTitle === '' || $companyName === '') {
                set_flash('error', 'Job title and company name are required.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO candidate_experiences
                        (profile_id, job_title, company_name, start_date, end_date, description)
                     VALUES
                        (:profile_id, :job_title, :company_name, :start_date, :end_date, :description)'
                );
                $statement->execute([
                    'profile_id' => $profile['id'],
                    'job_title' => $jobTitle,
                    'company_name' => $companyName,
                    'start_date' => $_POST['start_date'] !== '' ? $_POST['start_date'] : null,
                    'end_date' => $_POST['end_date'] !== '' ? $_POST['end_date'] : null,
                    'description' => trim((string) ($_POST['experience_description'] ?? '')),
                ]);
                set_flash('success', 'Work experience added.');
            }
        }

        if ($action === 'delete_experience') {
            $statement = $pdo->prepare('DELETE FROM candidate_experiences WHERE id = :id AND profile_id = :profile_id');
            $statement->execute([
                'id' => (int) ($_POST['experience_id'] ?? 0),
                'profile_id' => $profile['id'],
            ]);
            set_flash('success', 'Experience removed.');
        }

        if ($action === 'upload_cv') {
            $upload = handle_upload('cv_file', CV_UPLOAD_DIR, ['pdf', 'doc', 'docx']);
            if ($upload !== null) {
                $statement = $pdo->prepare(
                    'INSERT INTO candidate_documents (profile_id, document_type, file_name, original_name, created_at)
                     VALUES (:profile_id, "CV", :file_name, :original_name, NOW())'
                );
                $statement->execute([
                    'profile_id' => $profile['id'],
                    'file_name' => $upload['file_name'],
                    'original_name' => $upload['original_name'],
                ]);
                set_flash('success', 'CV uploaded successfully.');
            }
        }

        if ($action === 'upload_certificate') {
            $upload = handle_upload('certificate_file', CERTIFICATE_UPLOAD_DIR, ['pdf', 'jpg', 'jpeg', 'png']);
            if ($upload !== null) {
                $statement = $pdo->prepare(
                    'INSERT INTO candidate_documents (profile_id, document_type, file_name, original_name, created_at)
                     VALUES (:profile_id, "Certificate", :file_name, :original_name, NOW())'
                );
                $statement->execute([
                    'profile_id' => $profile['id'],
                    'file_name' => $upload['file_name'],
                    'original_name' => $upload['original_name'],
                ]);
                set_flash('success', 'Certificate uploaded successfully.');
            }
        }

        if ($action === 'delete_document') {
            $documentStatement = $pdo->prepare(
                'SELECT id, document_type, file_name
                 FROM candidate_documents
                 WHERE id = :id AND profile_id = :profile_id
                 LIMIT 1'
            );
            $documentStatement->execute([
                'id' => (int) ($_POST['document_id'] ?? 0),
                'profile_id' => $profile['id'],
            ]);
            $document = $documentStatement->fetch();

            if ($document !== false) {
                $baseDirectory = $document['document_type'] === 'CV' ? CV_UPLOAD_DIR : CERTIFICATE_UPLOAD_DIR;
                $filePath = $baseDirectory . DIRECTORY_SEPARATOR . $document['file_name'];
                if (is_file($filePath)) {
                    unlink($filePath);
                }

                $deleteStatement = $pdo->prepare('DELETE FROM candidate_documents WHERE id = :id');
                $deleteStatement->execute(['id' => $document['id']]);
                set_flash('success', 'Document removed.');
            }
        }

        if ($action === 'submit_profile') {
            $skillsStatement = $pdo->prepare('SELECT COUNT(*) FROM candidate_skills WHERE profile_id = :profile_id');
            $skillsStatement->execute(['profile_id' => $profile['id']]);
            $skillsCount = (int) $skillsStatement->fetchColumn();

            $cvStatement = $pdo->prepare(
                'SELECT COUNT(*) FROM candidate_documents WHERE profile_id = :profile_id AND document_type = "CV"'
            );
            $cvStatement->execute(['profile_id' => $profile['id']]);
            $cvCount = (int) $cvStatement->fetchColumn();

            if (
                trim((string) $profile['headline']) === '' ||
                trim((string) $profile['bio']) === '' ||
                trim((string) $profile['location']) === '' ||
                trim((string) $profile['education_level']) === '' ||
                trim((string) $profile['availability']) === '' ||
                $skillsCount === 0 ||
                $cvCount === 0
            ) {
                set_flash('error', 'Please complete profile details, add at least one skill, and upload a CV before submitting.');
            } else {
                $statement = $pdo->prepare(
                    'UPDATE candidate_profiles
                     SET status = "Pending",
                         submitted_at = NOW(),
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $statement->execute(['id' => $profile['id']]);

                create_notification(
                    $pdo,
                    (int) $currentUser['id'],
                    'Profile submitted',
                    'Your applicant profile has been submitted to admin for approval.'
                );

                set_flash('success', 'Your profile has been submitted for admin approval.');
            }
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable instanceof RuntimeException ? $throwable->getMessage() : 'The requested profile action failed.');
    }

    redirect('applicant/profile.php');
}

$profileStatement = $pdo->prepare('SELECT * FROM candidate_profiles WHERE user_id = :user_id LIMIT 1');
$profileStatement->execute(['user_id' => $currentUser['id']]);
$profile = (array) $profileStatement->fetch();

$categories = fetch_skill_categories($pdo);

$skillsStatement = $pdo->prepare('SELECT * FROM candidate_skills WHERE profile_id = :profile_id ORDER BY id DESC');
$skillsStatement->execute(['profile_id' => $profile['id']]);
$skills = $skillsStatement->fetchAll();

$qualificationsStatement = $pdo->prepare(
    'SELECT * FROM candidate_qualifications WHERE profile_id = :profile_id ORDER BY id DESC'
);
$qualificationsStatement->execute(['profile_id' => $profile['id']]);
$qualifications = $qualificationsStatement->fetchAll();

$experiencesStatement = $pdo->prepare(
    'SELECT * FROM candidate_experiences WHERE profile_id = :profile_id ORDER BY id DESC'
);
$experiencesStatement->execute(['profile_id' => $profile['id']]);
$experiences = $experiencesStatement->fetchAll();

$documentsStatement = $pdo->prepare(
    'SELECT * FROM candidate_documents WHERE profile_id = :profile_id ORDER BY id DESC'
);
$documentsStatement->execute(['profile_id' => $profile['id']]);
$documents = $documentsStatement->fetchAll();

require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<section class="two-column">
    <article class="form-card">
        <div class="section-head">
            <div>
                <h2>Profile Details</h2>
                <p>Public listings remain anonymous. Full identity and documents stay admin-only.</p>
            </div>
            <span class="badge <?= e(status_badge_class($profile['status'])) ?>"><?= e($profile['status']) ?></span>
        </div>

        <form method="post">
            <div class="form-grid">
                <div class="field full">
                    <label for="headline">Professional Headline</label>
                    <input id="headline" name="headline" type="text" value="<?= e($profile['headline']) ?>">
                </div>
                <div class="field full">
                    <label for="bio">Short Bio</label>
                    <textarea id="bio" name="bio"><?= e($profile['bio']) ?></textarea>
                </div>
                <div class="field">
                    <label for="experience_years">Years of Experience</label>
                    <input id="experience_years" name="experience_years" type="number" min="0" value="<?= e((string) $profile['experience_years']) ?>">
                </div>
                <div class="field">
                    <label for="education_level">Education Level</label>
                    <input id="education_level" name="education_level" type="text" value="<?= e($profile['education_level']) ?>">
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" name="location" type="text" value="<?= e($profile['location']) ?>">
                </div>
                <div class="field">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <option value="">Select availability</option>
                        <option value="Immediate" <?= selected_option((string) $profile['availability'], 'Immediate') ?>>Immediate</option>
                        <option value="2 Weeks" <?= selected_option((string) $profile['availability'], '2 Weeks') ?>>2 Weeks</option>
                        <option value="1 Month" <?= selected_option((string) $profile['availability'], '1 Month') ?>>1 Month</option>
                        <option value="Open" <?= selected_option((string) $profile['availability'], 'Open') ?>>Open</option>
                    </select>
                </div>
                <div class="field">
                    <label for="expected_salary_min">Expected Salary Min</label>
                    <input id="expected_salary_min" name="expected_salary_min" type="number" min="0" step="0.01" value="<?= e((string) $profile['expected_salary_min']) ?>">
                </div>
                <div class="field">
                    <label for="expected_salary_max">Expected Salary Max</label>
                    <input id="expected_salary_max" name="expected_salary_max" type="number" min="0" step="0.01" value="<?= e((string) $profile['expected_salary_max']) ?>">
                </div>
                <div class="field full">
                    <label for="address">Full Address (admin only)</label>
                    <textarea id="address" name="address"><?= e($profile['address']) ?></textarea>
                </div>
            </div>

            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit" name="action" value="save_profile">Save Profile</button>
                <button class="ghost-button" type="submit" name="action" value="submit_profile">Submit for Approval</button>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Profile Checklist</h2>
        <ul class="list-clean">
            <li><strong>Candidate Code:</strong> <?= e($profile['candidate_code']) ?></li>
            <li><strong>Approval Status:</strong> <?= e($profile['status']) ?></li>
            <li><strong>Submitted:</strong> <?= e($profile['submitted_at'] ? format_date($profile['submitted_at']) : 'Not yet submitted') ?></li>
            <li><strong>Admin Notes:</strong> <?= e($profile['approval_notes'] ?: 'No notes yet') ?></li>
            <li><strong>Privacy:</strong> Public visitors and employers only see anonymous profile fields until admin approves access.</li>
        </ul>
    </article>
</section>

<section class="two-column">
    <article class="form-card">
        <h2>Add Skill</h2>
        <form method="post">
            <input type="hidden" name="action" value="add_skill">
            <div class="form-grid">
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Optional category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="skill_name">Skill Name</label>
                    <input id="skill_name" name="skill_name" type="text">
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit">Add Skill</button>
            </div>
        </form>

        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Skill</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skills as $skill): ?>
                        <tr>
                            <td><?= e($skill['skill_name']) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_skill">
                                    <input type="hidden" name="skill_id" value="<?= e((string) $skill['id']) ?>">
                                    <button class="ghost-button" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($skills === []): ?>
                        <tr>
                            <td colspan="2">No skills added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="form-card">
        <h2>Add Qualification</h2>
        <form method="post">
            <input type="hidden" name="action" value="add_qualification">
            <div class="form-grid">
                <div class="field">
                    <label for="qualification_title">Qualification Title</label>
                    <input id="qualification_title" name="qualification_title" type="text">
                </div>
                <div class="field">
                    <label for="institution_name">Institution</label>
                    <input id="institution_name" name="institution_name" type="text">
                </div>
                <div class="field full">
                    <label for="year_completed">Year Completed</label>
                    <input id="year_completed" name="year_completed" type="text">
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit">Add Qualification</button>
            </div>
        </form>

        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Qualification</th>
                        <th>Institution</th>
                        <th>Year</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qualifications as $qualification): ?>
                        <tr>
                            <td><?= e($qualification['qualification_title']) ?></td>
                            <td><?= e($qualification['institution_name']) ?></td>
                            <td><?= e($qualification['year_completed']) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_qualification">
                                    <input type="hidden" name="qualification_id" value="<?= e((string) $qualification['id']) ?>">
                                    <button class="ghost-button" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($qualifications === []): ?>
                        <tr>
                            <td colspan="4">No qualifications added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="two-column">
    <article class="form-card">
        <h2>Add Work Experience</h2>
        <form method="post">
            <input type="hidden" name="action" value="add_experience">
            <div class="form-grid">
                <div class="field">
                    <label for="job_title">Job Title</label>
                    <input id="job_title" name="job_title" type="text">
                </div>
                <div class="field">
                    <label for="company_name">Company</label>
                    <input id="company_name" name="company_name" type="text">
                </div>
                <div class="field">
                    <label for="start_date">Start Date</label>
                    <input id="start_date" name="start_date" type="date">
                </div>
                <div class="field">
                    <label for="end_date">End Date</label>
                    <input id="end_date" name="end_date" type="date">
                </div>
                <div class="field full">
                    <label for="experience_description">Description</label>
                    <textarea id="experience_description" name="experience_description"></textarea>
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit">Add Experience</button>
            </div>
        </form>

        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Period</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($experiences as $experience): ?>
                        <tr>
                            <td>
                                <strong><?= e($experience['job_title']) ?></strong><br>
                                <?= e($experience['company_name']) ?>
                            </td>
                            <td><?= e(format_date($experience['start_date'])) ?> - <?= e($experience['end_date'] ? format_date($experience['end_date']) : 'Present') ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete_experience">
                                    <input type="hidden" name="experience_id" value="<?= e((string) $experience['id']) ?>">
                                    <button class="ghost-button" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($experiences === []): ?>
                        <tr>
                            <td colspan="3">No work experience added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="form-card">
        <h2>Documents</h2>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_cv">
            <div class="form-grid">
                <div class="field full">
                    <label for="cv_file">Upload CV</label>
                    <input id="cv_file" name="cv_file" type="file" accept=".pdf,.doc,.docx">
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit">Upload CV</button>
            </div>
        </form>

        <form method="post" enctype="multipart/form-data" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="upload_certificate">
            <div class="form-grid">
                <div class="field full">
                    <label for="certificate_file">Upload Certificate</label>
                    <input id="certificate_file" name="certificate_file" type="file" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="card-actions" style="margin-top: 1rem;">
                <button class="button button-dark" type="submit">Upload Certificate</button>
            </div>
        </form>

        <div class="table-wrap" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>File</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><?= e($document['document_type']) ?></td>
                            <td><?= e($document['original_name']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="ghost-button" href="<?= e(app_url('download-document.php?id=' . $document['id'])) ?>" target="_blank" rel="noopener">View</a>
                                    <form method="post">
                                        <input type="hidden" name="action" value="delete_document">
                                        <input type="hidden" name="document_id" value="<?= e((string) $document['id']) ?>">
                                        <button class="ghost-button" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($documents === []): ?>
                        <tr>
                            <td colspan="3">No documents uploaded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
