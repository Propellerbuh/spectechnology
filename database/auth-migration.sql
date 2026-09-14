CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, username VARCHAR(40) NOT NULL, email VARCHAR(254) NOT NULL,
 password_hash VARCHAR(255) NOT NULL, role ENUM('user','admin') NOT NULL DEFAULT 'user', registration_id BIGINT UNSIGNED NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_users_username(username), UNIQUE KEY uq_users_registration(registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS registration_messages (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, registration_id BIGINT UNSIGNED NOT NULL, author_user_id BIGINT UNSIGNED NULL,
 message TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY idx_messages_registration(registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
