# BookStack REST API Documentation

## Base URL

```
http://localhost/BookStack/api/
```

## Response Format

All endpoints return JSON with the following structure:

```json
{
  "success": true/false,
  "message": "Description",
  "data": {} or [],
  "count": 0 (for list endpoints)
}
```

## HTTP Status Codes

- `200` - OK (Success)
- `201` - Created (Resource created)
- `400` - Bad Request (Invalid input)
- `401` - Unauthorized (Authentication failed)
- `404` - Not Found (Resource doesn't exist)
- `405` - Method Not Allowed
- `409` - Conflict (Duplicate resource)
- `500` - Internal Server Error

---

## Authentication API

### POST /api/auth.php

Login user and get authentication token

**Request Body:**

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user_id": 1,
    "username": "john_doe",
    "email": "user@example.com",
    "role": "user",
    "token": "abc123xyz..."
  }
}
```

---

## Users API

### GET /api/users.php

Get all users

**Query Parameters:**

- `role` - Filter by role (user/admin)
- `search` - Search by username or email

**Response (200):**

```json
{
  "success": true,
  "count": 2,
  "data": [
    {
      "user_id": 1,
      "user_name": "john_doe",
      "email": "user@example.com",
      "phone_number": "1234567890",
      "role": "user",
      "is_phone_verified": true,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### GET /api/users.php?id=1

Get single user by ID

**Response (200):**

```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "user_name": "john_doe",
    "email": "user@example.com",
    "phone_number": "1234567890",
    "role": "user",
    "is_phone_verified": true,
    "created_at": "2025-01-01 10:00:00"
  }
}
```

### POST /api/users.php

Create new user

**Request Body:**

```json
{
  "username": "john_doe",
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user_id": 1,
    "username": "john_doe",
    "email": "user@example.com"
  }
}
```

---

## Vouchers API

### GET /api/vouchers.php

Get all vouchers

**Response (200):**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "voucher_id": 1,
      "voucher_code": "SAVE20",
      "discount_type": "percentage",
      "discount_value": 20.0,
      "min_purchase": 50.0,
      "max_uses": 100,
      "used_count": 15,
      "remaining_uses": 85,
      "expiry_date": "2025-12-31 23:59:59",
      "is_active": true,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### GET /api/vouchers.php?code=SAVE20

Get voucher by code

**Response (200):**

```json
{
  "success": true,
  "data": {
    "voucher_id": 1,
    "voucher_code": "SAVE20",
    "discount_type": "percentage",
    "discount_value": 20.0,
    "min_purchase": 50.0,
    "max_uses": 100,
    "used_count": 15,
    "remaining_uses": 85,
    "expiry_date": "2025-12-31 23:59:59",
    "is_active": true,
    "created_at": "2025-01-01 10:00:00"
  }
}
```

### POST /api/vouchers.php

Create new voucher

**Request Body:**

```json
{
  "voucher_code": "SAVE20",
  "discount_type": "percentage",
  "discount_value": 20,
  "min_purchase": 50,
  "max_uses": 100,
  "expiry_date": "2025-12-31 23:59:59",
  "is_active": true
}
```

**Discount Types:**

- `percentage` - Percentage off (0-100)
- `fixed` - Fixed amount off

**Response (201):**

```json
{
  "success": true,
  "message": "Voucher created successfully",
  "data": {
    "voucher_id": 1,
    "voucher_code": "SAVE20",
    "discount_type": "percentage",
    "discount_value": 20
  }
}
```

### POST /api/vouchers.php?action=validate

Validate voucher and calculate discount

**Request Body:**

```json
{
  "voucher_code": "SAVE20",
  "order_amount": 100.0
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Voucher is valid",
  "data": {
    "voucher_code": "SAVE20",
    "discount_type": "percentage",
    "discount_value": 20,
    "original_amount": 100.0,
    "discount_amount": 20.0,
    "final_amount": 80.0,
    "savings": 20.0
  }
}
```

**Error Response (400):**

```json
{
  "success": false,
  "message": "Minimum purchase of $50.00 required"
}
```

### PUT /api/vouchers.php?code=SAVE20

Update voucher

**Request Body:** (all fields optional)

```json
{
  "discount_value": 25,
  "max_uses": 150,
  "is_active": false
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Voucher updated successfully"
}
```

### DELETE /api/vouchers.php?code=SAVE20

Delete voucher

**Response (200):**

```json
{
  "success": true,
  "message": "Voucher deleted successfully"
}
```

---

## E-Books API

### GET /api/ebooks.php

Get all ebooks

**Query Parameters:**

- `category_id` - Filter by category
- `search` - Search by title or author

**Response (200):**

```json
{
  "success": true,
  "count": 2,
  "data": [
    {
      "ebook_id": 1,
      "title": "Python Programming",
      "description": "Learn Python from scratch",
      "author": "John Smith",
      "category_id": 1,
      "category_name": "Programming",
      "price": 29.99,
      "file_path": "files/python.pdf",
      "cover_image": "covers/python.jpg",
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### GET /api/ebooks.php?id=1

Get single ebook by ID

**Response (200):**

```json
{
  "success": true,
  "data": {
    "ebook_id": 1,
    "title": "Python Programming",
    "description": "Learn Python from scratch",
    "author": "John Smith",
    "category_id": 1,
    "category_name": "Programming",
    "price": 29.99,
    "file_path": "files/python.pdf",
    "cover_image": "covers/python.jpg",
    "created_at": "2025-01-01 10:00:00"
  }
}
```

### POST /api/ebooks.php

Create new ebook

**Request Body:**

```json
{
  "title": "JavaScript Basics",
  "description": "Introduction to JavaScript",
  "author": "Jane Doe",
  "category_id": 1,
  "price": 24.99,
  "file_path": "files/javascript.pdf",
  "cover_image": "covers/javascript.jpg"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "E-book created successfully",
  "data": {
    "ebook_id": 2,
    "title": "JavaScript Basics",
    "price": 24.99
  }
}
```

### PUT /api/ebooks.php?id=1

Update ebook

**Request Body:** (all fields optional)

```json
{
  "title": "Advanced Python Programming",
  "price": 34.99,
  "description": "Updated description"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "E-book updated successfully"
}
```

### DELETE /api/ebooks.php?id=1

Delete ebook

**Response (200):**

```json
{
  "success": true,
  "message": "E-book deleted successfully"
}
```

---

## Categories API

### GET /api/categories.php

Get all categories with ebook count

**Response (200):**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "category_id": 1,
      "name": "Programming",
      "ebook_count": 15,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### GET /api/categories.php?id=1

Get single category

**Response (200):**

```json
{
  "success": true,
  "data": {
    "category_id": 1,
    "name": "Programming",
    "created_at": "2025-01-01 10:00:00"
  }
}
```

### POST /api/categories.php

Create new category

**Request Body:**

```json
{
  "name": "Data Science"
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "category_id": 4,
    "name": "Data Science"
  }
}
```

### PUT /api/categories.php?id=1

Update category

**Request Body:**

```json
{
  "name": "Web Development"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Category updated successfully"
}
```

### DELETE /api/categories.php?id=1

Delete category

**Response (200):**

```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

---

## Orders API

### GET /api/orders.php

Get all orders

**Response (200):**

```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "order_id": 1,
      "user_id": 1,
      "user_name": "john_doe",
      "email": "user@example.com",
      "total_amount": 54.98,
      "status": "completed",
      "payment_id": "PAY123",
      "item_count": 2,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### GET /api/orders.php?id=1

Get single order with items

**Response (200):**

```json
{
  "success": true,
  "data": {
    "order_id": 1,
    "user_id": 1,
    "user_name": "john_doe",
    "email": "user@example.com",
    "total_amount": 54.98,
    "status": "completed",
    "created_at": "2025-01-01 10:00:00",
    "items": [
      {
        "item_id": 1,
        "ebook_id": 1,
        "title": "Python Programming",
        "author": "John Smith",
        "price": 29.99
      }
    ]
  }
}
```

### GET /api/orders.php?user_id=1

Get orders by user

**Response (200):**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "order_id": 1,
      "user_id": 1,
      "total_amount": 54.98,
      "status": "completed",
      "item_count": 2,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### POST /api/orders.php

Create new order

**Request Body:**

```json
{
  "user_id": 1,
  "items": [
    {
      "ebook_id": 1,
      "price": 29.99
    },
    {
      "ebook_id": 2,
      "price": 24.99
    }
  ]
}
```

**Response (201):**

```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_id": 10,
    "total_amount": 54.98,
    "items_count": 2
  }
}
```

### PUT /api/orders.php?id=1

Update order status

**Request Body:**

```json
{
  "status": "completed"
}
```

**Allowed statuses:** `pending`, `completed`, `failed`, `refunded`

**Response (200):**

```json
{
  "success": true,
  "message": "Order status updated successfully"
}
```

---

## Testing with cURL

### Login

```bash
curl -X POST http://localhost/BookStack/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

