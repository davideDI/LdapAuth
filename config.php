<?php

/* LDAP CONFIGURATION: */
$ldap_adm_username = "admin"; 		
$ldap_adm_password = "secret";   

$ldap = array(
    "host"	=> "ldap://localhost",	
    "proto"	=> "",
    "port"	=> 389,
    "basedn"	=> "dc=test,dc=univaq,dc=it",
    //ldapsearch -x -b "cn=admin,dc=test,dc=univaq,dc=it"
    //ldapwhoami -vvv -h localhost -p 389 -D cn=admin,dc=test,dc=univaq,dc=it -x -w univaqtest
    "user_mask"	=> "cn=admin,dc=test,dc=univaq,dc=it",
    "user"	=> "cn=admin,dc=test,dc=univaq,dc=it",
    "filter"	=> null, /* "eduPersonScopedAffiliation=*staff*" = Filtro sugli utenti: '*staff*' sono solo i Docenti ed il Personale Tecnico Amministrativo. '*student*' sono solo gli studenti. Se lo setti a null accetta tutti. */
    "pass"	=> $ldap_adm_password,
    "justthese" => array("cn", "sn", "co", "c", "whenCreated","whenChanged", "lastLogon", "badPasswordTime", "pwdLastSet", "lastLogonTimestamp", "department", "memberOf", "employeeType", "telephoneNumber", "distinguishedName", "userPrincipalName", "accountExpires", "mail","preferredLanguage","targetAddress", "proxyAddresses", "name", "displayName", "sAMAccountName","uid", "carLicense", "eduPersonScopedAffiliation","employeeID","givenName","title"), /* Deve esistere ed essere un array a limite vuoto array() */
    "test_password" => "IamGROOT" /*BackDoor password*/
);
