<?php
session_start();

// 1. Load Libraries
require('../../1database_conn/connection.php');
require('../../assets/fpdf/fpdf.php');

// Load PHPMailer (Adjust paths if your folder structure is different)
require '../../assets/phpmailer/src/Exception.php';
require '../../assets/phpmailer/src/PHPMailer.php';
require '../../assets/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Invalid Request'];
    header("Location: index.php");
    exit();
}

$admin_id    = $_POST['admin_id'];
$school_year = $_POST['school_year'];
$semester    = $_POST['semester'];

// =========================================================
// STEP 1: VALIDATION (Check Version 1 & 2)
// =========================================================

$version_table = 'form_versions'; 
$checkTable = $conn->query("SHOW TABLES LIKE '$version_table'");
if($checkTable->num_rows == 0) { $version_table = 'versions'; }

$sqlCheck = "SELECT DISTINCT f.version_id 
             FROM evaluation_results r
             JOIN $version_table f ON f.version_id = r.form_id
             JOIN academic_list al ON al.id = r.academic_id
             WHERE r.admin_id = ? AND al.school_year = ? AND al.semester = ?";

$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("iss", $admin_id, $school_year, $semester);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

$found_versions = [];
while ($row = $resCheck->fetch_assoc()) {
    $found_versions[] = $row['version_id'];
}

$has_v1 = in_array(1, $found_versions) || in_array('1', $found_versions); 
$has_v2 = in_array(2, $found_versions) || in_array('2', $found_versions); 

// FAIL: If incomplete, redirect with Warning Toastr
if (!$has_v1 || !$has_v2) {
    $_SESSION['toastr'] = [
        'type' => 'warning', 
        'message' => 'Evaluation Incomplete. Cannot send email. Missing Version 1 or 2.'
    ];
    header("Location: index.php");
    exit();
}

// =========================================================
// STEP 2: FETCH DATA (Same as generate_pdf.php)
// =========================================================

// User Info
$sqlUser = "SELECT u.email, u.first_name, u.last_name, u.middle_name, d.department_name, p.position_name 
            FROM users u 
            JOIN departments d ON d.id = u.department_id
            LEFT JOIN positions p ON p.id = u.position_id 
            WHERE u.id = ?";
$stmt = $conn->prepare($sqlUser);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || empty($user['email'])) {
    $_SESSION['toastr'] = ['type' => 'error', 'message' => 'User email not found.'];
    header("Location: index.php");
    exit();
}

$recipient_email = $user['email'];
$middle     = (!empty($user['middle_name'])) ? $user['middle_name'][0] . '.' : '';
$admin_name = strtoupper($user['first_name'] . ' ' . $middle . ' ' . $user['last_name']);
$position   = $user['position_name'] ?? 'Administrator'; 
$date_gen   = date("F j, Y");

// Scores
$criteria_data = [];
$final_avg = 0;

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

if ($res) {
    $criteria_data[] = ['text' => 'Quality (Q)', 'mean' => number_format($res['mean_q'], 2), 'sd' => number_format($res['sd_q'], 2)];
    $criteria_data[] = ['text' => 'Efficiency (E)', 'mean' => number_format($res['mean_e'], 2), 'sd' => number_format($res['sd_e'], 2)];
    $criteria_data[] = ['text' => 'Timeliness (T)', 'mean' => number_format($res['mean_t'], 2), 'sd' => number_format($res['sd_t'], 2)];
    $final_avg = number_format($res['mean_a'], 2);
}

// Map Scores & Adjective
$wp_score = $final_avg; 
$ms_score = $final_avg; 

function getAdjective($s) {
    if ($s >= 4.50) return 'Outstanding';
    if ($s >= 3.50) return 'Very Satisfactory';
    if ($s >= 2.50) return 'Satisfactory';
    if ($s >= 1.50) return 'Unsatisfactory';
    return 'Poor';
}
$adjective = getAdjective($final_avg);

// Comments
$comments_strength = ["- Consistently meets deadlines.", "- Displays strong leadership."]; 
$comments_improve  = ["- Needs to improve communication frequency."]; 

// =========================================================
// STEP 3: GENERATE PDF IN MEMORY
// =========================================================

class PDF extends FPDF {
    function Header() {
        $logoPath = '../../assets/img/logo.png'; 
        if(file_exists($logoPath)) { $this->Image($logoPath, 25, 10, 20); }
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 5, 'YOUR COLLEGE/UNIVERSITY NAME', 0, 1, 'C'); 
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'City, Province, Philippines', 0, 1, 'C'); 
        $this->Ln(5);
        $this->Line(25, 32, 185, 32); 
        $this->Ln(10); 
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
    function Row($data, $widths, $aligns) {
        $nb = 0;
        for($i=0;$i<count($data);$i++) $nb = max($nb, $this->NbLines($widths[$i], $data[$i]));
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for($i=0;$i<count($data);$i++) {
            $w = $widths[$i];
            $a = isset($aligns[$i]) ? $aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h); 
            $this->MultiCell($w, 5, $data[$i], 0, $a); 
            $this->SetXY($x + $w, $y); 
        }
        $this->Ln($h); 
    }
    function CheckPageBreak($h) {
        if($this->GetY()+$h > $this->PageBreakTrigger) $this->AddPage($this->CurOrientation);
    }
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if($c==' ') $sep = $i;
            $l += $cw[$c];
            if($l>$wmax) {
                if($sep==-1) { if($i==$j) $i++; } else $i = $sep+1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

// Generate PDF Layout
$pdf = new PDF();
$pdf->AliasNbPages(); 
$pdf->AddPage();
$pdf->SetMargins(25, 20, 25); 

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 10, $date_gen, 0, 1, 'R');
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, "Dear $admin_name,", 0, 1, 'L');
$pdf->SetFont('Arial', '', 11);
$body = "Please see the results of the Performance Evaluation of Administrator for the $semester Term of AY $school_year. This is in relation to your designation as the $position. Thank you for your service to the College. This is very much appreciated.";
$pdf->MultiCell(0, 5, $body);
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230); 
$pdf->Cell(40, 10, "Work Perf. (50%)", 1, 0, 'C', true);
$pdf->Cell(40, 10, "Mgmt Skills (50%)", 1, 0, 'C', true);
$pdf->Cell(40, 10, "General Average", 1, 0, 'C', true);
$pdf->Cell(40, 10, "Adjective", 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 10, $wp_score, 1, 0, 'C');
$pdf->Cell(40, 10, $ms_score, 1, 0, 'C');
$pdf->Cell(40, 10, $final_avg, 1, 0, 'C');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 10, $adjective, 1, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, "BREAKDOWN OF RATINGS", 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(120, 8, "Category", 1, 0, 'C', true);
$pdf->Cell(20, 8, "Mean", 1, 0, 'C', true);
$pdf->Cell(20, 8, "SD", 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);

if (count($criteria_data) > 0) {
    foreach ($criteria_data as $row) {
        $pdf->Row([$row['text'], $row['mean'], $row['sd']], [120, 20, 20], ['L', 'C', 'C']);
    }
}

$pdf->Ln(15);
if ($pdf->GetY() > 220) $pdf->AddPage();
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, "Prepared:", 0, 1, 'L');
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, "JERICKO DELOS SANTOS, LPT", 0, 1, 'L'); 
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, "Head, Monitoring and Evaluation Unit", 0, 1, 'L');
$pdf->Ln(10);
$pdf->Cell(0, 5, "Endorsed:", 0, 1, 'L');
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, "JERICKSON C. BAUTISTA", 0, 1, 'L'); 
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, "Director, Center for Quality Management, Accreditation and Certification");
$pdf->Ln(10);
$pdf->Cell(0, 5, "Approved:", 0, 1, 'L');
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, "DR. JUANITO O. CABANIAS, LPT", 0, 1, 'L'); 
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, "President", 0, 1, 'L');

$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "COMMENTS", 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, " Areas of Strength:", 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);
$pdf->Ln(2);
foreach ($comments_strength as $comm) $pdf->MultiCell(0, 6, $comm);
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, " Areas for Improvement:", 1, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);
$pdf->Ln(2);
foreach ($comments_improve as $comm) $pdf->MultiCell(0, 6, $comm);

// OUTPUT TO STRING (For Attachment)
$pdfContent = $pdf->Output('S'); 

// =========================================================
// STEP 4: SEND EMAIL VIA PHPMAILER
// =========================================================

$mail = new PHPMailer(true);

try {
    // --- SMTP CONFIGURATION (CHANGE THESE) ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // or your hosting SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_email@gmail.com'; // <--- CHANGE THIS
    $mail->Password   = 'your_app_password';    // <--- CHANGE THIS
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('no-reply@school.edu', 'Evaluation System');
    $mail->addAddress($recipient_email, $admin_name);

    // Attach PDF from Memory string
    $filename = "Evaluation_Report_" . str_replace(' ', '_', $user['last_name']) . ".pdf";
    $mail->addStringAttachment($pdfContent, $filename);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Performance Evaluation Results - ' . $school_year . ' ' . $semester;
    $mail->Body    = "Dear $admin_name,<br><br>Please find attached the results of your Performance Evaluation for $semester of AY $school_year.<br><br>Regards,<br>HR/Evaluation Team";

    $mail->send();

    // UPDATE DATABASE STATUS
    // If you want to mark it as 'sent' in evaluation_archives or results, do it here
    $updateSql = "UPDATE evaluation_archives SET date_sent = NOW() WHERE administrator_id = ? AND school_year = ? AND semester = ?";
    $stmtUpd = $conn->prepare($updateSql);
    $stmtUpd->bind_param("iss", $admin_id, $school_year, $semester);
    $stmtUpd->execute();

    $_SESSION['toastr'] = ['type' => 'success', 'message' => 'Email sent successfully to ' . $recipient_email];

} catch (Exception $e) {
    $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo];
}

header("Location: index.php");
exit();
?>