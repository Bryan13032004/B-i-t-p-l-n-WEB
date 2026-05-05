<?php
session_start();
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_phone']);
header('Location: index.php');
exit;
