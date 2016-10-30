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
const AZURE_URL = 'https://snickdxstore.table.core.windows.net:443/EventStream?sv=2015-12-11&si=EventStream-15813D258AE&tn=eventstream&sig=4jhiVtkelDZyHVnUkFummSyJn14LLz6V1JpEdzuia8A%3D';
const AZURE_QUERY_STRING = '?sv=2015-12-11&si=EventStream-15813D258AE&tn=eventstream&sig=4jhiVtkelDZyHVnUkFummSyJn14LLz6V1JpEdzuia8A%3D';


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
    if($action == "delete")$entity->addProperty("key", EdmType::STRING, $data['key']);

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
    echo 'Delete Successful';

});

$app->post('/playbackStream', function(){
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
        $data = [
            "company" => $entity->getPropertyValue('company'),
            "time" => intval($entity->getPropertyValue('time')),
            "user" => $entity->getPropertyValue('user')
        ];
        switch($entity->getPropertyValue('action')){
            case "request":
                $firebase->push("/requests/sent", $data);
                break;
            case "delete":
                $firebase->delete("requests/sent/".$entity->getPropertyValue('key'));
                $firebase->set("requests/deleted/".$entity->getPropertyValue('key'), $data);
                break;
            default: echo "diff case";
        }
    }
});

$app->post('/destroyView', function(){
    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);
    $firebase->delete("requests/sent/");
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

    $arr = [];

    foreach($entities as $entity){
        $data = [
            "company" => $entity->getPropertyValue('company'),
            "time" => intval($entity->getPropertyValue('time')),
            "user" => $entity->getPropertyValue('user'),
            "action" => $entity->getPropertyValue('action')
        ];
        array_push($arr, $data);
    }

    echo json_encode($arr);
});

$app->post('/acceptrequest', function () {

    $firebase = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);

    $data = $firebase->get("requests/sent/".$_POST['key']);
    $firebase->delete("requests/sent/".$_POST['key']);
    $firebase->set("requests/accepted/".$_POST['key'], $data);
});

$app->run();