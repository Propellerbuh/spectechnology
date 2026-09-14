CREATE TABLE IF NOT EXISTS registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('client','architect','manufacturer') NOT NULL,
  name VARCHAR(160) NOT NULL,
  company VARCHAR(200) NULL,
  email VARCHAR(254) NOT NULL,
  details TEXT NOT NULL,
  language ENUM('en','pl') NOT NULL DEFAULT 'en',
  consent_at DATETIME NOT NULL,
  status ENUM('new','reviewed','approved','archived') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_registrations_email_role (email,role),
  KEY idx_registrations_status_created (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
