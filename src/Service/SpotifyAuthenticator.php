<?php

namespace App\Service;

use App\Objects\SpotifyAuthCredentials;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyAuthenticator
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, HttpClientInterface $httpClient)
    {
        $this->client = $httpClient->withOptions(['base_uri' => 'https://accounts.spotify.com']);
        $this->logger = $logger;
    }

    /**
     * Returns an auth link for start the OAuth process
     *
     * @param SpotifyAuthCredentials $credentials Spotify auth credentials
     * @param string $redirectUri uri to redirect to
     * @return string auth link
     */
    public function authLink(SpotifyAuthCredentials $credentials, string $redirectUri): string
    {
        $baseUrl = 'https://accounts.spotify.com/authorize';
        $params = [
            'response_type' => 'code',
            'client_id' => $credentials->clientId(),
            'scope' => 'user-read-currently-playing user-read-email',
            'redirect_uri' => $redirectUri
        ];
        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Exchanges auth code with spotify for an access token
     *
     * @param SpotifyAuthCredentials $credentials Spotify auth credentials
     * @param string $code Spotify auth code
     * @param string $redirectUri uri to redirect to (only for verification)
     * @return array Access token response
     */
    public function exchangeCode(SpotifyAuthCredentials $credentials, string $code, string $redirectUri): array
    {
        $base64Credentials = base64_encode($credentials->clientId() . ':' . $credentials->clientSecret());
        $response = $this->client->request(
            'POST',
            '/api/token',
            [
                /* auth_basic could be used, but doing auth manually it's easier to read if
                you don't know how the HTTPClient Class works. */
                'headers' => [
                    'Authorization' => 'Basic ' . $base64Credentials,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri
                ]
            ]
        );

        if ($response->getStatusCode() > 299 || $response->getContent() === '') {
            $this->logger->error($response->getContent(false));
            return [];
        }

        return $response->toArray();
    }

    /**
     * Renews the Spotify token
     *
     * @param SpotifyAuthCredentials $credentials Spotify auth credentials
     * @param string $refreshToken Spotify refresh token
     * @return array Access token response
     */
    public function renewToken(SpotifyAuthCredentials $credentials, string $refreshToken): array
    {
        $base64Credentials = base64_encode($credentials->clientId() . ':' . $credentials->clientSecret());
        $response = $this->client->request(
            'POST',
            '/api/token',
            [
                /* auth_basic could be used, but doing auth manually it's easier to read if
                you don't know how the HTTPClient Class works. */
                'headers' => [
                    'Authorization' => 'Basic ' . $base64Credentials
                ],
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken
                ]
            ]
        );

        if ($response->getStatusCode() > 299 || $response->getContent() === '') {
            $this->logger->error($response->getContent(false));
            return [];
        }

        return $response->toArray();
    }
}
