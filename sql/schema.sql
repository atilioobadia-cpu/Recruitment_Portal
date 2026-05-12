CREATE DATABASE IF NOT EXISTS recruitment_broker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE recruitment_broker;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'applicant', 'employer') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS skill_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS candidate_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    candidate_code VARCHAR(30) NOT NULL UNIQUE,
    headline VARCHAR(255) NULL,
    bio TEXT NULL,
    experience_years INT UNSIGNED NOT NULL DEFAULT 0,
    education_level VARCHAR(150) NULL,
    location VARCHAR(150) NULL,
    expected_salary_min DECIMAL(10, 2) NULL,
    expected_salary_max DECIMAL(10, 2) NULL,
    availability VARCHAR(80) NULL,
    address TEXT NULL,
    status ENUM('Draft', 'Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Draft',
    approval_notes TEXT NULL,
    admin_notes TEXT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS candidate_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    skill_name VARCHAR(150) NOT NULL,
    CONSTRAINT fk_candidate_skills_profile
        FOREIGN KEY (profile_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_candidate_skills_category
        FOREIGN KEY (category_id) REFERENCES skill_categories(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS candidate_qualifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    qualification_title VARCHAR(200) NOT NULL,
    institution_name VARCHAR(200) NOT NULL,
    year_completed VARCHAR(10) NULL,
    CONSTRAINT fk_candidate_qualifications_profile
        FOREIGN KEY (profile_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS candidate_experiences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    job_title VARCHAR(200) NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    description TEXT NULL,
    CONSTRAINT fk_candidate_experiences_profile
        FOREIGN KEY (profile_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS candidate_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    document_type ENUM('CV', 'Certificate') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_documents_profile
        FOREIGN KEY (profile_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employer_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    company_name VARCHAR(200) NOT NULL,
    industry VARCHAR(150) NULL,
    company_size VARCHAR(100) NULL,
    location VARCHAR(150) NULL,
    address TEXT NULL,
    website VARCHAR(200) NULL,
    contact_person VARCHAR(200) NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employer_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category_id INT UNSIGNED NULL,
    location VARCHAR(150) NOT NULL,
    employment_type VARCHAR(80) NOT NULL,
    salary_min DECIMAL(10, 2) NULL,
    salary_max DECIMAL(10, 2) NULL,
    summary TEXT NOT NULL,
    description LONGTEXT NOT NULL,
    requirements LONGTEXT NULL,
    status ENUM('Draft', 'Published', 'Closed') NOT NULL DEFAULT 'Draft',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_jobs_category
        FOREIGN KEY (category_id) REFERENCES skill_categories(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_jobs_creator
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS job_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    applicant_user_id INT UNSIGNED NOT NULL,
    profile_id INT UNSIGNED NOT NULL,
    cover_message TEXT NOT NULL,
    status ENUM('Pending', 'Reviewing', 'Approved', 'Rejected', 'Connected', 'Closed') NOT NULL DEFAULT 'Pending',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_job_applications_job
        FOREIGN KEY (job_id) REFERENCES jobs(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_job_applications_user
        FOREIGN KEY (applicant_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_job_applications_profile
        FOREIGN KEY (profile_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employer_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_user_id INT UNSIGNED NOT NULL,
    candidate_profile_id INT UNSIGNED NULL,
    requirement_title VARCHAR(200) NOT NULL,
    requirement_details LONGTEXT NOT NULL,
    message_to_admin LONGTEXT NOT NULL,
    status ENUM('Pending', 'Reviewing', 'Approved', 'Rejected', 'Connected', 'Closed') NOT NULL DEFAULT 'Pending',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employer_requests_user
        FOREIGN KEY (employer_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_employer_requests_candidate
        FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profiles(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(150) NOT NULL UNIQUE,
    setting_value LONGTEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_content_items (
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
);
