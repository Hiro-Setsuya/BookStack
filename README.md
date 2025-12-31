ebook-ecommerce-system/
│
├── admin/
│ ├── index.php # Admin login
│ ├── dashboard.php # Admin dashboard
│ ├── manage-ebooks.php # Add/Edit/Delete e-books
│ ├── manage-categories.php # Manage book categories
│ ├── manage-users.php # View users
│ ├── manage-orders.php # View orders
│ ├── reports.php # Sales reports
│ └── logout.php
│
├── api/ # REST API (Task 9.5)
│ ├── ebooks.php # GET e-books
│ ├── orders.php # GET/POST orders
│ ├── users.php # GET users (limited)
│ ├── auth.php # API authentication
│ └── response.php # JSON response handler
│
├── assets/
│ ├── css/
│ │ ├── bootstrap.min.css
│ │ └── style.css
│ │
│ ├── js/
│ │ ├── bootstrap.bundle.min.js
│ │ ├── cart.js
│ │ └── validation.js
│ │
│ └── images/
│ ├── logo.png
│ └── ebooks/
│
├── chatbot/ # Ollama integration (Task 9.2)
│ ├── chatbot.php
│ └── ollama-config.php
│
├── config/
│ ├── db.php # Database connection
│ ├── paypal.php # PayPal config
│ ├── mail.php # Email config
│ └── sms.php # SMS config
│
├── includes/
│ ├── header.php
│ ├── footer.php
│ ├── auth.php # Session/auth checks
│ ├── functions.php # Common helper functions
│ └── navbar.php
│
├── uploads/
│ ├── ebooks/ # Uploaded PDF e-books
│ └── thumbnails/ # Book cover images
│
├── payments/ # PayPal integration (Task 9.1)
│ ├── create-order.php
│ ├── capture-order.php
│ └── payment-success.php
│
├── notifications/ # SMS & Email (Task 9.3–9.4)
│ ├── send-email.php
│ └── send-sms.php
│
├── database/
│ ├── ebook_store_db.sql # SQL schema dump
│
├── docs/ # Documentation assets
│ ├── diagrams/
│ │ ├── flowchart.png
│ │ ├── usecase.png
│ │ ├── class-diagram.png
│ │ └── activity-diagram.png
│ └── screenshots/
│
├── index.php # Home page
├── ebooks.php # Browse e-books
├── ebook-details.php # View single e-book
├── cart.php # Shopping cart
├── checkout.php # Checkout page
├── orders.php # User order history
├── download.php # Secure e-book download
├── login.php
├── register.php
├── logout.php
│
├── .env.example # Environment variables sample
├── .gitignore # Ignore uploads, secrets
├── README.md # Project overview
└── LICENSE
