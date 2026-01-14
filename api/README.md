# 📖 BookStack API

Welcome to the BookStack API documentation. This folder contains all API endpoints for user and voucher management.

## 📂 What's Inside

### API Endpoints

- **[auth.php](auth.php)** - User authentication and login
- **[users.php](users.php)** - User CRUD operations (admin only)
- **[voucher.php](voucher.php)** - Voucher management
- **[auth-middleware.php](auth-middleware.php)** - Basic auth validation
- **[response.php](response.php)** - JSON response handler

### Documentation

- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Complete API documentation with examples
- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Quick reference cheat sheet
- **[api-examples.html](api-examples.html)** - Interactive browser-based testing tool
- **[../BookStack_API.postman_collection.json](../BookStack_API.postman_collection.json)** - Postman collection

## 🚀 Quick Start

### 1. Start Your Server

```bash
# Start XAMPP Apache and MySQL
```

### 2. Test Authentication

```bash
curl -X POST http://localhost/BookStack/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@bookstack.com","password":"password123"}'
```

### 3. Get Vouchers

```bash
curl http://localhost/BookStack/api/voucher.php?available=1
```

### 4. Interactive Testing

Open [api-examples.html](api-examples.html) in your browser for an interactive testing interface.

## 📚 API Overview

### Authentication API

**Endpoint:** `/auth.php`

- `POST` - Login and receive token
- No authentication required

### Users API

**Endpoint:** `/users.php`  
**Authentication:** Basic Auth (Admin only)

- `GET` - List all users or get by ID
- `POST` - Create new user
- `PUT` - Update existing user

### Vouchers API

**Endpoint:** `/voucher.php`

- `GET` - List vouchers, get by ID or code (no auth required)
- `POST` - Create voucher
- `PUT` - Update voucher
- `DELETE` - Delete voucher

## 🔐 Authentication

### For User Management

All user endpoints require Basic Authentication with admin credentials:

```javascript
Authorization: Basic base64(username:password)
```

### For Voucher Management

- **GET** requests: No authentication required
- **POST/PUT/DELETE**: Basic Authentication required

## 📖 Documentation Levels

Choose your documentation level:

### 🎯 **I need a quick reference**

→ [QUICK_REFERENCE.md](QUICK_REFERENCE.md)

### 📚 **I want complete documentation**

→ [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

### 🧪 **I want to test the API**

→ [api-examples.html](api-examples.html) or [Postman Collection](../BookStack_API.postman_collection.json)

## 💻 Code Examples

### JavaScript

```javascript
// Get available vouchers
fetch("http://localhost/BookStack/api/voucher.php?available=1")
  .then((res) => res.json())
  .then((data) => console.log(data));

// Create user with Basic Auth
const credentials = btoa("admin:password123");
fetch("http://localhost/BookStack/api/users.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Authorization: `Basic ${credentials}`,
  },
  body: JSON.stringify({
    username: "newuser",
    email: "new@example.com",
    password: "pass123",
  }),
})
  .then((res) => res.json())
  .then((data) => console.log(data));
```

### PHP

```php
<?php
// Get voucher by code
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/BookStack/api/voucher.php?code=SAVE20");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$voucher = json_decode($response, true);
print_r($voucher);
?>
```

### Python

```python
import requests
from requests.auth import HTTPBasicAuth

# Get all users
response = requests.get(
    'http://localhost/BookStack/api/users.php',
    auth=HTTPBasicAuth('admin', 'password123')
)
users = response.json()
print(users)
```

## 📊 Response Format

All responses are in JSON format:

### Success

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error

```json
{
  "success": false,
  "message": "Error description"
}
```

## 🔢 HTTP Status Codes

| Code | Description                    |
| ---- | ------------------------------ |
| 200  | OK - Request successful        |
| 201  | Created - Resource created     |
| 400  | Bad Request - Invalid input    |
| 401  | Unauthorized - Auth failed     |
| 404  | Not Found - Resource not found |
| 409  | Conflict - Duplicate resource  |
| 500  | Server Error                   |

## 🧪 Testing Tools

### Option 1: Interactive HTML

Open [api-examples.html](api-examples.html) in your browser for a visual testing interface with forms and response viewers.

### Option 2: Postman

1. Import [BookStack_API.postman_collection.json](../BookStack_API.postman_collection.json)
2. Update environment variables if needed
3. Test all endpoints with pre-configured requests

### Option 3: cURL

Use the command-line examples in [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

## 🔒 Security Notes

1. **Use HTTPS in production** - Never send credentials over HTTP in production
2. **Strong passwords** - Enforce minimum 8 characters with complexity
3. **Rate limiting** - Implement to prevent abuse
4. **Input validation** - Already implemented with prepared statements
5. **Admin access** - Restrict to trusted networks in production

## 📞 Support

For detailed endpoint documentation, validation rules, and advanced examples:

→ **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)**

---

## 📁 File Structure

```
api/
├── auth.php                          # Authentication endpoint
├── users.php                         # User CRUD operations
├── voucher.php                       # Voucher management
├── auth-middleware.php               # Basic auth validator
├── response.php                      # JSON response helper
├── API_DOCUMENTATION.md              # Complete documentation
├── QUICK_REFERENCE.md                # Quick reference guide
├── README.md                         # This file
├── api-examples.html                 # Interactive testing tool
└── ../BookStack_API.postman_collection.json  # Postman collection
```

---

**API Version:** 1.0  
**Last Updated:** January 14, 2026
