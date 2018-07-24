<?php

require_once '../../vendor/autoload.php';

const IKEY = '';
const SKEY = '';
const AKEY = '';
const HOST = '';

$template = <<<'EOD'
<html>
  <head>
    <style>
      #duo_iframe {
        width: 100%%;
        min-width: 304px;
        max-width: 620px;
        height: 330px;
        border: none;
      }
    </style>
  </head>
  <body>
    <form method="post" id="duo_form"></form>
    <iframe id="duo_iframe"
            data-host="%s"
            data-init-txid="%s"
            data-post-action="/">
    </iframe>
    <script src="Duo-Web-v3.js"></script>
  </body>
</html>
EOD;

$client = new \DuoAPI\Frame(
    IKEY,
    SKEY,
    HOST,
    null,
    true,
    \DuoAPI\SIGNATURE_CANON_JSON_STRING_BODY
);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!array_key_exists('username', $_GET)) {
        die("Please include a 'username' parameter.");
    }

    $username = $_GET['username'];
    $txid = \Duo\Web::initAuth($client, IKEY, AKEY, $username);

    $html = sprintf($template, HOST, $txid);
    echo $html;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!array_key_exists('response_txid', $_POST)) {
        die("Malformed response from Duo service.");
    }

    $response_txid = $_POST['response_txid'];
    $username = \Duo\Web::verifyAuth($client, IKEY, AKEY, $response_txid);

    if ($username === null) {
        echo 'Unsuccessful authentication';
    } else {
        echo 'Successful authentication with ' . $username;
    }
} else {
    throw new Exception('Unknown request method: ' . $_SERVER['REQUEST_METHOD']);
}
