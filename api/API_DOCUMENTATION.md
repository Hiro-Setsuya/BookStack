# 📖 BookStack API Documentation

Complete REST API documentation for BookStack E-Book Store platform.

## 📚 Quick Links

- **[Quick Reference Guide](QUICK_REFERENCE.md)** - Cheat sheet for all endpoints
- **[Postman Collection](../BookStack_API.postman_collection.json)** - Import into Postman for testing
- **[Interactive Examples](api-examples.html)** - Test API in your browser

## 🌐 Base URL

```
http://localhost/BookStack/api
```

## 🔐 Authentication

### Authentication Types

1. **Basic Authentication** (Required for User Management)

   - Used for admin-only endpoints
   - Username/Password from the users table with `role = 'admin'`
   - Header: `Authorization: Basic <base64(username:password)>`

2. **Token Authentication** (Login Endpoint)
   - Login endpoint returns a token for session management
   - Not required for public voucher queries

### Admin Authentication Example

```bash
# Using cURL
curl -X GET "http://localhost/BookStack/api/users.php" \
  -u "admin:password123"
```

```javascript
// Using JavaScript Fetch
fetch("http://localhost/BookStack/api/users.php", {
  headers: {
    Authorization: "Basic " + btoa("admin:password123"),
  },
});
```

---

## 📚 API Endpoints

### Authentication

#### POST `/auth.php` - Login

Authenticate user and receive token.

**Request:**

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**

```json
{
  "user_id": 1,
  "username": "john_doe",
  "email": "user@example.com",
  "role": "customer",
  "token": "a1b2c3d4e5f6...",
  "message": "Login successful"
}
```

**Errors:**

- `400` - Missing email or password
- `401` - Invalid credentials

---

## 👥 Users API

**Base Path:** `/users.php`  
**Authentication:** Basic Auth (Admin only)

### GET `/users.php` - Get All Users

Retrieve all users with optional filtering.

**Query Parameters:**

- `role` (optional) - Filter by role: `admin`, `customer`
- `search` (optional) - Search by username or email

**Example:**

```bash
GET /users.php?role=customer&search=john
```

**Response (200):**

```json
[
  {
    "user_id": 1,
    "user_name": "john_doe",
    "email": "john@example.com",
    "phone_number": "+1234567890",
    "role": "customer",
    "is_account_verified": 1,
    "created_at": "2024-01-01 10:00:00",
    "updated_at": "2024-01-01 10:00:00"
  }
]
```

---

### GET `/users.php?id={id}` - Get User by ID

Retrieve a specific user by ID.

**Path Parameters:**

- `id` (required) - User ID

**Example:**

```bash
GET /users.php?id=1
```

**Response (200):**

```json
{
  "user_id": 1,
  "user_name": "john_doe",
  "email": "john@example.com",
  "phone_number": "+1234567890",
  "role": "customer",
  "is_account_verified": 1,
  "password_hash": "$2y$10$...",
  "created_at": "2024-01-01 10:00:00",
  "updated_at": "2024-01-01 10:00:00"
}
```

**Errors:**

- `404` - User not found

---

### POST `/users.php` - Create User

Create a new user account.

**Request Body:**

```json
{
  "username": "jane_smith",
  "email": "jane@example.com",
  "password": "securepass123",
  "phone_number": "+1987654321"
}
```

**Field Validation:**

- `username` - Required, unique
- `email` - Required, unique, valid email format
- `password` - Required, minimum 6 characters
- `phone_number` - Optional

**Response (201):**

```json
{
  "user_id": 2,
  "username": "jane_smith",
  "email": "jane@example.com",
  "message": "User registered successfully"
}
```

**Errors:**

- `400` - Missing required fields or validation errors
- `409` - Username or email already exists

---

### PUT `/users.php` - Update User

Update existing user information.

**Request Body:**

```json
{
  "user_id": 2,
  "username": "jane_updated",
  "email": "jane.new@example.com",
  "password": "newpassword123",
  "phone_number": "+1234567890",
  "role": "customer"
}
```

**Notes:**

