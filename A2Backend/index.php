<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';



const DEFAULT_URL = 'https://comp6950a2.firebaseio.com';
const DEFAULT_TOKEN = 'l97gPC93474K9eKvK6dLaw9HRerFjYmuc4dzxS4U';
const DEFAULT_PATH = '/';



$app = new \Slim\Slim();

$app->add(new \CorsSlim\CorsSlim());

$app->config('debug', true);

$corsOptions = array(
    "origin" => "*",
    "maxAge" => 1728000,
    "allowCredentials" => False,
    "allowMethods" => array("POST, GET"),
    "allowHeaders" => array("X-PINGOTHER")
);

$cors = new \CorsSlim\CorsSlim($corsOptions);

$app->get('/hello/:name', function ($name) {
    echo "Hello, " . $name;
});

$app->get('/requests', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

    $data = $firebase->get('/requests');

    echo json_encode($data);
});

$app->post('/sendrequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

    $firebase->push("/requests/sent", [
        "company" => $_POST['company'],
        "time" => intval($_POST['time']),
        "user" => $_POST['user']
    ]);
});

$app->post('/deleterequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);
     $data = $firebase->get("requests/sent/".$_POST['key']);
     $firebase->delete("requests/sent/".$_POST['key']);
     $firebase->set("requests/deleted/".$_POST['key'], $data);


});

$app->post('/acceptrequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

    $data = $firebase->get("requests/sent/".$_POST['key']);
    $firebase->delete("requests/sent/".$_POST['key']);
    $firebase->set("requests/accepted/".$_POST['key'], $data);
});

$app->run();

?>