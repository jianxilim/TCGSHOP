<?php

define('PAYPAL_MODE', 'sandbox');  


define('PAYPAL_CLIENT_ID', 'AXTZbHOzw9XivAyKQPM_0Chh8dtXbhVPCmFUrNubTkAcodo49qqVuhRzegRXKZykq9fQ-w3xmr_kUyrI');      
define('PAYPAL_CLIENT_SECRET', 'EGk6I_hzxaoBUorYOh_Qf2o5B1dgyhOUJRgd15Bo4v9LsqspL2gK92z9X1T6nqCGadEodxGplBUIePwh'); 


if (PAYPAL_MODE == 'sandbox') {
    define('PAYPAL_API_URL', 'https://api-m.sandbox.paypal.com');
    define('PAYPAL_WEB_URL', 'https://www.sandbox.paypal.com');
} else {
    define('PAYPAL_API_URL', 'https://api-m.paypal.com');
    define('PAYPAL_WEB_URL', 'https://www.paypal.com');
}

define('SITE_URL', 'http://localhost/website/');
define('PAYPAL_RETURN_URL', SITE_URL . 'member/paypal_success.php');  
define('PAYPAL_CANCEL_URL', SITE_URL . 'member/paypal_cancel.php');   
?>