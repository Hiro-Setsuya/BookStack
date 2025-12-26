# BookStack API - Partner Integration Guide

## 🤝 APIs for Sharing with Other Groups

We provide **2 main APIs** for partner integration:

### 1. **Users API** - Share user data between systems

### 2. **Vouchers API** - Share discount codes and validate purchases

---

## 📋 Base URL

```
http://localhost/BookStack/api/
```

---

## 👥 Users API

### Get All Users

```http
GET /api/users.php
```

**Response:**

```json
{
  "success": true,
  "count": 10,
  "data": [
    {
      "user_id": 1,
      "user_name": "john_doe",
      "email": "john@example.com",
      "phone_number": "1234567890",
      "role": "user",
      "is_phone_verified": true,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### Get Specific User

```http
GET /api/users.php?id=1
```

### Filter Users

```http
GET /api/users.php?role=user
GET /api/users.php?search=john
```

### Create New User

```http
POST /api/users.php
Content-Type: application/json

{
  "username": "jane_smith",
  "email": "jane@example.com",
  "password": "securepass123"
}
```

**Response:**

```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user_id": 15,
    "username": "jane_smith",
    "email": "jane@example.com"
  }
}
```

---

## 🎟️ Vouchers API

### Get All Vouchers

```http
GET /api/vouchers.php
```

**Response:**

```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "voucher_id": 1,
      "voucher_code": "SAVE20",
      "discount_type": "percentage",
      "discount_value": 20.0,
      "min_purchase": 50.0,
      "max_uses": 100,
      "used_count": 25,
      "remaining_uses": 75,
      "expiry_date": "2025-12-31 23:59:59",
      "is_active": true,
      "created_at": "2025-01-01 10:00:00"
    }
  ]
}
```

### Get Voucher by Code

```http
GET /api/vouchers.php?code=SAVE20
```

### 🔥 Validate Voucher (Most Important!)

```http
POST /api/vouchers.php?action=validate
Content-Type: application/json

{
  "voucher_code": "SAVE20",
  "order_amount": 100.00
}
```

**Success Response:**

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

**Error Response:**

```json
{
  "success": false,
  "message": "Voucher has expired"
}
```

**Other error messages:**

- "Invalid or inactive voucher code"
- "Voucher usage limit reached"
- "Minimum purchase of $50.00 required"

### Create Voucher

```http
POST /api/vouchers.php
Content-Type: application/json

{
  "voucher_code": "NEWUSER10",
  "discount_type": "fixed",
  "discount_value": 10,
  "min_purchase": 30,
  "max_uses": 50,
  "expiry_date": "2025-12-31 23:59:59",
  "is_active": true
}
```

**Discount Types:**

- `percentage` - Value from 0-100 (e.g., 20 = 20% off)
- `fixed` - Fixed dollar amount (e.g., 10 = $10 off)

---

## 🔧 Integration Examples

### JavaScript/Fetch API

**Check if user exists:**

```javascript
async function checkUser(userId) {
  const response = await fetch(
    `http://localhost/BookStack/api/users.php?id=${userId}`
  );
  const data = await response.json();

  if (data.success) {
    console.log("User found:", data.data);
    return data.data;
  } else {
    console.log("User not found");
    return null;
  }
}

// Usage
const user = await checkUser(1);
```

**Validate voucher before checkout:**

```javascript
async function validateVoucher(code, amount) {
  const response = await fetch(
    "http://localhost/BookStack/api/vouchers.php?action=validate",
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        voucher_code: code,
        order_amount: amount,
      }),
    }
  );

  const result = await response.json();

  if (result.success) {
    console.log("Voucher valid!");
    console.log("Original:", result.data.original_amount);
    console.log("Discount:", result.data.discount_amount);
    console.log("Final:", result.data.final_amount);
    return result.data;
  } else {
    console.log("Error:", result.message);
    return null;
  }
}

