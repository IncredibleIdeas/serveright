<?php
require_once 'config.php';

// Destroy all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
redirect('index.php');
?>