# 📚 BookStack - E-Book Store & Management System

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)
![PayPal](https://img.shields.io/badge/PayPal-00457C?style=flat&logo=paypal&logoColor=white)

BookStack is a comprehensive e-book e-commerce platform built with PHP and MySQL. It provides a complete solution for selling digital books online with features like shopping cart, payment processing, user management, admin dashboard, REST API, and advanced verification systems.

## ✨ Features

### 🛒 Customer Features

- **User Authentication** - Registration, login, password recovery, and email/SMS verification
- **E-Book Browsing** - Browse books by categories with detailed descriptions
- **Shopping Cart** - Add multiple books to cart and manage purchases
- **Secure Checkout** - PayPal integration for safe payments
- **Order History** - View and track all past purchases
- **Secure Downloads** - Download purchased e-books securely
- **Profile Management** - Update personal information and change password
- **AI Chatbot** - Interactive chatbot for customer support

### 👨‍💼 Admin Features

- **Dashboard** - Overview of sales, orders, and users
- **E-Book Management** - Add, edit, delete, and manage e-books
- **Category Management** - Organize books into categories
- **User Management** - View and manage user accounts
- **Order Management** - Track and manage all orders
- **Verification System** - Send verification codes via Email/SMS with reply tracking
- **Sales Reports** - Generate and view sales analytics
- **Voucher System** - Create and manage discount vouchers

### 🔧 Technical Features

- **REST API** - Full-featured API with authentication (See [API Documentation](api/API_DOCUMENTATION.md) | [Postman Collection](BookStack_API.postman_collection.json))
- **Email/SMS Integration** - Automated notifications and verification
- **Reply Processing** - Capture and process email/SMS replies for verification
- **Partner Integration** - API for third-party integrations
- **Secure File Handling** - Protected e-book downloads
- **Session Management** - Secure user sessions
- **Responsive Design** - Mobile-friendly interface

## 🏗️ Architecture

```
BookStack/
│
├── admin/                          # Admin Panel
│   ├── dashboard.php              # Admin dashboard
│   ├── login.php                  # Admin login
│   ├── manage-ebooks.php          # E-book CRUD operations
│   ├── manage-categories.php      # Category management
│   ├── manage-users.php           # User management
│   ├── manage-orders.php          # Order management
│   ├── manage-reports.php         # Sales reports
│   ├── manage-verification.php    # Verification system
│   └── logout.php                 # Admin logout
│
├── api/                           # REST API
│   ├── auth.php                   # API authentication
│   ├── auth-middleware.php        # API middleware
│   ├── users.php                  # User endpoints
│   ├── voucher.php                # Voucher endpoints
│   ├── response.php               # JSON response handler
│   ├── process-sms-reply.php      # SMS reply processor
│   ├── process-email-reply.php    # Email reply processor
│   ├── check-email-replies.php    # Email reply checker
│   └── API_DOCUMENTATION.md       # Complete API documentation
│
│
├── assets/
│   ├── img/
│   │   ├── ebook_cover/           # Book cover images
│   │   └── logo/                  # Logo assets
│   └── js/
│       └── cart.js                # Shopping cart logic
│
├── chatbot/                       # AI Chatbot
│   ├── chatbot.php               # Chatbot interface
│   ├── script.js                 # Chatbot JavaScript
│   ├── style.css                 # Chatbot styles
│   └── data.txt                  # Chatbot training data
│
├── config/                        # Configuration Files
│   ├── db.php                     # Database connection
│   ├── mail.php                   # Email configuration
│   ├── paypal.php                 # PayPal settings
│   └── sms.php                    # SMS gateway config
│
├── lib/                           # Third-party Libraries
│   └── PHPMailer-master/         # Email library
│
├── logs/                          # Application logs
│
├── notifications/                 # Notification System
│   ├── send-email.php            # Email sender
│   └── send-sms.php              # SMS sender
│
├── payment/                       # Payment Processing
│   ├── create-order.php          # Create PayPal order
│   └── capture-order.php         # Capture payment
│
├── cart.php                       # Shopping cart page
├── checkout.php                   # Checkout process
├── ebook-details.php             # E-book details page
├── ebooks.php                     # E-book listing
├── download.php                   # Secure download handler
├── index.php                      # Home page
├── login.php                      # User login
├── register.php                   # User registration
├── forgot-password.php           # Password recovery
├── change-password.php           # Password change
├── profile.php                    # User profile
├── my-ebooks.php                 # Purchased e-books
├── orders.php                     # Order history
├── request-verification.php       # Verification request
├── sql_query_tables.sql          # Database schema
├── sql_query_insert.sql          # Sample data
└── style.css                      # Global styles
```

## 🚀 Getting Started

### Prerequisites

- **XAMPP** (or any PHP server with MySQL)
  - PHP 7.4 or higher
  - MySQL 5.7 or higher
- **Web Browser** (Chrome, Firefox, Edge, Safari)
- **PayPal Account** (for payment integration)
- **Email/SMS Gateway** (optional, for notifications)

### Installation

1. **Clone or Download the Repository**

   ```bash
   git clone <repository-url>
   ```

   Or download and extract to `C:\xampp\htdocs\BookStack`

2. **Start XAMPP**

   - Start Apache and MySQL from XAMPP Control Panel

3. **Create Database**

   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create a new database named `bookstack_db`
   - Import the database schema:
     - Open `sql_query_tables.sql` and execute it
     - (Optional) Import `sql_query_insert.sql` for sample data

4. **Configure Database Connection**

   Edit `config/db.php`:

   ```php
   $dbhost = "localhost:3307";  // Change port if needed
   $dbuser = "root";
   $dbpass = "";
   $db = "bookstack_db";
   ```

5. **Configure Email (Optional)**

   Edit `config/mail.php` with your SMTP settings:

   ```php
   $mail->Host = 'smtp.gmail.com';
   $mail->Username = 'your-email@gmail.com';
   $mail->Password = 'your-app-password';
   ```

6. **Configure PayPal (Optional)**

   Edit `config/paypal.php`:

   ```php
   define('PAYPAL_CLIENT_ID', 'your-client-id');
   define('PAYPAL_SECRET', 'your-secret');
   ```

7. **Configure SMS Gateway (Optional)**

   Edit `config/sms.php` with your SMS provider credentials

8. **Access the Application**
   - **Frontend**: http://localhost/BookStack/
   - **Admin Panel**: http://localhost/BookStack/admin/
     - Default admin credentials (create in database or register first user as admin)

## 📖 Usage Guide

### For Customers

1. **Register an Account**

   - Go to Register page
   - Fill in details (name, email, phone, password)
   - Verify account via email/SMS

2. **Browse E-Books**

   - Browse categories
   - View book details, author, price, description

3. **Purchase E-Books**

   - Add books to cart
   - Proceed to checkout
   - Pay via PayPal
   - Download from "My E-Books"

4. **Manage Profile**
   - Update personal information
   - Change password
   - View order history

### For Administrators

1. **Login to Admin Panel**

   - Navigate to `/admin/`
   - Enter admin credentials

2. **Manage E-Books**

   - Add new e-books with title, description, author, price, category
   - Upload cover images and PDF files
   - Edit or delete existing e-books

3. **Manage Categories**

   - Create book categories
   - Organize books by genre/topic

4. **Manage Users**

   - View all registered users
   - Manage user accounts
   - Send verification codes

5. **Process Verification**

   - Send verification codes via Email/SMS
   - Track user responses
   - Verify user accounts

6. **View Reports**
   - Monitor sales
   - Track orders
   - Generate analytics

## 🔌 API Documentation

The BookStack API provides programmatic access to user management and voucher operations.

### 📖 Complete Documentation

- **[Full API Documentation](api/API_DOCUMENTATION.md)** - Comprehensive guide with all endpoints
- **[Postman Collection](BookStack_API.postman_collection.json)** - Import into Postman for testing

### 🚀 Quick Start

**Base URL:** `http://localhost/BookStack/api`

#### Authentication

```bash
POST /auth.php
Content-Type: application/json

{
  "email": "admin@bookstack.com",
  "password": "password123"
}
```

#### Get All Vouchers

```bash
GET /voucher.php?available=1
```

#### Create User (requires Basic Auth)

```bash
POST /users.php
Authorization: Basic YWRtaW46cGFzc3dvcmQxMjM=
Content-Type: application/json

{
  "username": "newuser",
  "email": "user@example.com",
  "password": "securepass123"
}
```

#### Get Voucher by Code

```bash
GET /voucher.php?code=SAVE20
```

### 📋 Available Endpoints

#### Users API (Admin Auth Required)

- `GET /users.php` - Get all users
- `GET /users.php?id={id}` - Get user by ID
- `POST /users.php` - Create new user
- `PUT /users.php` - Update user

#### Vouchers API

- `GET /voucher.php` - Get all vouchers
- `GET /voucher.php?id={id}` - Get voucher by ID
- `GET /voucher.php?code={code}` - Get voucher by code
- `POST /voucher.php` - Create voucher
- `PUT /voucher.php?id={id}` - Update voucher
- `DELETE /voucher.php?id={id}` - Delete voucher

### 🧪 Testing

Import **[BookStack_API.postman_collection.json](BookStack_API.postman_collection.json)** into Postman for ready-to-use API requests with examples.

## 🔐 Security Features

- **Password Hashing** - Passwords stored with secure hashing
- **SQL Injection Protection** - Prepared statements and input validation
- **Session Management** - Secure session handling
- **CSRF Protection** - Cross-site request forgery prevention
- **File Upload Validation** - Secure file handling
- **Authentication Middleware** - API token-based authentication
- **Access Control** - Role-based permissions (user/admin)

## 📧 Email & SMS Integration

### Email System

- **PHPMailer** library for reliable email delivery
- Supports SMTP configuration
- Email reply processing for verification
- Automated notifications for orders and verification

### SMS System

- SMS gateway integration
- Reply capture via webhook or polling
- Two-way SMS communication
- Verification code delivery

See [VERIFICATION_REPLY_INTEGRATION.md](VERIFICATION_REPLY_INTEGRATION.md) for detailed setup.

## 🤝 Partner Integration

The system supports third-party integrations through the REST API. See [PARTNER_INTEGRATION.md](api/PARTNER_INTEGRATION.md) for:

- Partner authentication
- Voucher creation
- Order processing
- Webhook notifications

## 🗄️ Database Schema

Key tables:

- **users** - User accounts and authentication
- **ebooks** - E-book catalog
- **categories** - Book categories
- **orders** - Purchase orders
- **order_items** - Order line items
- **cart_items** - Shopping cart
- **downloads** - Download tracking
- **messages** - Verification messages with reply tracking
- **vouchers** - Discount vouchers
- **api_tokens** - API authentication tokens

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Libraries**:
  - PHPMailer (Email)
  - PayPal SDK (Payments)
  - JWT (API Authentication)
- **Server**: Apache (XAMPP)

## 📝 Additional Documentation

- [API Documentation](api/README.md) - Complete REST API reference
- [Implementation Summary](api/IMPLEMENTATION_SUMMARY.md) - Technical implementation details
- [Partner Integration Guide](api/PARTNER_INTEGRATION.md) - Third-party integration
- [Verification System](VERIFICATION_REPLY_INTEGRATION.md) - Email/SMS verification setup
- [SMS Forwarder Setup](SMS_FORWARDER_SETUP.md) - SMS configuration guide

## 🐛 Troubleshooting

### Database Connection Issues

- Verify MySQL is running in XAMPP
- Check port number in `config/db.php` (default: 3306 or 3307)
- Ensure database `bookstack_db` exists

### PayPal Integration Issues

- Verify API credentials in `config/paypal.php`
- Use sandbox mode for testing
- Check PayPal dashboard for error logs

### Email Not Sending

- Verify SMTP settings in `config/mail.php`
- Enable "Less secure app access" for Gmail
- Use app-specific passwords for Gmail

### File Upload Issues

- Check `php.ini` for `upload_max_filesize` and `post_max_size`
- Ensure `assets/img/ebook_cover/` has write permissions
- Verify file path is accessible

## 📄 License

This project is available for educational and commercial use.

## 👥 Contributors

Developed as an e-commerce platform for digital book distribution.

## 📞 Support

For issues or questions:

- Check the documentation files in the project
- Review API documentation for integration help
- Examine logs in `/logs/` directory

---

**Built with ❤️ using PHP & MySQL**
