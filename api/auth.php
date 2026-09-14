<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$sessionDir=dirname(__DIR__).'/private/sessions';
if(!is_dir($sessionDir)) @mkdir($sessionDir,0750,true);
session_save_path($sessionDir);
session_name('spectech_session');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request'); } }
function current_user(): ?array { if (empty($_SESSION['user_id'])) return null; $s=db()->prepare('SELECT id,username,email,role,registration_id FROM users WHERE id=? AND active=1'); $s->execute([$_SESSION['user_id']]); return $s->fetch() ?: null; }
function require_user(): array { $u=current_user(); if (!$u) { header('Location: /login.php'); exit; } return $u; }
function e(?string $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }


