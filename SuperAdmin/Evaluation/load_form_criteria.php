<?php
require_once '../../1database_conn/connection.php';

$form_id = $_GET['form_id'] ?? null;

if(!$form_id){
    echo "<div class='alert alert-warning'>No form selected.</div>";
    exit;
}

// Load categories
$stmt = $conn->prepare("SELECT id, category_name, category_percentage 
                        FROM evaluation_categories 
                        WHERE form_id = ?");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$categories = $stmt->get_result();

if($categories->num_rows === 0){
    echo "<div class='alert alert-info'>No categories found for this form.</div>";
    exit;
}

while($cat = $categories->fetch_assoc()):
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-light'><strong>".htmlspecialchars($cat['category_name'])." (".$cat['category_percentage']."%)</strong></div>";
    echo "<div class='card-body p-0'>";
    echo "<table class='table table-bordered mb-0'>";
    echo "<thead><tr>
            <th>Output</th>
            <th>Success Indicator</th>
            <th>Accomplishment</th>
            <th>Q</th>
            <th>E</th>
            <th>T</th>
            <th>A</th>
          </tr></thead><tbody>";

    // Load criteria for this category
    $stmt2 = $conn->prepare("SELECT output_name, success_indicator, accomplishment, q_rating, e_rating, t_rating, a_rating 
                              FROM evaluation_form_details 
                              WHERE form_id = ? AND category_id = ?");
    $stmt2->bind_param("ii", $form_id, $cat['id']);
    $stmt2->execute();
    $criteria = $stmt2->get_result();

    while($c = $criteria->fetch_assoc()){
        echo "<tr>
                <td><textarea class='form-control' readonly>".htmlspecialchars($c['output_name'])."</textarea></td>
                <td><textarea class='form-control' readonly>".htmlspecialchars($c['success_indicator'])."</textarea></td>
                <td><textarea class='form-control' readonly>".htmlspecialchars($c['accomplishment'])."</textarea></td>
                <td><input type='number' class='form-control q-input' value='".(int)$c['q_rating']."' min='0' max='5' step='1'></td>
                <td><input type='number' class='form-control e-input' value='".(int)$c['e_rating']."' min='0' max='5' step='1'></td>
                <td><input type='number' class='form-control t-input' value='".(int)$c['t_rating']."' min='0' max='5' step='1'></td>
                <td><input type='number' class='form-control a-input' value='".(int)$c['a_rating']."' min='0' max='5' step='1' readonly></td>
              </tr>";
    }

    echo "</tbody></table>";
    echo "</div></div>";
endwhile;
