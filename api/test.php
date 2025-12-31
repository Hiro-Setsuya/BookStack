<?php
// URL of your API
$api_url = "http://localhost:8080/BookStack/api/users.php";

// Basic Auth credentials (admin)
$username = "Hiro Setsuya";
$password = "Adrian1#";

// Create a stream context with Basic Auth
$context = stream_context_create([
    "http" => [
        "header" => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Fetch the JSON data from the API
$response = file_get_contents($api_url, false, $context);

// Decode JSON to PHP array
$users = json_decode($response, true);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Users List</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 8px 12px;
            text-align: left;
        }

        th {
            background-color: #eee;
        }
    </style>
</head>

<body>
    <h1 style="text-align:center;">Users List</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Verified</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['user_id']) ?></td>
                    <td><?= htmlspecialchars($user['user_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= $user['is_account_verified'] == 1 ? 'Yes' : 'No' ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td><?= htmlspecialchars($user['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>