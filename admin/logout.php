<?php
require_once '../config.php';

unset($_SESSION['admin_id']);
session_destroy();
redirect('login.php');
?>