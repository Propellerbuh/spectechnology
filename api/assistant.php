<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
require __DIR__.'/auth.php';
require __DIR__.'/assistant-common.php';
ensure_assistant_schema();

function assistant_fail(string $message,int $status=422): never { http_response_code($status); echo json_encode(['error'=>$message]); exit; }
function assistant_body(): array { $data=json_decode((string)file_get_contents('php://input'),true); return is_array($data)?$data:[]; }
function assistant_clean(string $value,int $max): string { return mb_substr(trim($value),0,$max); }

$data=assistant_body();
$action=(string)($data['action']??'');
$pdo=db();
$window=(int)($_SESSION['assistant_window']??0);
if ($window<time()-3600) { $_SESSION['assistant_window']=time(); $_SESSION['assistant_requests']=0; }
$_SESSION['assistant_requests']=(int)($_SESSION['assistant_requests']??0)+1;
if ($_SESSION['assistant_requests']>120) assistant_fail('Too many requests. Please try again later.',429);

if ($action==='start') {
    $publicId=bin2hex(random_bytes(16));
    $language=($data['language']??'')==='pl'?'pl':'en';
    $page=assistant_clean((string)($data['page_url']??''),500);
    $stmt=$pdo->prepare('INSERT INTO assistant_conversations(public_id,language,page_url) VALUES(?,?,?)');
    $stmt->execute([$publicId,$language,$page?:null]);
    echo json_encode(['ok'=>true,'conversation_id'=>$publicId]); exit;
}

$publicId=preg_replace('/[^a-f0-9]/','',(string)($data['conversation_id']??''));
if (strlen($publicId)!==32) assistant_fail('Invalid conversation.');
$conversation=assistant_conversation($publicId);
if (!$conversation) assistant_fail('Conversation not found.',404);

if ($action==='message') {
    $sender=($data['sender']??'')==='assistant'?'assistant':'visitor';
    $message=assistant_clean((string)($data['message']??''),4000);
    if ($message==='') assistant_fail('Message is empty.');
    $stmt=$pdo->prepare('INSERT INTO assistant_messages(conversation_id,sender,message) VALUES(?,?,?)');
    $stmt->execute([$conversation['id'],$sender,$message]);
    $pdo->prepare('UPDATE assistant_conversations SET updated_at=NOW() WHERE id=?')->execute([$conversation['id']]);
    echo json_encode(['ok'=>true]); exit;
}

if ($action==='contact') {
    $name=assistant_clean((string)($data['name']??''),160);
    $email=filter_var(trim((string)($data['email']??'')),FILTER_VALIDATE_EMAIL);
    $phone=assistant_clean((string)($data['phone']??''),60);
    if (!$email) assistant_fail('Please enter a valid email address.');
    if (($data['consent']??false)!==true) assistant_fail('Contact consent is required.');
    $stmt=$pdo->prepare('UPDATE assistant_conversations SET name=?,email=?,phone=?,consent_at=NOW(),status="new",updated_at=NOW() WHERE id=?');
    $stmt->execute([$name?:null,$email,$phone?:null,$conversation['id']]);
    $mail="New assistant conversation\n\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\n\nAdmin: https://spectechnology.pl/conversations.php?id=".$conversation['id'];
    @mail('office@spectechnology.pl','SPECTECHNOLOGY — new assistant conversation',$mail,"From: office@spectechnology.pl\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8",'-f office@spectechnology.pl');
    echo json_encode(['ok'=>true]); exit;
}

assistant_fail('Unknown action.');
