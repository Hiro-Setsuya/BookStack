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
define('ESCAPINAS_API_VOUCHERS', ESCAPINAS_BASE_URL . ESCAPINAS_API_PATH . '/vouchers.php');

// Bookstore Base URL 
define('BOOKSTORE_BASE_URL', '192.168.18.37');
