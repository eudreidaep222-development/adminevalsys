<?php
session_start();
// ❌ Check if user is logged in (after OTP verification)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ✅ Get role safely
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loading Please Wait</title>
  <link rel="icon" type="image/png" href="../assets/img/IMG-0010.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel="stylesheet" href="assets/css/log-in-loading_style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <center><h1 style="padding-bottom: 550px;">Logging In...</h1></center>
    <div class="container">
        <div class="item item-1"></div>
        <div class="item item-2"></div>
        <div class="item item-3"></div>
        <div class="item item-4"></div>
    </div>

<script>
setTimeout(function(){
  switch ("<?= htmlspecialchars($role) ?>") {
    case 'Super Admin':
        window.location.replace('/SuperAdmin/Dashboard/home.php');
        break;
    case 'Administrator':
        window.location.replace('/Admin/Dashboard/home.php');
        break;
    case 'Faculty':
        window.location.replace('/Faculty/Dashboard/home.php');
        break;
    default:
        window.location.replace('index.php');
        break;
  }
}, 1500);
</script>
</body>
</html>
