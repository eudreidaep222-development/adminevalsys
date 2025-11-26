<?php
session_start();
require_once '../../1database_conn/connection.php';
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

$deptId = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;

// 🔹 Employees
$empSql = "SELECT COUNT(*) as total FROM users WHERE role IN ('Faculty','Administrator')";
if ($deptId) $empSql .= " AND department_id = $deptId";
$employees = $conn->query($empSql)->fetch_assoc()['total'] ?? 0;

// 🔹 Completed Evaluations (from assignments)
$completedSql = "
    SELECT COUNT(*) as total 
    FROM evaluation_assignments ea
    JOIN users u ON ea.evaluatee_id = u.id
    WHERE ea.status = 'completed'
";
if ($deptId) $completedSql .= " AND u.department_id = $deptId";
$completed = $conn->query($completedSql)->fetch_assoc()['total'] ?? 0;

// 🔹 Pending Evaluations (from assignments)
$pendingSql = "
    SELECT COUNT(*) as total 
    FROM evaluation_assignments ea
    JOIN users u ON ea.evaluatee_id = u.id
    WHERE ea.status = 'pending'
";
if ($deptId) $pendingSql .= " AND u.department_id = $deptId";
$pending = $conn->query($pendingSql)->fetch_assoc()['total'] ?? 0;

// 🔹 Average Performance (still from results)
$avgSql = "
    SELECT AVG(a) as avg_score 
    FROM evaluation_results er
    JOIN users u ON er.admin_id = u.id
";
if ($deptId) $avgSql .= " WHERE u.department_id = $deptId";
$avgScore = round($conn->query($avgSql)->fetch_assoc()['avg_score'] ?? 0, 2);

// 🔹 Average Performance by Department
$avgDeptSql = "
    SELECT d.department_name, ROUND(AVG(er.a),2) as avg_score
    FROM evaluation_results er
    JOIN users u ON er.admin_id = u.id
    JOIN departments d ON u.department_id = d.id
";
if ($deptId) $avgDeptSql .= " WHERE d.id = $deptId";
$avgDeptSql .= " GROUP BY d.department_name";

$avgDeptResult = $conn->query($avgDeptSql);
$avgPerformance = [];
$avgDeptLabels = [];
while ($row = $avgDeptResult->fetch_assoc()) {
    $avgDeptLabels[] = $row['department_name'];
    $avgPerformance[] = $row['avg_score'];
}

// 🔹 Performance Trends
$trendSql = "
    SELECT al.school_year, ROUND(AVG(er.a),2) as avg_score
    FROM evaluation_results er
    JOIN academic_list al ON er.academic_id = al.id
";
if ($deptId) $trendSql .= " JOIN users u ON er.admin_id = u.id WHERE u.department_id = $deptId";
$trendSql .= " GROUP BY al.school_year ORDER BY al.start_date ASC";

$trendResult = $conn->query($trendSql);
$trendLabels = [];
$trendScores = [];
while ($row = $trendResult->fetch_assoc()) {
    $trendLabels[] = $row['school_year'];
    $trendScores[] = $row['avg_score'];
}

// ✅ Single response
$response = [
    'employees' => $employees,
    'completed' => $completed,
    'pending' => $pending,
    'avg_score' => $avgScore,
    // 🔹 Now both charts use assignments
    'completion_rate' => [$completed, $pending],
    'completed_vs_pending' => [$completed, $pending],
    'avg_performance' => $avgPerformance,
    'avg_performance_labels' => $avgDeptLabels,
    'performance_trends' => $trendScores,
    'performance_trends_labels' => $trendLabels
];

header('Content-Type: application/json');
echo json_encode($response);
?>
