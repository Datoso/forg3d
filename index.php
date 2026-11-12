<?php
session_start();
header('Location: view/' . (isset($_SESSION['usuario']) ? 'home.php' : 'login.php'));
exit;
