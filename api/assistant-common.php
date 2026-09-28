<?php
declare(strict_types=1);

require_once __DIR__.'/db.php';

function ensure_assistant_schema(): void {
    static $ready=false;
    if ($ready) return;
    $pdo=db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS assistant_conversations (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      public_id CHAR(32) NOT NULL UNIQUE,
      language ENUM('en','pl') NOT NULL DEFAULT 'en',
      name VARCHAR(160) NULL,
      email VARCHAR(254) NULL,
      phone VARCHAR(60) NULL,
      consent_at DATETIME NULL,
      status ENUM('new','read','contacted','archived') NOT NULL DEFAULT 'new',
      page_url VARCHAR(500) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_assistant_status_created(status,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS assistant_messages (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      conversation_id BIGINT UNSIGNED NOT NULL,
      sender ENUM('visitor','assistant') NOT NULL,
      message TEXT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_assistant_messages_conversation(conversation_id,id),
      CONSTRAINT fk_assistant_message_conversation FOREIGN KEY (conversation_id) REFERENCES assistant_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready=true;
}

function assistant_conversation(string $publicId): ?array {
    $stmt=db()->prepare('SELECT * FROM assistant_conversations WHERE public_id=?');
    $stmt->execute([$publicId]);
    return $stmt->fetch() ?: null;
}
