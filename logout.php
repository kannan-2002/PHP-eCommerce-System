<?php
require_once 'config.php';

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
session_destroy();
redirect('index.php');
?>