- `user_id` is required
- All other fields are optional (only update what's provided)
- Password will be automatically hashed
- Email must be valid if provided

**Response (200):**

```json
{
  "success": true,
  "message": "User updated successfully"
}
```

**Errors:**

- `400` - Invalid input or no fields to update
- `404` - User not found

---

## 🎟️ Vouchers API

**Base Path:** `/voucher.php`  
**Authentication:** Not required for GET, Basic Auth for POST/PUT/DELETE

### GET `/voucher.php` - Get All Vouchers

Retrieve all vouchers with optional filtering.

**Query Parameters:**

- `user_id` (optional) - Filter by user ID
- `external_system` (optional) - Filter by external system
- `is_redeemed` (optional) - Filter by redemption status (0 or 1)
- `available` (optional) - Get only available vouchers (1)
- `search` (optional) - Search by voucher code

**Example:**

```bash
GET /voucher.php?user_id=1&available=1
```

**Response (200):**

```json
[
  {
    "voucher_id": 1,
    "user_id": 1,
    "user_name": "john_doe",
    "email": "john@example.com",
    "external_system": "ebook_store",
    "code": "SAVE20",
    "discount_type": "percentage",
    "discount_amount": 20.0,
    "min_order_amount": 10.0,
    "max_uses": 100,
    "times_used": 5,
    "remaining_uses": 95,
    "is_redeemed": 0,
    "is_expired": false,
    "is_available": true,
    "issued_at": "2024-01-01 10:00:00",
    "expires_at": "2024-12-31 23:59:59",
    "redeemed_at": null
  }
]
```

---

### GET `/voucher.php?id={id}` - Get Voucher by ID

Retrieve a specific voucher by ID.

**Path Parameters:**

- `id` (required) - Voucher ID

**Example:**

```bash
GET /voucher.php?id=1
```

**Response (200):**

```json
{
  "voucher_id": 1,
  "user_id": 1,
  "user_name": "john_doe",
  "email": "john@example.com",
  "code": "SAVE20",
  "discount_type": "percentage",
  "discount_amount": 20.0,
  "min_order_amount": 10.0,
  "max_uses": 100,
  "times_used": 5,
  "remaining_uses": 95,
  "is_redeemed": 0,
  "is_expired": false,
  "is_available": true,
  "issued_at": "2024-01-01 10:00:00",
  "expires_at": "2024-12-31 23:59:59"
}
```

**Errors:**

- `404` - Voucher not found

---

### GET `/voucher.php?code={code}` - Get Voucher by Code

Retrieve a voucher by its code (case-insensitive).

**Path Parameters:**

- `code` (required) - Voucher code

**Example:**

```bash
GET /voucher.php?code=SAVE20
```

**Response:** Same as Get Voucher by ID

**Errors:**

- `404` - Voucher not found

---

### POST `/voucher.php` - Create Voucher

Create a new voucher.

**Request Body:**

```json
{
  "user_id": 1,
  "code": "NEWUSER10",
  "discount_type": "percentage",
  "discount_amount": 10.0,
  "min_order_amount": 5.0,
  "max_uses": 100,
  "expires_at": "2024-12-31 23:59:59",
  "external_system": "ebook_store"
}
```

**Field Validation:**

- `user_id` - Required, must exist in users table
- `code` - Required, unique, automatically converted to uppercase
- `discount_type` - Optional, default: `fixed` (options: `fixed`, `percentage`)
- `discount_amount` - Required, must be > 0
- `min_order_amount` - Optional, default: 0.00
- `max_uses` - Optional, default: 1
- `expires_at` - Optional, datetime format
- `external_system` - Optional, default: `ebook_store`

**Response (201):**

```json
{
  "success": true,
  "message": "Voucher created successfully",
  "voucher": {
    "voucher_id": 5,
    "user_id": 1,
    "code": "NEWUSER10",
    "discount_amount": 10.00,
    ...
  }
}
```

**Errors:**

- `400` - Missing required fields, invalid data, or code already exists
- `404` - User not found

---

### PUT `/voucher.php?id={id}` - Update Voucher

Update an existing voucher.

**Path Parameters:**

- `id` (required) - Voucher ID

**Request Body (all fields optional):**

```json
{
  "discount_amount": 15.0,
  "min_order_amount": 20.0,
  "max_uses": 200,
  "expires_at": "2025-01-31 23:59:59",
  "is_redeemed": 1
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Voucher updated successfully",
  "voucher": {
    "voucher_id": 1,
    "discount_amount": 15.00,
    ...
  }
}
```

**Errors:**

- `400` - No fields to update
- `404` - Voucher not found

---

### DELETE `/voucher.php?id={id}` - Delete Voucher

Permanently delete a voucher.

**Path Parameters:**

- `id` (required) - Voucher ID

**Example:**

```bash
DELETE /voucher.php?id=1
```

**Response (200):**

```json
{
  "success": true,
  "message": "Voucher deleted successfully"
}
```

**Errors:**

- `400` - Missing voucher ID
- `404` - Voucher not found

---

## 📊 Response Format

All API responses follow a consistent JSON format:

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description"
}
```

## 🔢 HTTP Status Codes

| Code | Description                                      |
| ---- | ------------------------------------------------ |
| 200  | OK - Request successful                          |
| 201  | Created - Resource created successfully          |
| 400  | Bad Request - Invalid input or validation error  |
| 401  | Unauthorized - Authentication required or failed |
| 404  | Not Found - Resource not found                   |
| 405  | Method Not Allowed - HTTP method not supported   |
| 409  | Conflict - Resource already exists               |
| 500  | Internal Server Error - Server error             |

---

## 🧪 Testing with Postman

### Import Collection

1. Download [BookStack_API.postman_collection.json](../BookStack_API.postman_collection.json)
2. Open Postman
3. Click **Import** → **Upload Files**
4. Select the collection file
5. Collection will appear in your workspace

### Configure Environment

The collection includes default variables:

- `base_url`: `http://localhost/BookStack/api`
- `admin_username`: `admin`
- `admin_password`: `password123`

