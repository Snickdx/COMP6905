<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

const DEFAULT_URL = 'https://comp6950a2.firebaseio.com';
const DEFAULT_TOKEN = 'l97gPC93474K9eKvK6dLaw9HRerFjYmuc4dzxS4U';
const DEFAULT_PATH = '/';
const AZURE_STORAGE_KEY = 'G4VL0eFtlmXMxajh3gqwidvjR1+v5CIfPn6SaR+jKcahpcdTioqx+O60XJwj33GJ2hjSM/VThE54JW0GA2rnJQ==';
const AZURE_ACCOUNT = 'snickdxstore';


use MicrosoftAzure\Storage\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Table\Models\Entity;
use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Table\Models\EdmType;

$connectionString = 'DefaultEndpointsProtocol=https;AccountName='.AZURE_ACCOUNT.';AccountKey='.AZURE_STORAGE_KEY;
$tableClient = ServicesBuilder::getInstance()->createTableService($connectionString);

function insertEntity($tableClient, $data, $action)
{
    $entity = new Entity();
    $entity->setPartitionKey($data['user']);
    $entity->setRowKey(time()."");
    $entity->addProperty("user", EdmType::STRING, $data['user']);
    $entity->addProperty("time", EdmType::STRING, $data['time']."");
    $entity->addProperty("company", EdmType::STRING, $data['company']);
    $entity->addProperty("action", EdmType::STRING, $action);

    try{
        $tableClient->insertEntity("EventStream", $entity);
    } catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }
}

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

$app->get('/', function () {
    echo 'Hi :)';
});

$app->post('/sendrequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

    $data = [
        "company" => $_POST['company'],
        "time" => intval($_POST['time']),
        "user" => $_POST['user']
    ];

    $firebase->push("/requests/sent", $data);
    $connectionString = 'DefaultEndpointsProtocol=https;AccountName='.AZURE_ACCOUNT.';AccountKey='.AZURE_STORAGE_KEY;
    $tableClient = ServicesBuilder::getInstance()->createTableService($connectionString);
    insertEntity($tableClient, $data, "request");
});

$app->post('/deleterequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);
     $data = $firebase->get("requests/sent/".$_POST['key']);
     $firebase->delete("requests/sent/".$_POST['key']);
     $firebase->set("requests/deleted/".$_POST['key'], $data);
    $data = json_decode($data);
    $connectionString = 'DefaultEndpointsProtocol=https;AccountName='.AZURE_ACCOUNT.';AccountKey='.AZURE_STORAGE_KEY;
    $tableClient = ServicesBuilder::getInstance()->createTableService($connectionString);
    insertEntity($tableClient, ['user'=>$data->user, 'company'=>$data->company, 'time' => $data->time, 'key'=> $_POST['key']], "delete");

});

$app->get('/playbackStream', function(){
    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);
    $filter = "RowKey ne '3'";
    $connectionString = 'DefaultEndpointsProtocol=https;AccountName='.AZURE_ACCOUNT.';AccountKey='.AZURE_STORAGE_KEY;
    $tableClient = ServicesBuilder::getInstance()->createTableService($connectionString);

    try {
        $result = $tableClient->queryEntities("EventStream", $filter);
    } catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }

    $entities = $result->getEntities();

    foreach($entities as $entity){
//        $data = [
//            "company" => $entity->company,
//            "time" => intval($entity->time),
//            "user" => $entity->user
//        ];
        echo print_r($entity);
//        switch($entity->action){
//            case "request":
//                $firebase->push("/requests/sent", $data);
//                break;
//            case "delete":
//                $firebase->delete("requests/sent/".$data->key);
//                $firebase->set("requests/deleted/".$data->key, $data);
//                break;
//            default: echo "diff case";
//        }
    }
});

$app->get('/getstream', function(){

    $filter = "RowKey ne '3'";
    $connectionString = 'DefaultEndpointsProtocol=https;AccountName='.AZURE_ACCOUNT.';AccountKey='.AZURE_STORAGE_KEY;
    $tableClient = ServicesBuilder::getInstance()->createTableService($connectionString);

    try {
        $result = $tableClient->queryEntities("EventStream", $filter);
    } catch(ServiceException $e){
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }

    $entities = $result->getEntities();

    var_dump($entities);

//    foreach($entities as $entity){
//    }
});

$app->post('/acceptrequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

    $data = $firebase->get("requests/sent/".$_POST['key']);
    $firebase->delete("requests/sent/".$_POST['key']);
    $firebase->set("requests/accepted/".$_POST['key'], $data);
});

$app->run();