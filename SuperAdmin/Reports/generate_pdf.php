<?php
// START SESSION
session_start();

// 1. Load Database
require('../../1database_conn/connection.php');

// 2. Load DomPDF (Adjust path if necessary)
require_once '../../assets/dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Invalid Request');

$admin_id    = $_POST['admin_id'];
$school_year = $_POST['school_year'];
$semester    = $_POST['semester'];

// =========================================================
// SECTION A: PRE-CHECK (Validation)
// =========================================================
if (isset($_POST['check_only']) && $_POST['check_only'] === '1') {
    header('Content-Type: application/json');
    
    $version_table = 'form_versions'; 
    $checkTable = $conn->query("SHOW TABLES LIKE '$version_table'");
    if($checkTable->num_rows == 0) { $version_table = 'versions'; }

    $sqlCheck = "SELECT DISTINCT f.version_id 
                 FROM evaluation_results r
                 JOIN $version_table f ON f.version_id = r.form_id
                 JOIN academic_list al ON al.id = r.academic_id
                 WHERE r.admin_id = ? AND al.school_year = ? AND al.semester = ?";
    
    $stmtCheck = $conn->prepare($sqlCheck);
    if (!$stmtCheck) { echo json_encode(['status'=>'error', 'message'=>"Table Error"]); exit; }
    
    $stmtCheck->bind_param("iss", $admin_id, $school_year, $semester);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    $found_versions = [];
    while ($row = $resCheck->fetch_assoc()) { $found_versions[] = $row['version_id']; }

    $has_v1 = in_array(1, $found_versions) || in_array('1', $found_versions); 
    $has_v2 = in_array(2, $found_versions) || in_array('2', $found_versions); 

    if (!$has_v1 || !$has_v2) {
        echo json_encode(['status' => 'error', 'message' => "Missing Data: Both Form V1 and V2 required."]);
    } else {
        echo json_encode(['status' => 'success']);
    }
    exit;
}

// =========================================================
// SECTION B: DATA FETCHING
// =========================================================

// 1. User Info
$sqlUser = "SELECT u.first_name, u.last_name, u.middle_name, d.department_name, p.position_name 
            FROM users u 
            JOIN departments d ON d.id = u.department_id
            LEFT JOIN positions p ON p.id = u.position_id 
            WHERE u.id = ?";
$stmt = $conn->prepare($sqlUser);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("User not found.");

$middle     = (!empty($user['middle_name'])) ? $user['middle_name'][0] . '.' : '';
$admin_name = strtoupper($user['first_name'] . ' ' . $middle . ' ' . $user['last_name']);
$position   = $user['position_name'] ?? 'Administrator'; 
$date_gen   = date("F j, Y");

// 2. Scores (Q/E/T/A)
$sqlScores = "SELECT 
                AVG(r.q) as mean_q, STDDEV(r.q) as sd_q,
                AVG(r.e) as mean_e, STDDEV(r.e) as sd_e,
                AVG(r.t) as mean_t, STDDEV(r.t) as sd_t,
                AVG(r.a) as mean_a
              FROM evaluation_results r
              JOIN academic_list al ON al.id = r.academic_id
              WHERE r.admin_id = ? AND al.school_year = ? AND al.semester = ?";

$stmtScore = $conn->prepare($sqlScores);
$stmtScore->bind_param("iss", $admin_id, $school_year, $semester);
$stmtScore->execute();
$res = $stmtScore->get_result()->fetch_assoc();

// Prepare Data Variables
$wp_score = number_format($res['mean_a'], 2); // Placeholder logic
$ms_score = number_format($res['mean_a'], 2);
$final_avg = number_format($res['mean_a'], 2);

function getAdjective($s) {
    if ($s >= 4.50) return 'Outstanding (O)';
    if ($s >= 3.50) return 'Very Satisfactory (VS)';
    if ($s >= 2.50) return 'Satisfactory (S)';
    if ($s >= 1.50) return 'Unsatisfactory (US)';
    return 'Poor (P)';
}
$adjective = getAdjective($final_avg);

// Criteria Data (Mapping Q/E/T to rows)
$criteria_rows = "";
$criteria_rows .= "<tr><td>Quality of Work</td><td align='center'>".number_format($res['mean_q'], 2)."</td><td align='center'>".number_format($res['sd_q'], 2)."</td></tr>";
$criteria_rows .= "<tr><td>Efficiency</td><td align='center'>".number_format($res['mean_e'], 2)."</td><td align='center'>".number_format($res['sd_e'], 2)."</td></tr>";
$criteria_rows .= "<tr><td>Timeliness</td><td align='center'>".number_format($res['mean_t'], 2)."</td><td align='center'>".number_format($res['sd_t'], 2)."</td></tr>";

