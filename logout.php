<?php
// logout.php
require_once 'includes/config.php';
session_destroy();
header('Location: index.php?logged_out=1');
exit();
?>