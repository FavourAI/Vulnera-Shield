# logout.php to end session
logout_php = '''<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>