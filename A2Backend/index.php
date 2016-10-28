<?php

require 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->get('/hello/:name', function ($name) {
    echo "Hello, " . $name;
});

$app->get('/getView', function () {
    $view = [
        [
            "company" => "Hugh Jass Co.",
            "time" => 1477670341
        ],
        [
            "company" => "Harambe Ltd.",
            "time" => 1477670341
        ],
        [
            "company" => "Hugh Jass Co.",
            "time" => 1477670341
        ]
    ];


    echo json_encode($view);
});


$app->run();