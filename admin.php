<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

include 'head.php';
include 'adminheader.php';
