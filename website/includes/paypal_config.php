<?php

define('PAYPAL_MODE', 'sandbox');  


define('PAYPAL_CLIENT_ID', 'Ad0tODP9qAXaWIHTOcjmC284d9IpJGQi_VwOrlSc1AwXjKpZeFLQOyswxsMNfkBWXFe5cQTt8ppwWvBd');      
define('PAYPAL_CLIENT_SECRET', 'EJV2lOl9GnMsZMmoKVc8UhdEozxpdL2U_AqRrbdiCFQNQUi6axcl6Tdcz3rO1tnMnVhLpe4-Ks0fb73h'); 


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