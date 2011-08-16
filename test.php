<?php

require_once("duo_web.php");

const IKEY = "DIXXXXXXXXXXXXXXXXXX";
const SKEY = "deadbeefdeadbeefdeadbeefdeadbeefdeadbeef";
const AKEY = "useacustomerprovidedapplicationsecretkey";

const USER = "testuser";

const INVALID_RESPONSE = "AUTH|INVALID|SIG";
const EXPIRED_RESPONSE = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTMwMDE1Nzg3NA==|cb8f4d60ec7c261394cd5ee5a17e46ca7440d702";
const FUTURE_RESPONSE = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0Mw==|d20ad0d1e62d84b00a3e74ec201a5917e77b6aef";

/************************************************************/

$request_sig = Duo::signRequest(IKEY, SKEY, AKEY, USER);
assert($request_sig != null);

$request_sig = Duo::signRequest(IKEY, SKEY, AKEY, "");
assert($request_sig == Duo::ERR_USER);

$request_sig = Duo::signRequest("invalid", SKEY, AKEY, USER);
assert($request_sig == Duo::ERR_IKEY);

$request_sig = Duo::signRequest(IKEY, "invalid", AKEY, USER);
assert($request_sig == Duo::ERR_SKEY);

$request_sig = Duo::signRequest(IKEY, SKEY, "invalid", USER);
assert($request_sig == Duo::ERR_AKEY);

/************************************************************/

$request_sig = Duo::signRequest(IKEY, SKEY, AKEY, USER);
list($duo_sig, $valid_app_sig) = explode(':', $request_sig);

$request_sig = Duo::signRequest(IKEY, SKEY, "invalidinvalidinvalidinvalidinvalidinvalid", USER);
list($duo_sig, $invalid_app_sig) = explode(':', $request_sig);

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, INVALID_RESPONSE . ":" . $valid_app_sig);
assert($user == null);

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, EXPIRED_RESPONSE . ":" . $valid_app_sig);
assert($user == null);

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, FUTURE_RESPONSE . ":" . $invalid_app_sig);
assert($user == null);

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, FUTURE_RESPONSE . ":" . $valid_app_sig);
assert($user == USER);

?>
