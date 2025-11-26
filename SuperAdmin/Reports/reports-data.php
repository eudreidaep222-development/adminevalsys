<?php
require '../../1database_conn/connection.php';
header('Content-Type: application/json');

// 🔹 Disable warnings to avoid breaking JSON
error_reporting(0);

// 🔹 Get filters
$deptId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? intval($_GET['department_id']) : null;
$semesterId = isset($_GET['semesterFilter']) && $_GET['semesterFilter'] !== '' ? intval($_GET['semesterFilter']) : null;
$formId = isset($_GET['formFilter']) && $_GET['formFilter'] !== '' ? intval($_GET['formFilter']) : null;

// 🔹 Helper query function
function runQuery($conn, $sql){
    $result = $conn->query($sql);
    if(!$result) die(json_encode(['error'=>$conn->error,'sql'=>$sql]));
    return $result;
}

// 🔹 Total Completed Evaluations
$totalSql = "SELECT COUNT(*) AS total FROM evaluation_assignments WHERE status='completed'";
if($deptId) $totalSql .= " AND department_id=$deptId";
if($semesterId) $totalSql .= " AND academic_id=$semesterId";
if($formId) $totalSql .= " AND form_id=$formId";
$totalCompleted = intval(runQuery($conn, $totalSql)->fetch_assoc()['total'] ?? 0);

// 🔹 Average Performance
$avgSql = "SELECT ROUND(AVG(er.a),2) AS avg_score
           FROM evaluation_results er
           JOIN evaluation_assignments ea ON er.assignment_id=ea.id
           WHERE 1=1";
if($deptId) $avgSql .= " AND ea.department_id=$deptId";
if($semesterId) $avgSql .= " AND ea.academic_id=$semesterId";
if($formId) $avgSql .= " AND ea.form_id=$formId";
$avgScore = floatval(runQuery($conn, $avgSql)->fetch_assoc()['avg_score'] ?? 0);

// 🔹 Departments Covered
$deptCountSql = "SELECT COUNT(DISTINCT department_id) AS dept_count FROM evaluation_assignments WHERE 1=1";
if($deptId) $deptCountSql .= " AND department_id=$deptId";
if($semesterId) $deptCountSql .= " AND academic_id=$semesterId";
if($formId) $deptCountSql .= " AND form_id=$formId";
$deptCount = intval(runQuery($conn, $deptCountSql)->fetch_assoc()['dept_count'] ?? 0);

// 🔹 Department-wise Avg Scores
$deptChartSql = "SELECT d.department_name, ROUND(AVG(er.a),2) AS avg_score
                 FROM evaluation_results er
                 JOIN evaluation_assignments ea ON er.assignment_id=ea.id
                 JOIN departments d ON ea.department_id=d.id
                 WHERE 1=1";
if($deptId) $deptChartSql .= " AND d.id=$deptId";
if($semesterId) $deptChartSql .= " AND ea.academic_id=$semesterId";
if($formId) $deptChartSql .= " AND ea.form_id=$formId";
$deptChartSql .= " GROUP BY d.id ORDER BY d.department_name ASC";
$deptResult = runQuery($conn, $deptChartSql);
$deptLabels = $deptScores = [];
while($row=$deptResult->fetch_assoc()){
    $deptLabels[] = $row['department_name'];
    $deptScores[] = floatval($row['avg_score']);
}

// 🔹 Evaluation Trends Over Semesters
$semesters = [];
$semResult = runQuery($conn,"SELECT id, school_year, semester FROM academic_list ORDER BY start_date ASC");
while($s=$semResult->fetch_assoc()){
    $semesters[$s['id']] = ['label'=>'S.Y. '.$s['school_year'].' • Sem '.$s['semester'],'score'=>0];
}

$trendSql = "SELECT ea.academic_id, ROUND(AVG(er.a),2) AS avg_score
             FROM evaluation_results er
             JOIN evaluation_assignments ea ON er.assignment_id=ea.id
             WHERE 1=1";
if($deptId) $trendSql .= " AND ea.department_id=$deptId";
if($formId) $trendSql .= " AND ea.form_id=$formId";
$trendSql .= " GROUP BY ea.academic_id";
$trendResult = runQuery($conn, $trendSql);
while($row=$trendResult->fetch_assoc()){
    $acadId = intval($row['academic_id']);
    if(isset($semesters[$acadId])) $semesters[$acadId]['score'] = floatval($row['avg_score']);
}
$trendLabels = $trendScores = [];
foreach($semesters as $s){
    $trendLabels[] = $s['label'];
    $trendScores[] = $s['score'];
}

// 🔹 Detailed Evaluations
$detailsSql = "SELECT ea.id AS assignment_id,
               eva.fullName AS evaluator,
               evd.fullName AS evaluatee,
               d.department_name,
               f.form_name,
               ea.assigned_at,
               ea.status
               FROM evaluation_assignments ea
               JOIN users eva ON ea.evaluator_id=eva.id
               JOIN users evd ON ea.evaluatee_id=evd.id
               JOIN departments d ON ea.department_id=d.id
               JOIN evaluation_forms f ON ea.form_id=f.id
               WHERE ea.status='completed'"; // <-- Only completed
if($deptId) $detailsSql .= " AND ea.department_id=$deptId";
if($semesterId) $detailsSql .= " AND ea.academic_id=$semesterId";
if($formId) $detailsSql .= " AND ea.form_id=$formId";
$detailsSql .= " ORDER BY ea.assigned_at DESC";

$detailsResult = runQuery($conn, $detailsSql);
$details = [];
while($row=$detailsResult->fetch_assoc()){
    $details[] = [
        'assignment_id'=>$row['assignment_id'],
        'evaluator'=>$row['evaluator'],
        'evaluatee'=>$row['evaluatee'],
        'department'=>$row['department_name'],
        'form'=>$row['form_name'],
        'assigned_at'=>$row['assigned_at'],
        'actions'=>'<button class="btn btn-sm btn-primary" onclick="viewPDF('.$row['assignment_id'].')">PDF</button>
                    <button class="btn btn-sm btn-info" onclick="overviewForm('.$row['assignment_id'].')">Overview</button>'
    ];
}

// 🔹 Return JSON
echo json_encode([
    'totalCompleted'=>$totalCompleted,
    'avgScore'=>$avgScore,
    'deptCount'=>$deptCount,
    'deptLabels'=>$deptLabels,
    'deptScores'=>$deptScores,
    'trendLabels'=>$trendLabels,
    'trendScores'=>$trendScores,
    'details'=>$details
]);
exit;
?>
