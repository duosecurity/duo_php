<?php
namespace Unit;

class DuoTest extends \PHPUnit_Framework_TestCase
{

    const IKEY = "DIXXXXXXXXXXXXXXXXXX";
    const SKEY = "deadbeefdeadbeefdeadbeefdeadbeefdeadbeef";
    const AKEY = "useacustomerprovidedapplicationsecretkey";
    const USER = "testuser";


    public function setUp()
    {
        $request_sig = \Duo\Web::signRequest(
            self::IKEY,
            self::SKEY,
            self::AKEY,
            self::USER
        );
        list($duo_sig, $valid_app_sig) = explode(':', $request_sig);

        $request_sig = \Duo\Web::signRequest(
            self::IKEY,
            self::SKEY,
            "invalidinvalidinvalidinvalidinvalidinvalid",
            self::USER
        );
        list($duo_sig, $invalid_app_sig) = explode(':', $request_sig);

        $this->valid_app_sig = $valid_app_sig;
        $this->invalid_app_sig = $invalid_app_sig;
        $this->valid_future_response = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0Mw==|d20ad0d1e62d84b00a3e74ec201a5917e77b6aef";
        $this->valid_future_blob = "APP|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTY5MDEyODUyNQ==|19a752347685a65be2dd3866582473d48d43825c38fe1eac672a4105c91343eb7d27b200d542a7102fb6576f5e8309500f9910bbd4c5d72ff0eaa3d99bbf4e96";

        $this->mocked_client = $this->getMockBuilder("\DuoAPI\Frame")
                                    ->setMethods(["init", "auth_response"])
                                    ->disableOriginalConstructor()
                                    ->getMock();
    }

    public function testNonNull()
    {
        $this->assertNotEquals(
            \Duo\Web::signRequest(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                self::USER
            ),
            null
        );
    }

    public function testEmptyUsername()
    {
        $this->assertEquals(
            \Duo\Web::signRequest(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                ""
            ),
            \Duo\Web::ERR_USER
        );
    }

    public function testExtraSeparator()
    {
        $this->assertEquals(
            \Duo\Web::signRequest(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                "in|valid"
            ),
            \Duo\Web::ERR_USER
        );
    }

    public function testInvalidIkey()
    {
        $this->assertEquals(
            \Duo\Web::signRequest(
                "invalid",
                self::SKEY,
                self::AKEY,
                self::USER
            ),
            \Duo\Web::ERR_IKEY
        );
    }

    public function testInvalidSkey()
    {
        $this->assertEquals(
            \Duo\Web::signRequest(
                self::IKEY,
                "invalid",
                self::AKEY,
                self::USER
            ),
            \Duo\Web::ERR_SKEY
        );
    }

    public function testInvalidAkey()
    {
        $this->assertEquals(
            \Duo\Web::signRequest(
                self::IKEY,
                self::SKEY,
                "invalid",
                self::USER
            ),
            \Duo\Web::ERR_AKEY
        );
    }