Update these in the collection variables if your setup differs.

### Testing Endpoints

1. **Authentication**
   - Test `Login` endpoint first to get a token
2. **User Management**
   - All user endpoints require Basic Auth
   - Update username/password in the Authorization tab
3. **Voucher Management**
   - GET requests don't require authentication
   - POST/PUT/DELETE require Basic Auth

---

## 📝 Code Examples

### JavaScript (Fetch API)

#### Login

```javascript
const login = async () => {
  const response = await fetch("http://localhost/BookStack/api/auth.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      email: "admin@bookstack.com",
      password: "password123",
    }),
  });

  const data = await response.json();
  console.log(data);
};
```

#### Get All Vouchers

```javascript
const getVouchers = async () => {
  const response = await fetch(
    "http://localhost/BookStack/api/voucher.php?available=1"
  );
  const vouchers = await response.json();
  console.log(vouchers);
};
```

#### Create User (with Basic Auth)

```javascript
const createUser = async () => {
  const credentials = btoa("admin:password123");

  const response = await fetch("http://localhost/BookStack/api/users.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Basic ${credentials}`,
    },
    body: JSON.stringify({
      username: "newuser",
      email: "newuser@example.com",
      password: "password123",
    }),
  });

  const data = await response.json();
  console.log(data);
};
```

### PHP (cURL)

#### Get Voucher by Code

```php
<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/BookStack/api/voucher.php?code=SAVE20");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$voucher = json_decode($response, true);
print_r($voucher);
?>
```

#### Update User (with Basic Auth)

```php
<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/BookStack/api/users.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_USERPWD, "admin:password123");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_id' => 2,
    'username' => 'updated_name',
    'email' => 'updated@example.com'
]));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
?>
```

### Python (Requests)

#### Get All Users

```python
import requests
from requests.auth import HTTPBasicAuth

response = requests.get(
    'http://localhost/BookStack/api/users.php',
    auth=HTTPBasicAuth('admin', 'password123')
)

users = response.json()
print(users)
```

#### Create Voucher

```python
import requests

data = {
    'user_id': 1,
    'code': 'PYTHON20',
    'discount_type': 'percentage',
    'discount_amount': 20.00,
    'max_uses': 50
}

response = requests.post(
    'http://localhost/BookStack/api/voucher.php',
    json=data
)

result = response.json()
print(result)
```

---

## 🚨 Common Issues & Solutions

### 1. CORS Errors

**Issue:** Cross-origin requests blocked

**Solution:** API already includes CORS headers. If issues persist, check your browser's console and ensure the API server is running.

### 2. Authentication Failed

**Issue:** 401 Unauthorized response

**Solutions:**

- Verify username/password are correct
- Ensure user has `role = 'admin'` for user endpoints
- Check Basic Auth credentials are base64 encoded correctly

### 3. Voucher Code Already Exists

**Issue:** Cannot create voucher with duplicate code

**Solution:** Voucher codes must be unique. Use a different code or update the existing voucher.

### 4. User Not Found

**Issue:** Cannot create voucher for non-existent user

**Solution:** Ensure the `user_id` exists in the users table before creating vouchers.

---

## 🔒 Security Best Practices

1. **Use HTTPS in Production**

   - Never send credentials over HTTP in production
   - Configure SSL certificate on your server

2. **Strong Passwords**

   - Enforce minimum 8 characters
   - Require mix of letters, numbers, and symbols
   - Consider implementing password complexity rules

3. **Token Management**

   - Implement proper JWT tokens for production
   - Set reasonable expiration times
   - Store tokens securely (httpOnly cookies recommended)

4. **Rate Limiting**

   - Implement rate limiting to prevent abuse
   - Use tools like Cloudflare or nginx rate limiting

5. **Input Validation**

   - API already validates inputs
   - Add additional sanitization for production use
   - Use prepared statements (already implemented)

6. **Admin Access**
   - Restrict admin endpoints to internal networks
   - Implement IP whitelisting for admin operations
   - Use multi-factor authentication for admin accounts

---

## 📞 Support

For issues or questions:

- Check the [main README](../README.md)
- Review code in `/api` directory
- Check database schema in `/sql` directory

---

## 📄 License

Part of the BookStack E-Book Store platform.

---

**Last Updated:** January 14, 2026  
**API Version:** 1.0
