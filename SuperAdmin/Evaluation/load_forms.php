<?php
require_once '../../1database_conn/connection.php';
header('Content-Type: application/json; charset=utf-8');

$academic_id = $_GET['academic_id'] ?? null;
if(!$academic_id){
  echo json_encode(['success'=>false,'forms'=>[]]);
  exit;
}

$stmt = $conn->prepare("SELECT id, form_name 
                        FROM evaluation_forms 
                        WHERE academic_id = ? 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $academic_id);
$stmt->execute();
$res = $stmt->get_result();

$forms = [];
while($row = $res->fetch_assoc()){
  $forms[] = $row;
}

echo json_encode(['success'=>true,'forms'=>$forms]);