    public function testInvalidResponse()
    {
        $invalid_response = "AUTH|INVALID|SIG";
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                $invalid_response . ":" . $this->valid_app_sig
            ),
            null
        );
    }

    public function testExpiredResponse()
    {
        $expired_response = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTMwMDE1Nzg3NA==|cb8f4d60ec7c261394cd5ee5a17e46ca7440d702";
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                $expired_response . ":" . $this->valid_app_sig
            ),
            null
        );
    }

    public function testFutureResponse()
    {
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                $this->valid_future_response . ":" . $this->valid_app_sig
            ),
            self::USER
        );
    }

    public function testFutureInvalidResponse()
    {
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                $this->valid_future_response . ":" . $this->invalid_app_sig
            ),
            null
        );
    }

    public function testFutureInvalidParams()
    {
        $invalid_params = "APP|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0M3xpbnZhbGlkZXh0cmFkYXRh|7c2065ea122d028b03ef0295a4b4c5521823b9b5";
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                $this->valid_future_response . ":" . $invalid_params
            ),
            null
        );
    }

    public function testFutureInvalidResponseParams()
    {
        $invalid_response_params = "AUTH|dGVzdHVzZXJ8RElYWFhYWFhYWFhYWFhYWFhYWFh8MTYxNTcyNzI0M3xpbnZhbGlkZXh0cmFkYXRh|6cdbec0fbfa0d3f335c76b0786a4a18eac6cdca7";
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                self::IKEY,
                self::SKEY,
                self::AKEY,
                $invalid_response_params . ":" . $this->valid_app_sig
            ),
            null
        );
    }

    public function testFutureResponseInvalidIkey()
    {
        $wrong_ikey = "DIXXXXXXXXXXXXXXXXXY";
        $this->assertEquals(
            \Duo\Web::verifyResponse(
                $wrong_ikey,
                self::SKEY,
                self::AKEY,
                $this->valid_future_response . ":" . $this->valid_app_sig
            ),
            null
        );
    }

    public function testInitAuthEmptyUsername()
    {
        $this->assertEquals(
            \Duo\Web::initAuth(
                $this->mocked_client,
                self::IKEY,
                self::AKEY,
                ""
            ),
            \Duo\Web::ERR_USER
        );
    }

    public function testInitAuthExtraSeparator()
    {
        $this->assertEquals(
            \Duo\Web::initAuth(
                $this->mocked_client,
                self::IKEY,
                self::AKEY,
                "in|valid"
            ),
            \Duo\Web::ERR_USER
        );
    }

    public function testInitAuthInvalidIkey()
    {
        $this->assertEquals(
            \Duo\Web::initAuth(
                $this->mocked_client,
                "invalid",
                self::AKEY,
                self::USER
            ),
            \Duo\Web::ERR_IKEY
        );
    }

    public function testInitAuthInvalidAkey()
    {
        $this->assertEquals(
            \Duo\Web::initAuth(
                $this->mocked_client,
                self::IKEY,
                "invalid",
                self::USER
            ),
            \Duo\Web::ERR_AKEY
        );
    }

    public function testInitAuthSuccess()
    {
        $txid = "be869f64-d41e-4667-8c43-1105396fbaa0";
        $response = [
            "response" => [
                "response" => [
                    "txid" => $txid,
                ],
                "stat" => "OK",
            ],
            "success" => true,
            "http_status_code" => 200 ,
        ];

        $this->mocked_client->method("init")
                            ->willReturn($response);

        $this->assertEquals(
            \Duo\Web::initAuth(
                $this->mocked_client,
                self::IKEY,
                self::AKEY,
                self::USER
            ),
            $txid
        );
    }

    public function testVerifyAuthSuccess()
    {
        $response = [
            "response" => [
                "response" => [
                    "app_blob" => $this->valid_future_blob,
                    "client_version" => "duo_php/1.0.0",
                    "enroll_only" => false,
                    "expire" => 1690124925,
                    "ikey" => self::IKEY,
                    "uname" => self::USER,
                ],
                "stat" => "OK",
            ],
            "success" => true,
            "http_status_code" => 200,
        ];

        $this->mocked_client->method("auth_response")
                            ->willReturn($response);

        $this->assertEquals(
            \Duo\Web::verifyAuth(
                $this->mocked_client,
                self::IKEY,
                self::AKEY,
                "unused"
            ),
            self::USER
        );
    }

    public function testVerifyAuthInvalidUname()
    {
        $response = [
            "response" => [
                "response" => [
                    "app_blob" => $this->valid_future_blob,
                    "client_version" => "duo_php/1.0.0",
                    "enroll_only" => false,
                    "expire" => 1690124925,
                    "ikey" => self::IKEY,
                    "uname" => "invalid",
                ],
                "stat" => "OK",
            ],
            "success" => true,
            "http_status_code" => 200,
        ];

        $this->mocked_client->method("auth_response")
                            ->willReturn($response);

        $this->assertNull(
            \Duo\Web::verifyAuth(
                $this->mocked_client,
                self::IKEY,
                self::AKEY,
                "unused"
            )
        );
    }
}
