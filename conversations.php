<?php
declare(strict_types=1);
require __DIR__.'/api/auth.php';
require __DIR__.'/api/assistant-common.php';
$user=require_user();
if (($user['role']??'')!=='admin') { http_response_code(403); exit('Access denied'); }
ensure_assistant_schema();
$statuses=['new'=>'New / Nowa','read'=>'Read / Przeczytana','contacted'=>'Contacted / Kontakt wykonany','archived'=>'Archived / Archiwum'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $id=(int)($_POST['conversation_id']??0);
    $status=(string)($_POST['status']??'read');
    if ($id>0 && isset($statuses[$status])) db()->prepare('UPDATE assistant_conversations SET status=?,updated_at=NOW() WHERE id=?')->execute([$status,$id]);
    header('Location: /conversations.php?id='.$id); exit;
}
$selectedId=(int)($_GET['id']??0);
$items=db()->query("SELECT c.*,(SELECT COUNT(*) FROM assistant_messages m WHERE m.conversation_id=c.id) message_count,(SELECT LEFT(message,180) FROM assistant_messages m WHERE m.conversation_id=c.id AND m.sender='visitor' ORDER BY m.id DESC LIMIT 1) last_message FROM assistant_conversations c ORDER BY c.updated_at DESC")->fetchAll();
$selected=null;$messages=[];
if ($selectedId) {
    $stmt=db()->prepare('SELECT * FROM assistant_conversations WHERE id=?');$stmt->execute([$selectedId]);$selected=$stmt->fetch()?:null;
    if ($selected) {$stmt=db()->prepare('SELECT * FROM assistant_messages WHERE conversation_id=? ORDER BY id');$stmt->execute([$selectedId]);$messages=$stmt->fetchAll();if($selected['status']==='new'){db()->prepare("UPDATE assistant_conversations SET status='read' WHERE id=?")->execute([$selectedId]);$selected['status']='read';}}
}
?><!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Rozmowy — SPECTECHNOLOGY</title><link rel="stylesheet" href="/assets/site-prod.css"><link rel="stylesheet" href="/assets/portal.css"><link rel="stylesheet" href="/assets/conversations.css"></head><body class="portal"><header class="portal-header"><a href="/"><img src="/noBgColor-dark.png" alt="SPECTECHNOLOGY"></a><nav class="portal-nav"><a href="/dashboard.php">Zgłoszenia</a><a class="active" href="/conversations.php">Rozmowy</a><span><b><?=e($user['username'])?></b> · <a href="/logout.php">Wyloguj</a></span></nav></header><main class="conversation-admin"><aside class="conversation-list"><div class="conversation-list-head"><p class="eyebrow">ADMIN PANEL</p><h1>Rozmowy</h1><span><?=count($items)?> zapisanych</span></div><?php if(!$items):?><p class="conversation-empty">Brak rozmów.</p><?php endif?><?php foreach($items as $item):?><a class="conversation-row <?=$selectedId===$item['id']?'selected':''?>" href="?id=<?=$item['id']?>"><span class="conversation-dot status-<?=e($item['status'])?>"></span><span><strong><?=e($item['name']?:$item['email']?:'Anonimowy użytkownik')?></strong><small><?=e($item['language'])?> · <?=e($item['updated_at'])?> · <?=e((string)$item['message_count'])?> wiadomości</small><em><?=e($item['last_message']?:'Rozmowa rozpoczęta')?></em></span></a><?php endforeach?></aside><section class="conversation-detail"><?php if(!$selected):?><div class="conversation-placeholder"><h2>Wybierz rozmowę</h2><p>Pełna historia, kontakt i status pojawią się tutaj.</p></div><?php else:?><div class="conversation-detail-head"><div><p class="eyebrow">ROZMOWA #<?=e((string)$selected['id'])?></p><h2><?=e($selected['name']?:$selected['email']?:'Anonimowy użytkownik')?></h2><p><?=e($selected['email']?:'Brak email')?><?=!empty($selected['phone'])?' · '.e($selected['phone']):''?></p><small>Strona: <?=e($selected['page_url']?:'/')?> · <?=e($selected['created_at'])?></small></div><form method="post" class="conversation-status"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="conversation_id" value="<?=$selected['id']?>"><label>Status<select name="status"><?php foreach($statuses as $key=>$label):?><option value="<?=$key?>" <?=$selected['status']===$key?'selected':''?>><?=e($label)?></option><?php endforeach?></select></label><button class="primary">Zapisz</button></form></div><div class="conversation-transcript"><?php foreach($messages as $message):?><div class="transcript-message <?=e($message['sender'])?>"><small><?=$message['sender']==='visitor'?'Użytkownik':'Asystent'?> · <?=e($message['created_at'])?></small><p><?=nl2br(e($message['message']))?></p></div><?php endforeach?></div><?php endif?></section></main></body></html>