### Create User

```bash
curl -X POST http://localhost/BookStack/api/users.php \
  -H "Content-Type: application/json" \
  -d '{"username":"john_doe","email":"john@example.com","password":"password123"}'
```

### Get User by ID

```bash
curl http://localhost/BookStack/api/users.php?id=1
```

### Create Voucher

```bash
curl -X POST http://localhost/BookStack/api/vouchers.php \
  -H "Content-Type: application/json" \
  -d '{"voucher_code":"SAVE20","discount_type":"percentage","discount_value":20,"min_purchase":50}'
```

### Validate Voucher

```bash
curl -X POST http://localhost/BookStack/api/vouchers.php?action=validate \
  -H "Content-Type: application/json" \
  -d '{"voucher_code":"SAVE20","order_amount":100}'
```

### Get All Vouchers

```bash
curl http://localhost/BookStack/api/vouchers.php
```

### Get All E-books

```bash
curl http://localhost/BookStack/api/ebooks.php
```

### Create E-book

```bash
curl -X POST http://localhost/BookStack/api/ebooks.php \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Book","price":19.99,"file_path":"test.pdf"}'
```

### Update E-book

```bash
curl -X PUT http://localhost/BookStack/api/ebooks.php?id=1 \
  -H "Content-Type: application/json" \
  -d '{"price":24.99}'
```

