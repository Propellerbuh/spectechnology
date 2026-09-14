CREATE TABLE IF NOT EXISTS registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country VARCHAR(40) NULL,
  other_country VARCHAR(120) NULL,
  role VARCHAR(80) NULL,
  other_role VARCHAR(160) NULL,
  name VARCHAR(160) NULL,
  company VARCHAR(200) NULL,
  email VARCHAR(254) NOT NULL,
  details TEXT NOT NULL,
  language ENUM('en','pl') NOT NULL DEFAULT 'en',
  consent_at DATETIME NULL,
  status ENUM('new','reviewed','approved','archived') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_registrations_status_created (status,created_at), KEY idx_registrations_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registration_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(240) NOT NULL,
  stored_name VARCHAR(80) NOT NULL,
  mime_type VARCHAR(160) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_registration_files_stored (stored_name), KEY idx_registration_files_registration (registration_id),
  CONSTRAINT fk_registration_files_registration FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


