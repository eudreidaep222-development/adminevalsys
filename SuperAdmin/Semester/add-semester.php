<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Add Semester</title>

    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">   
</head>

<body>

    <div class="main-wrapper">

        <?php
        include '../../Layout Components/header.php';
        include '../../Layout Components/sidebar.php';
        ?>


        <div class="page-wrapper">
            <div class="content container-fluid">

                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-title">Add Semester</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="semester.php">Semester</a></li>
                                <li class="breadcrumb-item active">Add Semester</li>
                            </ul>
                        </div>
                    </div>
                </div>

<?php include '../../1database_conn/connection.php';
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $qry = $conn->query("SELECT * FROM academic_list WHERE id = $id");
    if ($qry && $qry->num_rows > 0) {
        $data = $qry->fetch_array();
        foreach ($data as $k => $v) {
            $$k = $v;
        }
    }
}
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <form id="semester-form">
                    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                        <h5 class="card-title">Semester Details</h5>
                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label>Year <span class="text-danger">*</span></label>
                            <input type="text" name="school_year" class="form-control" required value="<?php echo isset($school_year) ? $school_year : '' ?>">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label>Semester <span class="text-danger">*</span></label>
                            <input type="number" name="semester" class="form-control" required value="<?php echo isset($semester) ? $semester : '' ?>">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label>Semester Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?php echo isset($start_date) ? $start_date : '' ?>">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label>Semester End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="<?php echo isset($end_date) ? $end_date : '' ?>">
                        </div>
                        <?php if (isset($status)): ?>
                        <div class="col-sm-12 form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Pending</option>
                                <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Started</option>
                                <option value="2" <?php echo isset($status) && $status == 2 ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-sm-12 text-end">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

            </div>
            <?php include '../../Layout Components/footer.php'; ?>
        </div>

    </div>

<script src="../../assets/plugins/moment/moment.min.js"></script>
<script src="../../assets/js/bootstrap-datetimepicker.min.js"></script>
<script src="../../assets/js/alert-toast.js"></script>
<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/feather.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/datatables/datatables.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
  $('#semester-form').submit(function(e){
      e.preventDefault();
      start_load(); // This must be defined in script.js or elsewhere
      const formData = $(this).serialize() + '&action=save_academic';

      $.ajax({
          url: 'ajax.php',
          method: 'POST',
          data: formData,
          success: function(resp){
              if(resp == 1){
                  toastr.success("Semester saved successfully", "Success", {
                      closeButton: true,
                      tapToDismiss: false,
                  });
                  setTimeout(() => location.reload(), 1500);
              } else if(resp == 2){
                  toastr.error("Duplicate semester detected", "Error", {
                      closeButton: true,
                      tapToDismiss: false,
                  });
                  end_load();
              } else {
                  toastr.error("Unexpected error: " + resp, "Error", {
                      closeButton: true,
                      tapToDismiss: false,
                  });
                  end_load();
              }
          },
          error: function(xhr, status, error) {
              toastr.error("AJAX error: " + error, "Error", {
                  closeButton: true,
                  tapToDismiss: false,
              });
              end_load();
          }
      });
  });

 
</script>

</body>
</html>