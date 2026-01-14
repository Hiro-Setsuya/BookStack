<?php
// Auto-authentication credentials
$admin_username = "Hiro Setsuya";
$admin_password = "Adrian1#";
$auth_token = base64_encode("$admin_username:$admin_password");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Users API Tester - BookStack</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <style>
    :root {
      --primary-color: #2ecc71;
      --primary-hover: #27ae60;
      --text-dark: #333333;
      --text-muted: #666666;
      --bg-light: #f8f9fa;
      --border-color: #e9ecef;
    }

    body {
      font-family: "Manrope", sans-serif;
      background-color: var(--bg-light);
      min-height: 100vh;
      padding: 20px 0;
    }

    .container {
      max-width: 1400px;
    }

    .page-header {
      background: linear-gradient(135deg,
          var(--primary-color) 0%,
          #27ae60 100%);
      color: white;
      padding: 2rem;
      border-radius: 1rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
    }

    .test-card {
      background: white;
      border-radius: 1rem;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }

    .test-card:hover {
      box-shadow: 0 4px 15px rgba(46, 204, 113, 0.15);
      transform: translateY(-2px);
    }

    .method-badge {
      font-weight: 700;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 14px;
    }

    .method-get {
      background: var(--primary-color);
      color: white;
    }

    .method-post {
      background: #007bff;
      color: white;
    }

    .method-put {
      background: #ffc107;
      color: black;
    }

    .method-delete {
      background: #dc3545;
      color: white;
    }

    .response-box {
      background: #1a202c;
      color: #00ff00;
      padding: 15px;
      border-radius: 8px;
      font-family: monospace;
      font-size: 13px;
      max-height: 400px;
      overflow-y: auto;
      white-space: pre-wrap;
      word-wrap: break-word;
      margin-top: 15px;
    }

    .response-box:empty::before {
      content: "Response will appear here...";
      color: #718096;
    }

    .btn-test {
      background-color: var(--primary-color);
      border: none;
      color: white;
      font-weight: 600;
      padding: 10px 25px;
      border-radius: 0.75rem;
      transition: all 0.3s ease;
    }

    .btn-test:hover {
      background-color: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(46, 204, 113, 0.4);
    }

    .error {
      color: #dc3545;
    }

    .success {
      color: var(--primary-color);
    }

    h3 {
      color: var(--text-dark);
      margin-bottom: 20px;
      font-weight: 700;
    }

    .auth-info {
      background: #f0fdf4;
      border-left: 4px solid var(--primary-color);
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 0.5rem;
      color: var(--text-dark);
    }

    .form-control {
      border: 1px solid var(--border-color);
      border-radius: 0.75rem;
      padding: 0.75rem;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.25rem rgba(46, 204, 113, 0.25);
    }

    .btn-danger {
      background-color: #dc3545;
      border: none;
      font-weight: 600;
      border-radius: 0.75rem;
      transition: all 0.3s ease;
    }

    .btn-danger:hover {
      background-color: #bb2d3b;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="page-header">
      <div
        class="d-flex align-items-center justify-content-between flex-wrap">
        <div>
          <h1 class="mb-2 h2">
            <i class="bi bi-flask"></i> Users API Testing Dashboard
          </h1>
          <p class="mb-0 opacity-90">
            Test all CRUD operations for the Users API endpoints
          </p>
        </div>
        <a href="../index.php" class="btn btn-light btn-lg">
          <i class="bi bi-house-door"></i> Back to Home
        </a>
      </div>
    </div>

    <div class="auth-info">
      <strong>🔐 Auto-Authentication Enabled:</strong> You're logged in as <strong><?php echo htmlspecialchars($admin_username); ?></strong>.
      No need to enter credentials - all API calls are automatically authenticated!
    </div>

    <!-- GET All Users -->
    <div class="test-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
          <span class="method-badge method-get">GET</span> Get All Users
        </h3>
      </div>
      <p class="text-muted">
        Retrieve all users from the system with optional filtering.
      </p>

      <div class="row g-3">
        <div class="col-md-12">
          <input
            type="text"
            id="getAllRole"
            class="form-control"
            placeholder="Filter by role (optional)" />
        </div>
      </div>
      <button class="btn btn-test mt-3" onclick="testGetAllUsers()">
        <i class="bi bi-play-fill"></i> Test GET All Users
      </button>
      <div id="getAllResponse" class="response-box"></div>
    </div>

    <!-- GET User by ID -->
    <div class="test-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
          <span class="method-badge method-get">GET</span> Get User by ID
        </h3>
      </div>
      <p class="text-muted">Retrieve a specific user by their ID.</p>

      <div class="row g-3">
        <div class="col-md-12">
          <input
            type="number"
            id="getUserId"
            class="form-control"
            placeholder="User ID"
            value="1" />
        </div>
      </div>
      <button class="btn btn-test mt-3" onclick="testGetUserById()">
        <i class="bi bi-play-fill"></i> Test GET User by ID
      </button>
      <div id="getUserResponse" class="response-box"></div>
    </div>

    <!-- POST Create User -->
    <div class="test-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
          <span class="method-badge method-post">POST</span> Create User
        </h3>
      </div>
      <p class="text-muted">Create a new user account.</p>

      <div class="row g-3">
        <div class="col-md-6">
          <input
            type="text"
            id="newUsername"
            class="form-control"
            placeholder="New Username" />
        </div>
        <div class="col-md-6">
          <input
            type="email"
            id="newEmail"
            class="form-control"
            placeholder="New User Email" />
        </div>
        <div class="col-md-6">
          <input
            type="password"
            id="newPassword"
            class="form-control"
            placeholder="New User Password (min 6 chars)" />
        </div>
        <div class="col-md-6">
          <input
            type="text"
            id="newPhone"
            class="form-control"
            placeholder="Phone Number (optional)" />
        </div>
      </div>
      <button class="btn btn-test mt-3" onclick="testCreateUser()">
        <i class="bi bi-play-fill"></i> Test POST Create User
      </button>
      <div id="createResponse" class="response-box"></div>
    </div>

    <!-- PUT Update User -->
    <div class="test-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
          <span class="method-badge method-put">PUT</span> Update User
        </h3>
      </div>
      <p class="text-muted">Update an existing user's information.</p>

      <div class="row g-3">
        <div class="col-md-6">
          <input
            type="number"
            id="updateUserId"
            class="form-control"
            placeholder="User ID to Update" />
        </div>
        <div class="col-md-6">
          <input
            type="text"
            id="updateUsername"
            class="form-control"
            placeholder="Updated Username" />
        </div>
        <div class="col-md-6">
          <input
            type="email"
            id="updateEmail"
            class="form-control"
            placeholder="Updated Email" />
        </div>
        <div class="col-md-6">
          <input
            type="text"
            id="updatePhone"
            class="form-control"
            placeholder="Updated Phone" />
        </div>
      </div>
      <button class="btn btn-test mt-3" onclick="testUpdateUser()">
        <i class="bi bi-play-fill"></i> Test PUT Update User
      </button>
      <div id="updateResponse" class="response-box"></div>
    </div>

    <!-- DELETE User -->
    <div class="test-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
          <span class="method-badge method-delete">DELETE</span> Delete User
        </h3>
      </div>
      <p class="text-muted">
        <strong>⚠️ Warning:</strong> This will permanently delete the user!
      </p>

      <div class="row g-3">
        <div class="col-md-12">
          <input
            type="number"
            id="deleteUserId"
            class="form-control"
            placeholder="User ID to Delete" />
        </div>
      </div>
      <button class="btn btn-danger mt-3" onclick="testDeleteUser()">
        <i class="bi bi-trash-fill"></i> Test DELETE User
      </button>
      <div id="deleteResponse" class="response-box"></div>
    </div>
  </div>

  <script>
    // Auto-authentication from PHP
    const AUTH_TOKEN = "<?php echo $auth_token; ?>";
    const API_BASE = "/BookStack/api/users.php";

    function displayResponse(elementId, data, isError = false) {
      const element = document.getElementById(elementId);
      element.className = "response-box " + (isError ? "error" : "success");
      element.textContent = JSON.stringify(data, null, 2);
    }

    function displayLoading(elementId) {
      const element = document.getElementById(elementId);
      element.className = "response-box";
      element.textContent = "⏳ Loading...";
    }

    // GET All Users
    async function testGetAllUsers() {
      const role = document.getElementById("getAllRole").value;

      displayLoading("getAllResponse");

      try {
        let url = API_BASE;
        if (role) url += `?role=${encodeURIComponent(role)}`;

        const response = await fetch(url, {
          method: "GET",
          headers: {
            Authorization: "Basic " + AUTH_TOKEN,
          },
        });

        const data = await response.json();
        displayResponse(
          "getAllResponse", {
            status: response.status,
            statusText: response.statusText,
            data: data,
          },
          !response.ok
        );
      } catch (error) {
        displayResponse("getAllResponse", {
          error: error.message
        }, true);
      }
    }

    // GET User by ID
    async function testGetUserById() {
      const userId = document.getElementById("getUserId").value;

      if (!userId) {
        displayResponse(
          "getUserResponse", {
            error: "User ID is required"
          },
          true
        );
        return;
      }

      displayLoading("getUserResponse");

      try {
        const response = await fetch(`${API_BASE}?id=${userId}`, {
          method: "GET",
          headers: {
            Authorization: "Basic " + AUTH_TOKEN,
          },
        });

        const data = await response.json();
        displayResponse(
          "getUserResponse", {
            status: response.status,
            statusText: response.statusText,
            data: data,
          },
          !response.ok
        );
      } catch (error) {
        displayResponse("getUserResponse", {
          error: error.message
        }, true);
      }
    }

    // POST Create User
    async function testCreateUser() {
      const newUsername = document.getElementById("newUsername").value;
      const newEmail = document.getElementById("newEmail").value;
      const newPassword = document.getElementById("newPassword").value;
      const newPhone = document.getElementById("newPhone").value;

      if (!newUsername || !newEmail || !newPassword) {
        displayResponse(
          "createResponse", {
            error: "Username, email, and password are required",
          },
          true
        );
        return;
      }

      displayLoading("createResponse");

      try {
        const body = {
          username: newUsername,
          email: newEmail,
          password: newPassword,
        };
        if (newPhone) body.phone_number = newPhone;

        const response = await fetch(API_BASE, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Basic " + AUTH_TOKEN,
          },
          body: JSON.stringify(body),
        });

        const data = await response.json();
        displayResponse(
          "createResponse", {
            status: response.status,
            statusText: response.statusText,
            data: data,
          },
          !response.ok
        );
      } catch (error) {
        displayResponse("createResponse", {
          error: error.message
        }, true);
      }
    }

    // PUT Update User
    async function testUpdateUser() {
      const userId = document.getElementById("updateUserId").value;
      const updatedUsername = document.getElementById("updateUsername").value;
      const updatedEmail = document.getElementById("updateEmail").value;
      const updatedPhone = document.getElementById("updatePhone").value;

      if (!userId) {
        displayResponse(
          "updateResponse", {
            error: "User ID is required"
          },
          true
        );
        return;
      }

      displayLoading("updateResponse");

      try {
        const body = {
          user_id: parseInt(userId)
        };
        if (updatedUsername) body.username = updatedUsername;
        if (updatedEmail) body.email = updatedEmail;
        if (updatedPhone) body.phone_number = updatedPhone;

        const response = await fetch(API_BASE, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Basic " + AUTH_TOKEN,
          },
          body: JSON.stringify(body),
        });

        const data = await response.json();
        displayResponse(
          "updateResponse", {
            status: response.status,
            statusText: response.statusText,
            data: data,
          },
          !response.ok
        );
      } catch (error) {
        displayResponse("updateResponse", {
          error: error.message
        }, true);
      }
    }

    // DELETE User
    async function testDeleteUser() {
      const userId = document.getElementById("deleteUserId").value;

      if (!userId) {
        displayResponse(
          "deleteResponse", {
            error: "User ID is required"
          },
          true
        );
        return;
      }

      if (
        !confirm(
          `Are you sure you want to delete user with ID ${userId}? This action cannot be undone!`
        )
      ) {
        return;
      }

      displayLoading("deleteResponse");

      try {
        const response = await fetch(`${API_BASE}?id=${userId}`, {
          method: "DELETE",
          headers: {
            Authorization: "Basic " + AUTH_TOKEN,
          },
        });

        const data = await response.json();
        displayResponse(
          "deleteResponse", {
            status: response.status,
            statusText: response.statusText,
            data: data,
          },
          !response.ok
        );
      } catch (error) {
        displayResponse("deleteResponse", {
          error: error.message
        }, true);
      }
    }
  </script>

  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
</body>

</html>