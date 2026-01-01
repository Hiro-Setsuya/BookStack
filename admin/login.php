<?php
session_start();
require_once '../config/db.php';

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user']) && isset($_POST['pass'])) {
  $user_input = mysqli_real_escape_string($conn, trim($_POST['user']));
  $password = $_POST['pass'];

  // Query using executeQuery function
  $query = "SELECT user_id, user_name, email, password_hash, role FROM users WHERE (email = '$user_input' OR user_name = '$user_input') AND role = 'admin'";
  $result = executeQuery($query);

  if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    // Verify password (assuming passwords are hashed with password_hash)
    if (password_verify($password, $user['password_hash'])) {
      // Password is correct, set session variables
      $_SESSION['admin_id'] = $user['user_id'];
      $_SESSION['admin_name'] = $user['user_name'];
      $_SESSION['admin_email'] = $user['email'];
      $_SESSION['admin_role'] = $user['role'];

      // Redirect to admin dashboard
      header('Location: dashboard.php');
      exit;
    } else {
      $error_message = 'Invalid email/username or password.';
    }
  } else {
    $error_message = 'Invalid email/username or password, or you do not have admin privileges.';
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Portal - BookStack</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">

  <div class="container-fluid vh-100 p-0">
    <div class="row g-0 h-100">

      <div class="col-lg-5 d-none d-lg-flex align-items-center position-relative p-5 text-white"
        style="background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.85)), url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&q=80&w=1000'); background-size: cover; background-position: center;">
        <div class="p-4">
          <h2 class="fw-bold mb-3"><i class="bi bi-book-half me-2 text-green"></i> E-Book Admin</h2>
          <p class="fs-5 opacity-75">Manage your digital library efficiently. Secure access to student records, inventory, and sales analytics.</p>
          <div class="mt-4 d-flex align-items-center">
            <div class="active-dot rounded-pill me-1" style="height: 4px;"></div>
            <div class="bg-secondary rounded-pill me-1 opacity-50" style="height: 4px; width: 10px;"></div>
            <div class="bg-secondary rounded-pill opacity-50" style="height: 4px; width: 10px;"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-7 d-flex align-items-center justify-content-center bg-white position-relative">

        <a href="../index.php" class="btn btn-outline-dark btn-sm position-absolute top-0 end-0 m-4 shadow-sm fw-semibold">
          <i class="bi bi-archive me-1"></i> Return to Store
        </a>

        <div class="w-100 px-4" style="max-width: 420px;">
          <h2 class="fw-bold mb-1">Admin Portal</h2>
          <p class="text-muted small mb-4">Welcome back! Please enter your details.</p>

          <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <form action="login.php" method="POST">
            <div class="mb-3">
              <label class="form-label small fw-bold">Email or Username</label>
              <div class="input-group border rounded-3 bg-light">
                <input type="text" class="form-control border-0 bg-transparent py-2" placeholder="admin@gmail.com" name="user" required value="<?php echo isset($_POST['user']) ? htmlspecialchars($_POST['user']) : ''; ?>">
                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-person"></i></span>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Password</label>
              <div class="input-group border rounded-3 bg-light">
                <input type="password" class="form-control border-0 bg-transparent py-2" placeholder="••••••••" name="pass" required>
                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-eye-slash"></i></span>
              </div>
            </div>

            <button type="submit" class="btn btn-green w-100 py-2 fw-bold shadow-sm rounded-3">Log in</button>
          </form>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>