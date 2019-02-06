<?php
require_once('../config/core/config.php');

session_start();
unset($_SESSION);
session_destroy();

// Shibboleth local logout is always relative to the root
$shiblocallogout = 'https://' . $_SERVER['HTTP_HOST'] . '/Shibboleth.sso/Logout';
$shiblogout = $_SERVER['HTTP_SHIB_LOGOUTURL'];
require_once('../config/core/config.php');
require_once('../config/core/config.php');
require_once PHPWS_SOURCE_DIR . 'src/Server.php';

$parts = explode('/', $_SERVER['SCRIPT_NAME']);
while(array_pop($parts) != 'secure');
$return_url = 'http://' . $_SERVER['HTTP_HOST'] . implode('/', $parts);
?>
<html>
    <head>
        <meta http-equiv="refresh" content="2;url=<?php echo $return_url; ?>" />
    </head>
    <body>
    <p>Logging you out...</p>
  <p><a href="<?php echo $return_url; ?>">If you are not redirected automatically, please click this link.</a></p>
      <iframe style="display: none" src="<?php echo $shiblogout; ?>"><p>Logging You Out...</p></iframe>
      <iframe style="display: none" src="<?php echo $shiblocallogout; ?>"><p>Logging You Out...</p></iframe>

    </body>
</html>
