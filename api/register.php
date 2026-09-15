<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
require __DIR__ . '/auth.php';

function fail(string $message, int $status=422): never { http_response_code($status); echo json_encode(['error'=>$message]); exit; }
function value(string $key): string { return trim((string)($_POST[$key] ?? '')); }

$countries=['Ireland','Poland','Other'];
$roles=['private_client','developer','architect','construction_company','main_contractor','timber_frame_manufacturer','modular_manufacturer','mobile_home_manufacturer','component_supplier','installer','engineer','planning_consultant','surveyor','logistics','estate_agent','investor','public_body','industry_association','other'];
$country=value('country'); $otherCountry=value('other_country'); $role=value('role'); $otherRole=value('other_role');
$name=value('name'); $company=value('company'); $email=filter_var(value('email'), FILTER_VALIDATE_EMAIL); $details=value('details'); $language=value('language')==='pl'?'pl':'en';
$signedIn=current_user(); $linkedUserId=($signedIn && $email && strcasecmp((string)$signedIn['email'],(string)$email)===0)?(int)$signedIn['id']:null;
if ($country!=='' && !in_array($country,$countries,true)) fail('Invalid country.');
if ($role!=='' && !in_array($role,$roles,true)) fail('Invalid role.');
if (!$email || $details==='' || value('consent')!=='1') fail('Please complete all required fields and accept contact consent.');
if (mb_strlen($name)>160 || mb_strlen($company)>200 || mb_strlen($details)>5000 || mb_strlen($otherCountry)>120 || mb_strlen($otherRole)>160) fail('One or more fields are too long.');

$allowed=[
 'pdf'=>['application/pdf'], 'doc'=>['application/msword','application/octet-stream'],
 'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip','application/octet-stream'],
 'xml'=>['application/xml','text/xml','text/plain'], 'xls'=>['application/vnd.ms-excel','application/octet-stream'],
 'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip','application/octet-stream'],
 'csv'=>['text/csv','text/plain','application/vnd.ms-excel'], 'jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'],
 'png'=>['image/png'], 'webp'=>['image/webp']
];
$uploads=[]; $files=$_FILES['attachments'] ?? null;
if ($files && is_array($files['name'])) {
 if (count($files['name'])>5) fail('Maximum 5 files.');
 $finfo=new finfo(FILEINFO_MIME_TYPE);
 foreach ($files['name'] as $i=>$original) {
  $error=(int)$files['error'][$i]; if ($error===UPLOAD_ERR_NO_FILE) continue; if ($error!==UPLOAD_ERR_OK) { $uploadErrors=[UPLOAD_ERR_INI_SIZE=>'The file exceeds the server upload limit.',UPLOAD_ERR_FORM_SIZE=>'The file exceeds the form upload limit.',UPLOAD_ERR_PARTIAL=>'The file was uploaded only partially.',UPLOAD_ERR_NO_TMP_DIR=>'The server temporary upload directory is unavailable.',UPLOAD_ERR_CANT_WRITE=>'The server could not write the uploaded file.',UPLOAD_ERR_EXTENSION=>'The upload was stopped by a server extension.']; fail(($uploadErrors[$error] ?? 'File upload failed.').' (code '.$error.')'); }
  $size=(int)$files['size'][$i]; if ($size<1 || $size>10*1024*1024) fail('Each file must be 10 MB or smaller.');
  $ext=strtolower(pathinfo((string)$original,PATHINFO_EXTENSION)); $tmp=(string)$files['tmp_name'][$i]; $mime=$finfo->file($tmp) ?: '';
  if (!isset($allowed[$ext]) || !in_array($mime,$allowed[$ext],true)) fail('Unsupported or mismatched file format.');
  $uploads[]=['original'=>mb_substr(basename((string)$original),0,240),'tmp'=>$tmp,'ext'=>$ext,'mime'=>$mime,'size'=>$size];
 }
}

$dir=dirname(__DIR__).'/private/uploads'; if (!is_dir($dir) && !mkdir($dir,0750,true)) fail('Upload storage is unavailable.',500);
$activationToken=bin2hex(random_bytes(24)); $activationHash=hash('sha256',$activationToken); $activationExpires=date('Y-m-d H:i:s',time()+7*86400);
$pdo=db(); $stored=[];
try {
 $pdo->beginTransaction();
 $stmt=$pdo->prepare('INSERT INTO registrations (country,other_country,role,other_role,name,company,email,details,language,consent_at,activation_token_hash,activation_expires_at,portal_status,user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
 $stmt->execute([$country?:null,$country==='Other'?$otherCountry:null,$role?:null,$role==='other'?$otherRole:null,$name?:null,$company?:null,$email,$details,$language,value('consent')==='1'?date('Y-m-d H:i:s'):null,$linkedUserId?null:$activationHash,$linkedUserId?null:$activationExpires,'received',$linkedUserId]);
 $registrationId=(int)$pdo->lastInsertId();
 $fileStmt=$pdo->prepare('INSERT INTO registration_files (registration_id,original_name,stored_name,mime_type,file_size) VALUES (?,?,?,?,?)');
 foreach ($uploads as $file) { $storedName=bin2hex(random_bytes(16)).'.'.$file['ext']; $destination=$dir.'/'.$storedName; if (!move_uploaded_file($file['tmp'],$destination)) throw new RuntimeException('Could not store upload.'); $stored[]=$destination; $fileStmt->execute([$registrationId,$file['original'],$storedName,$file['mime'],$file['size']]); }
 $pdo->commit();
 $subject='New SPECTECHNOLOGY network registration';
 $message="Registration #{$registrationId}\nCountry: {$country}".($otherCountry!==''?" ({$otherCountry})":'')."\nRole: {$role}".($otherRole!==''?" ({$otherRole})":'')."\nName: {$name}\nCompany: {$company}\nEmail: {$email}\nFiles: ".count($stored)."\n\n{$details}";
 $officeMailQueued=@mail('office@spectechnology.pl',$subject,$message,"From: office@spectechnology.pl\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8",'-f office@spectechnology.pl');
 $activationUrl='https://spectechnology.pl/activate.php?token='.rawurlencode($activationToken);
 $userSubject=$language==='pl'?'SPECTECHNOLOGY — otrzymaliśmy Twoje zgłoszenie':'SPECTECHNOLOGY — we received your application';
 $accountUrl='https://spectechnology.pl/dashboard.php'; $userMessage=$linkedUserId?($language==='pl'?"Dziękujemy. Twoje zgłoszenie #{$registrationId} zostało otrzymane i jest już widoczne na Twoim koncie:\n{$accountUrl}":"Thank you. Application #{$registrationId} was received and is already visible in your account:\n{$accountUrl}"):($language==='pl'?"Dziękujemy. Twoje zgłoszenie #{$registrationId} zostało otrzymane.\n\nJeśli chcesz utworzyć konto i śledzić status, użyj tego jednorazowego linku (ważny 7 dni):\n{$activationUrl}":"Thank you. We received application #{$registrationId}.\n\nTo create an account and track its status, use this one-time link within 7 days:\n{$activationUrl}");
 $userMailQueued=@mail($email,$userSubject,$userMessage,"From: office@spectechnology.pl\r\nReply-To: office@spectechnology.pl\r\nContent-Type: text/plain; charset=UTF-8",'-f office@spectechnology.pl'); if(!$officeMailQueued||!$userMailQueued) error_log('Registration mail was not accepted by local mail transport.');
 echo json_encode(['ok'=>true,'id'=>$registrationId,'mail_queued'=>$userMailQueued]);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); foreach($stored as $path) @unlink($path); error_log($e->getMessage()); fail('Could not save registration.',500); }








