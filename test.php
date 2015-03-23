<?php

require_once("duo_web.php");

const IKEY = "DIXXXXXXXXXXXXXXXXXX";
const WRONG_IKEY = "DIXXXXXXXXXXXXXXXXXY";
const SKEY = "deadbeefdeadbeefdeadbeefdeadbeefdeadbeef";
const AKEY = "useacustomerprovidedapplicationsecretkey";

const USER = "testuser";

const INVALID_RESPONSE = "AUTH|INVALID|SIG";
const EXPIRED_RESPONSE = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTMwMDE1Nzg3NA==|cb8f4d60ec7c261394cd5ee5a17e46ca7440d702";
const FUTURE_RESPONSE = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0Mw==|d20ad0d1e62d84b00a3e74ec201a5917e77b6aef";
const WRONG_PARAMS_RESPONSE = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0M3xpbnZhbGlkZXh0cmFkYXRh|6cdbec0fbfa0d3f335c76b0786a4a18eac6cdca7";
const WRONG_PARAMS_APP = "APP|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0M3xpbnZhbGlkZXh0cmFkYXRh|7c2065ea122d028b03ef0295a4b4c5521823b9b5";

/************************************************************/

$request_sig = Duo::signRequest(IKEY, SKEY, AKEY, USER);
assert($request_sig != null);

$request_sig = Duo::signRequest(IKEY, SKEY, AKEY, "");
assert($request_sig == Duo::ERR_USER);

$request_sig = Duo::signRequest(IKEY, SKEY, AKEY, "in|valid");
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

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, FUTURE_RESPONSE . ":" . WRONG_PARAMS_APP);
assert($user == null);

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, WRONG_PARAMS_RESPONSE . ":" . $valid_app_sig);
assert($user == null);

$user = Duo::verifyResponse(WRONG_IKEY, SKEY, AKEY, FUTURE_RESPONSE . ":" . $valid_app_sig);
assert($user == null);

$user = Duo::verifyResponse(IKEY, SKEY, AKEY, FUTURE_RESPONSE . ":" . $valid_app_sig);
assert($user === USER);

?>
