<?php

if (!defined('APP_NAME')) {
    define('APP_NAME', 'POS SaaS');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}
if (!defined('CURRENCY')) {
    define('CURRENCY', 'PKR');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'Rs.');
}
if (!defined('DATE_FORMAT')) {
    define('DATE_FORMAT', 'd-m-Y');
}
if (!defined('DATETIME_FORMAT')) {
    define('DATETIME_FORMAT', 'd-m-Y H:i');
}
if (!defined('PER_PAGE')) {
    define('PER_PAGE', 25);
}
if (!defined('LOGIN_MAX_ATTEMPTS')) {
    define('LOGIN_MAX_ATTEMPTS', 5);
}
if (!defined('LOGIN_LOCKOUT_MINUTES')) {
    define('LOGIN_LOCKOUT_MINUTES', 15);
}
if (!defined('UPLOAD_MAX_SIZE')) {
    define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2MB
}
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
}
