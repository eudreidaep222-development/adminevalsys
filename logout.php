<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logging Out</title>
  <link rel="icon" type="image/png" href="../assets/img/IMG-0010.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel="stylesheet" href="assets/css/log-in-loading_style.css">
</head>
<body>

  <center><h1 style="padding-bottom: 550px;">Logging Out...</h1></center>
    <div class="container">
      <div class="item item-1"></div>
      <div class="item item-2"></div>
      <div class="item item-3"></div>
      <div class="item item-4"></div>
    </div>

    <?php
    session_start();
    // $connection = mysqli_connect("localhost", "root", "", "inventory204");

    // if (!$connection) {
    //     die("Connection failed: " . mysqli_connect_error());
    // }

    // // Log the logout action in the audit trail
    // if(isset($_SESSION['name'])) {
    //     $Handler = $_SESSION['name'];
    //     $action = "$Handler Logged out";
    //     $auditQuery = "INSERT INTO `AuditTrail`(`datetime`, `username`, `actionmade`) 
    //                   VALUES (NOW(), '$Handler', '$action')";
    //     mysqli_query($connection, $auditQuery);
    // }

    // Destroy the session
    session_destroy();
    ?>

    <script>
      setTimeout(function(){
        // Redirect the user to the login/signup page after 3 seconds
        window.location.replace('index.php');
      }, 1500); // 3 seconds delay
    </script>
  </body>
</html>
