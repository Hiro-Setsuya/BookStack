<?php
session_start();
require_once '../config/db.php';
require_once '../includes/form-input.php';

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

$title = 'Admin Portal';
ob_start();
renderFloatingInputStyles();
$floatingStyles = ob_get_clean();
$extraStyles = '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />' . "\n" . $floatingStyles . '
<style>
    .btn-toggle-pw {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      padding: 8px;
      cursor: pointer;
      color: #6c757d;
      transition: all 0.2s ease;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10;
    }

    .btn-toggle-pw:hover {
      background: #f8f9fa;
      color: #1fd26a;
    }

    .btn-toggle-pw:active {
      transform: translateY(-50%) scale(0.95);
    }

    .btn-toggle-pw .material-symbols-outlined {
      font-size: 20px;
      user-select: none;
    }
  </style>';
include '../includes/head.php';
?>

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

          <?php
          $error_message = !empty($error_message) ? $error_message : '';
          include '../includes/notification.php';
          ?>

          <form action="login.php" method="POST">
            <?php renderFloatingInput([
              'type' => 'text',
              'name' => 'user',
              'id' => 'user',
              'label' => 'Email or Username',
              'placeholder' => 'admin@example.com',
              'value' => $_POST['user'] ?? '',
              'required' => true,
              'autocomplete' => 'username'
            ]); ?>

            <div class="position-relative mb-4">
              <?php renderFloatingInput([
                'type' => 'password',
                'name' => 'pass',
                'id' => 'password',
                'label' => 'Password',
                'placeholder' => 'Enter your password',
                'required' => true,
                'autocomplete' => 'current-password',
                'class' => 'mb-0',
                'attributes' => ['style' => 'padding-right: 45px;']
              ]); ?>
              <button type="button" onclick="togglePassword()" class="btn-toggle-pw">
                <span class="material-symbols-outlined" id="eyeIcon">visibility</span>
              </button>
            </div>

            <button type="submit" class="btn btn-green w-100 py-3 fw-bold shadow-sm rounded-3">Log In</button>
          </form>
        </div>
      </div>

    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const eyeIcon = document.getElementById('eyeIcon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.textContent = 'visibility_off';
      } else {
        passwordInput.type = 'password';
        eyeIcon.textContent = 'visibility';
      }
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>