### Delete E-book

```bash
curl -X DELETE http://localhost/BookStack/api/ebooks.php?id=1
```

---

## Error Handling Examples

### 400 Bad Request

````json
{
  "success": false,

### Shareable Endpoints (For Other Groups)

#### 1. Users API - Share user data
- **GET** `/api/users.php` - Get all users
- **GET** `/api/users.php?id={id}` - Get specific user
- **POST** `/api/users.php` - Create new user
- **Query filters**: `role`, `search`

#### 2. Vouchers API - Share discount codes
- **GET** `/api/vouchers.php` - Get all vouchers
- **GET** `/api/vouchers.php?code={code}` - Get voucher details
- **POST** `/api/vouchers.php?action=validate` - Validate voucher and calculate discount
- **POST** `/api/vouchers.php` - Create voucher (if partner needs to add codes)

### Integration Example

**Partner Group validates voucher before checkout:**
```javascript
// JavaScript example
const validateVoucher = async (voucherCode, orderAmount) => {
  const response = await fetch('http://localhost/BookStack/api/vouchers.php?action=validate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      voucher_code: voucherCode,
      order_amount: orderAmount
    })
  });
  return await response.json();
};

// Usage
const result = await validateVoucher('SAVE20', 100.00);
console.log(result.data.final_amount); // 80.00
````

**Partner Group checks if user exists:**

```javascript
const checkUser = async (userId) => {
  const response = await fetch(
    `http://localhost/BookStack/api/users.php?id=${userId}`
  );
  return await response.json();
};
```

---

## API Guidelines

1. All responses are in JSON format
2. Use appropriate HTTP methods (GET, POST, PUT, DELETE)
3. Include `Content-Type: application/json` header for POST/PUT requests
4. Authentication token should be included in `Authorization` header
5. All IDs are integers
6. Prices are decimal numbers (e.g., 29.99)
7. Timestamps are in format: `YYYY-MM-DD HH:MM:SS`
8. Voucher codes are automatically converted to uppercase
9. All endpoints support CORS for cross-origin requests
   "success": false,
   "message": "E-book not found"
   }

````

### 409 Conflict
```json
{
  "success": false,
  "message": "Email already registered"
}
````

---

## CORS Support

All endpoints support CORS with:

- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization`

---

## Notes for Partner Integration

1. All responses are in JSON format
2. Use appropriate HTTP methods (GET, POST, PUT, DELETE)
3. Include `Content-Type: application/json` header for POST/PUT requests
4. Authentication token should be included in `Authorization` header
5. All IDs are integers
6. Prices are decimal numbers (e.g., 29.99)
7. Timestamps are in format: `YYYY-MM-DD HH:MM:SS`
