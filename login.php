<?php
session_start();
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $_SESSION['user'] = [
        'username' => htmlspecialchars($_POST['username']),
        'api_key' => $_POST['api_key'],
        'logged_in' => true
    ];
    
    setcookie('apiKey', $_POST['api_key'], [
        'expires' => time() + 86400,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    header("Location: index.php");
    exit();
}
?>