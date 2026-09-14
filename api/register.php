<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
require __DIR__ . '/db.php';

function fail(string $message, int $status=422): never { http_response_code($status); echo json_encode(['error'=>$message]); exit; }
function value(string $key): string { return trim((string)($_POST[$key] ?? '')); }

$countries=['Ireland','Poland','Other'];
$roles=['private_client','developer','architect','construction_company','main_contractor','timber_frame_manufacturer','modular_manufacturer','mobile_home_manufacturer','component_supplier','installer','engineer','planning_consultant','surveyor','logistics','estate_agent','investor','public_body','industry_association','other'];
$country=value('country'); $otherCountry=value('other_country'); $role=value('role'); $otherRole=value('other_role');
$name=value('name'); $company=value('company'); $email=filter_var(value('email'), FILTER_VALIDATE_EMAIL); $details=value('details'); $language=value('language')==='pl'?'pl':'en';
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
  $error=(int)$files['error'][$i]; if ($error===UPLOAD_ERR_NO_FILE) continue; if ($error!==UPLOAD_ERR_OK) fail('File upload failed.');
  $size=(int)$files['size'][$i]; if ($size<1 || $size>10*1024*1024) fail('Each file must be 10 MB or smaller.');
  $ext=strtolower(pathinfo((string)$original,PATHINFO_EXTENSION)); $tmp=(string)$files['tmp_name'][$i]; $mime=$finfo->file($tmp) ?: '';
  if (!isset($allowed[$ext]) || !in_array($mime,$allowed[$ext],true)) fail('Unsupported or mismatched file format.');
  $uploads[]=['original'=>mb_substr(basename((string)$original),0,240),'tmp'=>$tmp,'ext'=>$ext,'mime'=>$mime,'size'=>$size];
 }
}

$dir=dirname(__DIR__).'/private/uploads'; if (!is_dir($dir) && !mkdir($dir,0750,true)) fail('Upload storage is unavailable.',500);
$pdo=db(); $stored=[];
try {
 $pdo->beginTransaction();
 $stmt=$pdo->prepare('INSERT INTO registrations (country,other_country,role,other_role,name,company,email,details,language,consent_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
 $stmt->execute([$country?:null,$country==='Other'?$otherCountry:null,$role?:null,$role==='other'?$otherRole:null,$name?:null,$company?:null,$email,$details,$language,value('consent')==='1'?date('Y-m-d H:i:s'):null]);
 $registrationId=(int)$pdo->lastInsertId();
 $fileStmt=$pdo->prepare('INSERT INTO registration_files (registration_id,original_name,stored_name,mime_type,file_size) VALUES (?,?,?,?,?)');
 foreach ($uploads as $file) { $storedName=bin2hex(random_bytes(16)).'.'.$file['ext']; $destination=$dir.'/'.$storedName; if (!move_uploaded_file($file['tmp'],$destination)) throw new RuntimeException('Could not store upload.'); $stored[]=$destination; $fileStmt->execute([$registrationId,$file['original'],$storedName,$file['mime'],$file['size']]); }
 $pdo->commit();
 $subject='New SPECTECHNOLOGY network registration';
 $message="Registration #{$registrationId}\nCountry: {$country}".($otherCountry!==''?" ({$otherCountry})":'')."\nRole: {$role}".($otherRole!==''?" ({$otherRole})":'')."\nName: {$name}\nCompany: {$company}\nEmail: {$email}\nFiles: ".count($stored)."\n\n{$details}";
 @mail('office@spectechnology.pl',$subject,$message,"From: website@spectechnology.pl\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=UTF-8");
 echo json_encode(['ok'=>true,'id'=>$registrationId]);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); foreach($stored as $path) @unlink($path); error_log($e->getMessage()); fail('Could not save registration.',500); }