// Comments (Placeholder or Fetch from DB)
$strength_html = "<ul><li>Consistently meets deadlines [cite: 18]</li><li>Displays strong leadership</li></ul>";
$improve_html  = "<ul><li>Needs to improve communication frequency [cite: 19]</li></ul>";

// =========================================================
// SECTION C: HTML TEMPLATE (THE DESIGN)
// =========================================================
// This HTML block mimics your Word Doc layout.
// You can edit this HTML anytime to change the look!

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { width: 60px; vertical-align: middle; }
        .header-text { display: inline-block; vertical-align: middle; margin-left: 10px; }
        .date { text-align: right; margin-bottom: 15px; }
        .title { font-weight: bold; text-align: center; margin-bottom: 20px; }
        p { line-height: 1.5; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        
        /* Signatures */
        .signatures { width: 100%; border: none; margin-top: 40px; }
        .signatures td { border: none; padding: 20px 0; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #000; width: 80%; display: inline-block; margin-top: 40px; }
        .sig-name { font-weight: bold; text-transform: uppercase; margin-top: 5px; }
        .sig-title { font-style: italic; font-size: 11px; }
        
        /* Comments */
        .section-title { font-weight: bold; margin-top: 20px; background-color: #ddd; padding: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <img src="'. $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png">
        <div class="header-text">
            <strong>YOUR COLLEGE NAME</strong><br>
            City, Province, Philippines<br>
            Tel: (02) 1234-5678
        </div>
        <hr>
    </div>

    <div class="date">' . $date_gen . '</div>

    <p><strong>Dear ' . $admin_name . ',</strong></p>

    <p>Please see the results of the Performance Evaluation of Administrator for the <strong>' . $semester . ' Term of AY ' . $school_year . '</strong>. This is in relation to your designation as the <strong>' . $position . '</strong>. Thank you for your service to the College. This is very much appreciated.</p>

    <table>
        <thead>
            <tr>
                <th>Work Performance (50%)</th>
                <th>Management Skills (50%)</th>
                <th>General Average</th>
                <th>Adjective</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center">' . $wp_score . '</td>
                <td align="center">' . $ms_score . '</td>
                <td align="center"><strong>' . $final_avg . '</strong></td>
                <td align="center">' . $adjective . '</td>
            </tr>
        </tbody>
    </table>

    <div style="text-align:center; font-weight:bold; margin-bottom:5px;">MANAGEMENT AND LEADERSHIP SKILLS</div>
    <table>
        <thead>
            <tr>
                <th width="60%">Criteria</th>
                <th width="20%">Mean</th>
                <th width="20%">SD</th>
            </tr>
        </thead>
        <tbody>
            ' . $criteria_rows . '
            <tr>
                <td align="right"><strong>General Average:</strong></td>
                <td colspan="2" align="center"><strong>' . $final_avg . '</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td width="33%">
                Prepared:<br><br><br>
                <span class="sig-name">JERICKO DELOS SANTOS, LPT</span><br>
                <span class="sig-title">Head, Monitoring & Evaluation</span>
            </td>
            <td width="33%">
                Endorsed:<br><br><br>
                <span class="sig-name">JERICKSON C. BAUTISTA</span><br>
                <span class="sig-title">Director, CQMAC</span>
            </td>
            <td width="33%">
                Approved:<br><br><br>
                <span class="sig-name">DR. JUANITO O. CABANIAS, LPT</span><br>
                <span class="sig-title">President</span>
            </td>
        </tr>
    </table>

    <div style="page-break-before: always;"></div>

    <h3 style="text-align:center;">COMMENTS</h3>

    <div class="section-title">Areas of Strength:</div>
    ' . $strength_html . '

    <div class="section-title">Areas for Improvement:</div>
    ' . $improve_html . '

</body>
</html>';

// =========================================================
// SECTION D: RENDER PDF
// =========================================================

// Configure DomPDF
$options = new Options();
$options->set('isRemoteEnabled', true); // Allows loading images from server paths
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output Download
$filename = "Report_" . str_replace(' ', '_', $user['last_name']) . "_" . date("Ymd") . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>