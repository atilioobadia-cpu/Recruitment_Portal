USE recruitment_broker;

INSERT INTO users (id, role, first_name, last_name, email, phone, password_hash, account_status, created_at) VALUES
(1, 'admin', 'System', 'Admin', 'admin@blackbridge.test', '+254700000001', '$2y$10$wPB71XhnhHCfSRupTzNxg.2iQ0SEeWML8G1LBcGNiGBYGikTId.Km', 'Active', NOW()),
(2, 'applicant', 'Grace', 'Njeri', 'applicant@blackbridge.test', '+254700000002', '$2y$10$W1Ku9HBKy8piLryMPCzlLO3U5q3GJ47.FYqVYF6usaPemW/cF2fmW', 'Active', NOW()),
(3, 'employer', 'Daniel', 'Mwangi', 'employer@blackbridge.test', '+254700000003', '$2y$10$3eLq1xltTauxem0eHdPb3.z8I/TIUnj1llSAMjA1.I6VD8N8LXnOi', 'Active', NOW()),
(4, 'applicant', 'Faith', 'Otieno', 'pending@blackbridge.test', '+254700000004', '$2y$10$W1Ku9HBKy8piLryMPCzlLO3U5q3GJ47.FYqVYF6usaPemW/cF2fmW', 'Active', NOW());

INSERT INTO skill_categories (id, name, description, is_active, created_at) VALUES
(1, 'Domestic Staff', 'Housekeepers, nannies, caregivers, and domestic support roles.', 1, NOW()),
(2, 'Hospitality', 'Front office, kitchen, service, and guest-facing professionals.', 1, NOW()),
(3, 'Construction', 'Skilled site workers, technicians, and operational support.', 1, NOW()),
(4, 'Office Support', 'Administrative, clerical, and support professionals.', 1, NOW()),
(5, 'Security', 'Guards, supervisors, and site protection specialists.', 1, NOW());

INSERT INTO candidate_profiles (
    id, user_id, candidate_code, headline, bio, experience_years, education_level, location,
    expected_salary_min, expected_salary_max, availability, address, status, approval_notes,
    is_featured, submitted_at, approved_at, created_at, updated_at
) VALUES
(
    1, 2, 'CAND-1001', 'Experienced Hospitality Supervisor',
    'Professional hospitality worker with strong guest service and team coordination experience.',
    6, 'Diploma', 'Nairobi', 45000.00, 65000.00, 'Immediate',
    'Westlands, Nairobi', 'Approved', 'Approved for anonymous public listing.', 1,
    NOW(), NOW(), NOW(), NOW()
),
(
    2, 4, 'CAND-1002', 'Reliable Office Assistant',
    'Organized applicant with office coordination, scheduling, and records support skills.',
    3, 'Certificate', 'Mombasa', 28000.00, 38000.00, '2 Weeks',
    'Nyali, Mombasa', 'Pending', 'Awaiting admin review.', 0,
    NOW(), NULL, NOW(), NOW()
);

INSERT INTO candidate_skills (profile_id, category_id, skill_name) VALUES
(1, 2, 'Hospitality Operations'),
(1, 2, 'Guest Relations'),
(1, 4, 'Team Coordination'),
(2, 4, 'Office Administration'),
(2, 4, 'Scheduling');

INSERT INTO candidate_qualifications (profile_id, qualification_title, institution_name, year_completed) VALUES
(1, 'Diploma in Hospitality Management', 'Kenya Institute of Hospitality', '2019'),
(2, 'Office Administration Certificate', 'Coastal Training Centre', '2022');

INSERT INTO candidate_experiences (profile_id, job_title, company_name, start_date, end_date, description) VALUES
(1, 'Hospitality Supervisor', 'Metro Suites', '2020-03-01', '2025-02-28', 'Managed guest operations, staff schedules, and service quality.'),
(2, 'Office Assistant', 'BlueWave Logistics', '2022-06-01', NULL, 'Supports scheduling, document handling, and visitor coordination.');

INSERT INTO employer_profiles (
    id, user_id, company_name, industry, company_size, location, address, website, contact_person, description, created_at, updated_at
) VALUES
(
    1, 3, 'Northfield Client Services', 'Hospitality', '50-100', 'Nairobi',
    'Upper Hill, Nairobi', 'https://northfield.example.com', 'Daniel Mwangi',
    'Private client company seeking vetted talent through a brokered hiring process.',
    NOW(), NOW()
);

INSERT INTO jobs (
    id, title, category_id, location, employment_type, salary_min, salary_max, summary, description, requirements, status, is_featured, created_by, created_at, updated_at
) VALUES
(
    1, 'Front Office Supervisor', 2, 'Nairobi', 'Full Time', 50000.00, 70000.00,
    'Lead front office coordination and guest service quality across a busy hospitality environment.',
    'Admin-managed hospitality role for a client requiring leadership, shift planning, and customer service oversight.',
    'Minimum 4 years experience, guest service exposure, and team coordination ability.',
    'Published', 1, 1, NOW(), NOW()
),
(
    2, 'Office Support Assistant', 4, 'Mombasa', 'Full Time', 30000.00, 42000.00,
    'Support scheduling, records handling, and internal office workflow.',
    'Public office support role managed by admin for employer clients seeking organized talent.',
    'Administrative support skills, communication, and document handling.',
    'Published', 0, 1, NOW(), NOW()
);

INSERT INTO employer_requests (
    id, employer_user_id, candidate_profile_id, requirement_title, requirement_details, message_to_admin, status, admin_notes, created_at, updated_at
) VALUES
(
    1, 3, 1, 'Request for hospitality candidate',
    'Interested in an experienced hospitality supervisor for a client-facing operations role.',
    'Please assess this candidate and advise whether a brokered introduction is appropriate.',
    'Reviewing', 'Admin reviewing fit and coordinating next steps separately.', NOW(), NOW()
);

INSERT INTO job_applications (
    id, job_id, applicant_user_id, profile_id, cover_message, status, admin_notes, created_at, updated_at
) VALUES
(
    1, 1, 2, 1,
    'I have managed guest operations teams and would be a strong fit for this hospitality leadership role.',
    'Pending', 'Awaiting admin screening.', NOW(), NOW()
);

INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES
(2, 'Profile approved', 'Your profile CAND-1001 has been approved and is now visible anonymously to employers and public visitors.', 0, NOW()),
(3, 'Request received', 'Your request for an approved candidate is currently under admin review.', 0, NOW());

INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES
('Public Visitor', 'visitor@example.com', 'Brokerage inquiry', 'I would like to understand how your brokered recruitment model works.', NOW());
