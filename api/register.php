<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$role=(string)($_POST['role']??'');
$name=trim((string)($_POST['name']??''));
$company=trim((string)($_POST['company']??''));
$email=strtolower(trim((string)($_POST['email']??'')));
$details=trim((string)($_POST['details']??''));
$language=in_array($_POST['language']??'', ['en','pl'], true)?(string)$_POST['language']:'en';
$consent=($_POST['consent']??'')==='1';
if(!in_array($role,['client','architect','manufacturer'],true)||$name===''||$details===''||!$consent||!filter_var($email,FILTER_VALIDATE_EMAIL)){http_response_code(422);echo json_encode(['error'=>'Invalid form']);exit;}
try{
    $stmt=db()->prepare("INSERT INTO registrations (role,name,company,email,details,language,consent_at,status,created_at) VALUES (?,?,?,?,?,?,NOW(),'new',NOW())");
    $stmt->execute([$role,$name,$company?:null,$email,$details,$language]);
    http_response_code(201);echo json_encode(['ok'=>true,'id'=>(int)db()->lastInsertId()]);
}catch(PDOException $e){
    if(($e->errorInfo[1]??null)===1062){http_response_code(409);echo json_encode(['error'=>'Already registered']);}
    else{error_log($e->getMessage());http_response_code(500);echo json_encode(['error'=>'Database error']);}
}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);echo json_encode(['error'=>'Service not configured']);}
