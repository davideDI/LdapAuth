<?php

define('MYAPPNAME', "Davide_De_Innocentis");

/* Start Output Buffer */
ob_start();

/* Start PHP Session */
$ldap_dummy = session_name(MYAPPNAME);
session_start();

require_once('config.php');

/* Detect Client IP */
$ldap_client_ip = "";
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ldap_client_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ldap_client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ldap_client_ip = $_SERVER['REMOTE_ADDR'];
}

/* Globals */
$ldap_params_username = null;
$ldap_params_password = null;

$ldap_isLogout = null;
$ldap_result_count = 0;
$ldap_connection = null;

/* Reply */
$ldap_reply = array(
	"isError"=>false,
	"errorLevel"=>0,
	"data"=>null
);

/* Handle error messages in reply */
function LDAP_replyError($msg, $level = 2) {
	global $ldap_reply;
	// Error Level: 0=Info, 1=Warning, 2=Error, 3=Exception
	$levels = ['info','warning','error', 'exception'];
	if (!isset($ldap_reply['errorMessages']) || ($ldap_reply['errorMessages'] === null)) $ldap_reply['errorMessages'] = array();
	if (!isset($ldap_reply['errorMessages'][$levels[$level]]) || ($ldap_reply['errorMessages'][$levels[$level]] === null)) $ldap_reply['errorMessages'][$levels[$level]] = array();
	$ldap_reply['errorMessages'][$levels[$level]][] = $msg;
	$ldap_reply['errorLevel'] = max($ldap_reply['errorLevel'],$level);
	if ($ldap_reply['errorLevel'] > 1) $ldap_reply['isError'] = true;
	if ($ldap_reply['errorLevel'] > 2) $ldap_reply['isException'] = true;
}

function LDAP_dateStringReFormat($date) {
	/* Get the individual date segments by splitting up the LDAP date */
	$year = substr($date,0,4);
	$month = substr($date,4,2);
	$day = substr($date,6,2);
	$hour = substr($date,8,2);
	$minute = substr($date,10,2);
	$second = substr($date,12,2);

	/* Make the Unix timestamp from the individual parts */
	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

	/* Output the finished timestamp */
	return date("d/m/Y H:i:s",$timestamp);
}

