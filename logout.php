<?php

session_unset(); 
// destroy the session 
session_destroy();
$_SESSION = array();
session_start();
session_regenerate_id();
$_SESSION['loggedin'] = false;

header('Location: index.html');