// Usage
const discount = await validateVoucher("SAVE20", 100);
if (discount) {
  console.log(`Pay only $${discount.final_amount}`);
}
```

### PHP/cURL

**Validate voucher:**

```php
<?php
function validateVoucher($code, $amount) {
    $url = 'http://localhost/BookStack/api/vouchers.php?action=validate';

    $data = json_encode([
        'voucher_code' => $code,
        'order_amount' => $amount
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Usage
$result = validateVoucher('SAVE20', 100);
if ($result['success']) {
    echo "Final amount: $" . $result['data']['final_amount'];
} else {
    echo "Error: " . $result['message'];
}
?>
```

### Python/Requests

```python
import requests

def validate_voucher(code, amount):
    url = 'http://localhost/BookStack/api/vouchers.php?action=validate'
    data = {
        'voucher_code': code,
        'order_amount': amount
    }

    response = requests.post(url, json=data)
    result = response.json()

    if result['success']:
        print(f"Final amount: ${result['data']['final_amount']}")
        return result['data']
    else:
        print(f"Error: {result['message']}")
        return None

# Usage
discount = validate_voucher('SAVE20', 100)
```

---

## 📦 Postman Collection

Import this collection to test all endpoints:

```
BookStack_API.postman_collection.json
```

**How to import:**

1. Open Postman
2. Click "Import" button
3. Select the JSON file
4. All endpoints will be ready to test!

---

## ✅ Response Format

All APIs return JSON with this structure:

**Success:**

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "count": 10
}
```

**Error:**

```json
{
  "success": false,
  "message": "Error description"
}
```

---

## 📊 HTTP Status Codes

| Code | Meaning                    |
| ---- | -------------------------- |
| 200  | Success                    |
| 201  | Created                    |
| 400  | Bad Request (invalid data) |
| 404  | Not Found                  |
| 409  | Conflict (duplicate)       |
| 500  | Server Error               |

---

## 🔐 CORS Support

All endpoints support cross-origin requests:

- ✅ Cross-domain requests allowed
- ✅ All HTTP methods supported
- ✅ JSON content type supported

---

## 💡 Use Cases

### For Partner Group's E-commerce:

1. **Validate voucher at checkout** - Check if code is valid and calculate discount
2. **Check user exists** - Verify if customer is registered
3. **Create new user** - Register customers from your system
4. **Get voucher details** - Display available promotions

### For Partner Group's Admin Panel:

1. **View all vouchers** - See available discount codes
2. **Create vouchers** - Add new promotional codes
3. **Check voucher usage** - See remaining uses
4. **User management** - View/search users

---

## 🚀 Quick Start

**Step 1: Test if API is running**

```bash
curl http://localhost/BookStack/api/vouchers.php
```

**Step 2: Create a test voucher**

```bash
curl -X POST http://localhost/BookStack/api/vouchers.php \
  -H "Content-Type: application/json" \
  -d '{"voucher_code":"TEST20","discount_type":"percentage","discount_value":20,"min_purchase":0}'
```

**Step 3: Validate it**

```bash
curl -X POST http://localhost/BookStack/api/vouchers.php?action=validate \
  -H "Content-Type: application/json" \
  -d '{"voucher_code":"TEST20","order_amount":100}'
```

---

## 📞 Support

For questions about API integration:

- Check the main [API Documentation](README.md)
- Test with Postman collection
- Contact: support@bookstack.com

---

## ✨ Key Features

✅ **Users API**: Share user data between systems  
✅ **Vouchers API**: Complete discount code management  
✅ **Real-time validation**: Instant voucher verification  
✅ **Flexible discounts**: Percentage or fixed amount  
✅ **Usage limits**: Control voucher redemptions  
✅ **Expiry dates**: Time-limited promotions  
✅ **CORS enabled**: Works from any domain  
✅ **RESTful design**: Standard HTTP methods  
✅ **JSON format**: Easy to parse and use
