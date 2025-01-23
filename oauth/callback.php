<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Dotenv\Dotenv;

include './../vendor/autoload.php';

function exchangeCode($data, $apiUrl) {
    $client = new Client();

    try {
        $response = $client->post($apiUrl, [
            'form_params' => $data,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        if($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents());
        }
        return false;
    }
    catch(RequestException $e) {
        return false;
    }
}

if(isset($_GET['error']) || !isset($_GET['code'])) {
    echo 'Some error occurred';
    exit();
}

$authCode = $_GET['code'];

$dotenv = Dotenv::createImmutable(__DIR__. '/..');
$dotenv->load();

/**
 * let's exchange the code for an access token
 * for that we need to send a request to GitHub
 * PHP supports curl by default, by it's verbose - so let's use Guzzle
 */

$data = [
    'client_id' => $_ENV['GITHUB_CLIENT_KEY'],
    'client_secret' => $_ENV['GITHUB_CLIENT_SECRET'],
    'code' => $authCode,
];

$apiUrl = "https://github.com/login/oauth/access_token";

$tokenData = exchangeCode($data, $apiUrl);

if($tokenData === false) {
    exit('Error getting token');
}

if(!empty($tokenData->error)) {
    exit($tokenData->error);
}

if(!empty($tokenData->access_token)) {
    setcookie('nxlog_access_token', $tokenData->access_token, time() + 600000, "/", "", false, true);
    // the last argument - true - sets it as an httponly cookie
    header('Location: protected.php');
    exit();
}