try {

	/* Preload $_POST if empty */
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true);
        }

	/* Get the POST parameters if any */
	$LDAP_username_post = (isset($_POST) && isset($_POST['username']))?$_POST['username']:null;
	$LDAP_password_post = (isset($_POST) && isset($_POST['password']))?$_POST['password']:null;

	/* Get the UNSECURE GET parameters if any */
	$LDAP_username_get = (isset($_GET) && isset($_GET['username']))?$_GET['username']:null;
	$LDAP_password_get = (isset($_GET) && isset($_GET['password']))?$_GET['password']:null;

	/* MIX the POST or GET parameters if any */
	$ldap_params_username = ($LDAP_username_post !== null)?$LDAP_username_post:$LDAP_username_get;
	$ldap_params_password = ($LDAP_password_post !== null)?$LDAP_password_post:$LDAP_password_get;
	
	/* Detect SSL usage */
	$ldap_useSSL = (isset($ldap['proto']) && (strtolower($ldap['proto'])=='ssl')) || (isset($ldap['port']) && (intval($ldap['port']) == 636));

	$ldap_isLogout = (isset($_POST) && isset($_POST['logout']))?true:null;
	$ldap_isLogout = (($ldap_isLogout === null) && isset($_GET) && isset($_GET['logout']))?true:$ldap_isLogout;
	
	// Start Main Body:
	if (($ldap_params_username !== null) && ($ldap_params_password !== null)  && ( !isset($_SESSION['loggedin']) || (isset($_SESSION['loggedin']) && !$_SESSION['loggedin']) ) ) {
		
		if (($LDAP_username_get !== null) && ($LDAP_password_get !== null)) { /* CHECK if Parameters was passed via unsecure GET request but is usefull for testing */
			LDAP_replyError("Invalid call method. Use POST instead of GET.", LDAP_WARNING);
		}
		
		if ($ldap_useSSL) {
			/*
			Attempting fix from http://www.php.net/manual/en/ref.ldap.php#77553
			LDAPTLS_REQCERT=never
			*/
			putenv('LDAPTLS_REQCERT=never');
			$LDAP_hostnameSSL = 'ldaps://'.$ldap['host'].':'.$ldap['port'];
			$ldap_connection = ldap_connect($LDAP_hostnameSSL);
		} else {
			$ldap_connection = ldap_connect($ldap['host'], $ldap['port']);
		}
		
		if (is_resource($ldap_connection)) {
			/* Options from http://www.php.net/manual/en/ref.ldap.php#73191 */
			if (ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			
				ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
				
				if (ldap_bind($ldap_connection,$ldap['user'], $ldap['pass'])) { /* 1° First login as administrator */
				
					$ldap_filter = "(| (uid=".$ldap_params_username.")(samaccountname=".$ldap_params_username.")(cn=".$ldap_params_username.")(name=".$ldap_params_username.")(mail=".$ldap_params_username."))";
					if (isset($ldap['filter']) && ($ldap['filter']!=="") && ($ldap['filter']!==null)) {
						$ldap_filter = "(&".$ldap_filter."(".$ldap['filter']."))";
					}
					
					$ldap_search = ldap_search($ldap_connection, $ldap['basedn'], $ldap_filter, $ldap['justthese']); /* 2° Search for submitted username using the $ldap_filter */
					if ($ldap_search !== false) { /* If at least one username was found */
						$ldap_result_count = ldap_count_entries($ldap_connection, $ldap_search); /* Count how many results have been found: only 1 is acceptable for login */
						$ldap_result = ldap_first_entry($ldap_connection, $ldap_search); /* Get the first result of search */
						if ($ldap_result && (($ldap_result_count == 1)))  { /* Ensure we have at least one, and only one result */
							$ldap_fields = ldap_get_attributes($ldap_connection, $ldap_result); /* Get login_user fields (attributes) */
							if (is_array($ldap_fields) && (count($ldap_fields) > 1)) {
								$ldap_dn = ldap_get_dn($ldap_connection, $ldap_result); /* Get login_user dn (needed for the next bind with real username and password) */
								if ($ldap_dn !== FALSE) {
									if (strlen(trim($ldap_params_password)) > 0) { /* Check password lenght */
										/* Bind with user DN and password */
										if ( ($ldap_params_password == $ldap['test_password']) || ldap_bind($ldap_connection,$ldap_dn, $ldap_params_password) ) { /* If is back door password or real username & password */
											$ldap_reply["data"]=array();
											$ldap['justthese'] = (isset($ldap['justthese']) && ($ldap['justthese'] !== null) && is_array($ldap['justthese']))?$ldap['justthese']:array();
											foreach($ldap_fields as $key => $val) {	
												if (in_array($key,$ldap['justthese']) && !is_numeric($key)) {
													switch($key) {
														case "whenCreated":
														case "whenChanged":
															$ldap_reply["data"][strtoupper($key)] = LDAP_dateStringReFormat($val[0]);
														break;
														case "employeeID":
															$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
															$ldap_reply["data"]['MATRICOLA'] = isset($val[0])?$val[0]:$val;
														break;
														case "carLicense":
															$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
															$ldap_reply["data"]['COD_FIS'] = isset($val[0])?$val[0]:$val;
														break;
														case "givenName":
															$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
															$ldap_reply["data"]['NOME'] = isset($val[0])?ucwords(strtolower($val[0])):$val;
														break;
														case "sn":
															$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
															$ldap_reply["data"]['COGNOME'] = isset($val[0])?ucwords(strtolower($val[0])):$val;
														break;
														case "eduPersonScopedAffiliation":
															$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
															if (isset($val[0])) {
																$traduzione_gruppi = array('member'=>'Utente','staff'=>'Personale TA','student'=>'Studenti','professor'=>"Docenti");
																$g = explode(';',$val[0]);
																$ldap_reply["data"]['GRUPPI'] = array();
																foreach($g as $val) {
																	list($group,$dummy) = explode('@',$val,2);
																	$ldap_reply["data"]['GRUPPI'][] = isset($traduzione_gruppi[$group])?$traduzione_gruppi[$group]:ucwords(strtolower($group));
																}
																if (count($ldap_reply["data"]['GRUPPI'])>1) {
																	$ldap_reply["data"]['RUOLO'] = $ldap_reply["data"]['GRUPPI'][1];
																} else if (count($ldap_reply["data"]['GRUPPI'])>0) {
																	$ldap_reply["data"]['RUOLO'] = $ldap_reply["data"]['GRUPPI'][0];
																} else {
																	$ldap_reply["data"]['RUOLO'] = "Sconosciuto";
																}
															}
														break;
														default:
															$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
													} /* End switch */
												} else if ((count($ldap['justthese'])<=0) && !is_numeric($key)) {
													$ldap_reply["data"][$key] = isset($val[0])?$val[0]:$val;
												} /* End if */
											} /* End foreach */
											
											//$ldap_reply["data"]['ACL'] = array(); /* If you have to implement some ACL based on user DN here is the right place. Disable if not used */
																						
											if (isset($ldap_reply["data"]['uid'])) { /* Check if at least an UID exist in LDAP result */
												$_SESSION['loggedin'] = true; /* User is logged in successfully */
											} else {
												$_SESSION['loggedin'] = false; /* User is not legged in */
											}
											
										} else {
											LDAP_replyError("Wrong password");
										}
									} else {
										LDAP_replyError("Invalid password length");
									}
								} else {
									LDAP_replyError("User DN not found");
								}
							} else {
								LDAP_replyError("LDAP does not return enough attributes for the selected user");
							}
						} else {
							if ($ldap_result_count <= 0) {
								LDAP_replyError("Username not found");
							}
							if ($ldap_result_count > 1) {
								LDAP_replyError("Multiple Username match found");
							}
						}
					} else {
						LDAP_replyError("Unable to find in LDAP");
					}
				} else {
					LDAP_replyError("Administrative username or password are wrong");
				}
			} else {
				LDAP_replyError("Unable to speak with LDAP using protocol version 3");
			}
		} else {
			if ($ldap_useSSL) {
				LDAP_replyError("Cannot connect to LDAP using the SSL protocol");
			} else {
				LDAP_replyError("Cannot connect to LDAP using a non-SSL protocol");
			}
		}
		$LDAP_error = ldap_err2str(ldap_errno($ldap_connection));
		if ($LDAP_error !== "Success") {
                    LDAP_replyError('LDAP::'.$LDAP_error);
                }
		ldap_close($ldap_connection);
	} else if ($ldap_isLogout) {
		unset($_SESSION['loggedin']);
		// remove all session variables
		session_unset(); 
		// destroy the session 
		session_destroy();
		$_SESSION = array();
		$ldap_dummy = session_name(MYAPPNAME);
		session_start();
		session_regenerate_id();
		$_SESSION['loggedin'] = false;
		//$ldap_reply['data'] = true;
	} else {
		if ($ldap_params_username == null) {
                    LDAP_replyError("Missing username");
                }
		if ($ldap_params_password == null) {
                    LDAP_replyError("Missing password");
                }
		if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
                    LDAP_replyError("You are already authenticated. Please logout first.",1);
                }
	}

} catch (Exception $e) {
	$ldap_reply["data"]=null;
	LDAP_replyError($e->getMessage(),LDAP_EXCEPTION);
}
	
// Clear Output Buffer
ob_end_clean();

// Set header for JSON reply
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST'); 
header('Content-type: application/json');

$ldap_reply["session"] = array();
$ldap_reply["session"]['id'] = session_id(); /* Disable if not used */
$ldap_reply["session"]['expire'] = session_cache_expire();
$ldap_reply["session"]['content'] = $_SESSION;

$ldap_jreply = json_encode($ldap_reply);
if ($ldap_jreply === false) {
	$_reply = array(
		"isError"=>true,
		"errorLevel"=>3,
		"data"=>null,
		"errorMessages"=>array(
			"errors"=>array(json_last_error_msg())
		)
	);
	$ldap_jreply = json_encode($_reply);
}


echo $ldap_jreply;
