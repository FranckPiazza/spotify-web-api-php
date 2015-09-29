<?php
class SessionTest extends PHPUnit_Framework_TestCase
{
    private $clientID = 'b777292af0def22f9257991fc770b520';
    private $clientSecret = '6a0419f43d0aa93b2ae881429b6b9bc2';
    private $redirectURI = 'https://example.com/callback';
    private $refreshToken = '3692bfa45759a67d83aedf0045f6cb63';

    private function setupStub($expectedMethod, $expectedUri, $expectedParameters, $expectedHeaders, $expectedReturn)
    {
        $stub = $this->getMockBuilder('Request')
                ->setMethods(array('account'))
                ->getMock();

        $stub->expects($this->once())
                 ->method('account')
                 ->with(
                    $this->equalTo($expectedMethod),
                    $this->equalTo($expectedUri),
                    $this->equalTo($expectedParameters),
                    $this->equalTo($expectedHeaders)
                )
                ->willReturn($expectedReturn);

        return $stub;
    }

    public function testGetAuthorizeUrl()
    {
        $expected = sprintf('https://accounts.spotify.com/authorize/?client_id=%s&redirect_uri=%s&response_type=%s&show_dialog=false',
            $this->clientID,
            urlencode($this->redirectURI),
            'code'
        );

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $url = $session->getAuthorizeUrl();

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlScope()
    {
        $expected = sprintf('https://accounts.spotify.com/authorize/?client_id=%s&redirect_uri=%s&response_type=%s&scope=%s&show_dialog=false',
            $this->clientID,
            urlencode($this->redirectURI),
            'code',
            'user-read-email'
        );

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $url = $session->getAuthorizeUrl(array(
            'scope' => array('user-read-email'),
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlState()
    {
        $state = 'foobar';
        $expected = sprintf('https://accounts.spotify.com/authorize/?client_id=%s&redirect_uri=%s&response_type=%s&show_dialog=false&state=%s',
            $this->clientID,
            urlencode($this->redirectURI),
            'code',
            $state
        );

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $url = $session->getAuthorizeUrl(array(
            'state' => $state,
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetClientId()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->clientID;

        $session->setClientId($expected);

        $this->assertEquals($expected, $session->getClientId());
    }

    public function testGetClientSecret()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->clientSecret;

        $session->setClientSecret($expected);

        $this->assertEquals($expected, $session->getClientSecret());
    }

    public function testGetExpires()
    {
        $this->markTestSkipped('Skipped since we are going to change/remove this functionality anyway. See #20.');

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $session->requestCredentialsToken();

        $this->assertGreaterThan(0, $session->getExpires());
    }

    public function testGetRedirectUri()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->redirectURI;

        $session->setRedirectUri($expected);

        $this->assertEquals($expected, $session->getRedirectUri());
    }

    public function testGetRefreshToken()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->refreshToken;

        $session->setRefreshToken($expected);

        $this->assertEquals($expected, $session->getRefreshToken());
    }

    public function testRefreshAccessToken()
    {
        $expected = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => '',
        );

        $headers = array(
            'Authorization' => 'Basic Yjc3NzI5MmFmMGRlZjIyZjkyNTc5OTFmYzc3MGI1MjA6NmEwNDE5ZjQzZDBhYTkzYjJhZTg4MTQyOWI2YjliYzI=',
        );

        $return = array(
            'body' => get_fixture('refresh-token'),
        );

        $stub = $this->setupStub(
            'POST',
            '/api/token',
            $expected,
            $headers,
            $return
        );

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI, $stub);
        $session->refreshAccessToken();

        $this->assertNotEmpty($session->getAccessToken());
    }

    public function testRequestAccessToken()
    {
        $authorizationCode = 'd1e893a80f79d9ab5e7d322ed922da540964a63c';
        $expected = array(
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectURI,
        );

        $return = array(
            'body' => get_fixture('access-token'),
        );

        $stub = $this->setupStub(
            'POST',
            '/api/token',
            $expected,
            array(),
            $return
        );

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI, $stub);
        $result = $session->requestAccessToken($authorizationCode);

        $this->assertTrue($result);
        $this->assertNotEmpty($session->getAccessToken());
    }

    public function testRequestCredentialsToken()
    {
        $expected = array(
            'grant_type' => 'client_credentials',
            'scope' => 'user-read-email',
        );

        $headers = array(
            'Authorization' => 'Basic Yjc3NzI5MmFmMGRlZjIyZjkyNTc5OTFmYzc3MGI1MjA6NmEwNDE5ZjQzZDBhYTkzYjJhZTg4MTQyOWI2YjliYzI=',
        );

        $return = array(
            'body' => get_fixture('access-token'),
        );

        $stub = $this->setupStub(
            'POST',
            '/api/token',
            $expected,
            $headers,
            $return
        );

        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI, $stub);
        $result = $session->requestCredentialsToken(array('user-read-email'));

        $this->assertTrue($result);
        $this->assertNotEmpty($session->getAccessToken());
    }

    public function testSetClientId()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->clientID;

        $session->setClientId($expected);

        $this->assertEquals($expected, $session->getClientId());
    }

    public function testSetClientSecret()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->clientSecret;

        $session->setClientSecret($expected);

        $this->assertEquals($expected, $session->getClientSecret());
    }

    public function testSetRedirectUri()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->redirectURI;

        $session->setRedirectUri($expected);

        $this->assertEquals($expected, $session->getRedirectUri());
    }

    public function testSetRefreshToken()
    {
        $session = new SpotifyWebAPI\Session($this->clientID, $this->clientSecret, $this->redirectURI);
        $expected = $this->refreshToken;

        $session->setRefreshToken($expected);

        $this->assertEquals($expected, $session->getRefreshToken());
    }
}
