<?php

define('MYAPPNAME', "Davide_De_Innocentis");

session_unset(); 
// destroy the session 
session_destroy();
$_SESSION = array();
$ldap_dummy = session_name(MYAPPNAME);
session_start();
session_regenerate_id();
$_SESSION['loggedin'] = false;

header('Location: index.html');
