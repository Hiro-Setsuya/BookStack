<?php

/**
 * External API Configuration
 * Centralized configuration for EscaPinas system integration
 */

// EscaPinas Base URL
define('ESCAPINAS_BASE_URL', 'http://192.168.1.10');
define('ESCAPINAS_API_PATH', '/EscaPinas/frontend/integs/api');

// EscaPinas API Endpoints
define('ESCAPINAS_API_USERS', ESCAPINAS_BASE_URL . ESCAPINAS_API_PATH . '/users1.php');
define('ESCAPINAS_API_VOUCHERS', ESCAPINAS_BASE_URL . ESCAPINAS_API_PATH . '/vouchers1.php');

// Add more endpoints below as needed:
// define('ESCAPINAS_API_ORDERS', ESCAPINAS_BASE_URL . ESCAPINAS_API_PATH . '/orders.php');
// define('ESCAPINAS_API_PRODUCTS', ESCAPINAS_BASE_URL . ESCAPINAS_API_PATH . '/products.php');
// define('ESCAPINAS_API_PAYMENTS', ESCAPINAS_BASE_URL . ESCAPINAS_API_PATH . '/payments.php');
