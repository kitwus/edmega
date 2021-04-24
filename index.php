<?php
ini_set('max_execution_time', 0);
ini_set('display_startup_errors', 'On');
ini_set('display_errors', 'On');
error_reporting(E_ALL);


include __DIR__ . '/vendor/autoload.php';
include dirname(__DIR__) . '/wp-load.php';


$client = new Raven_Client('https://7bd19e32985e405a81f17e77b80ddc33:8c07a900faed4ee18c887d96fa951a2a@sentry.io/1203224');

$client->install();

try {

    $parser = new \Anonym\Parser();
    $insert = new \Anonym\WpPostInsert();
    $metaInserter = new \Anonym\PostmetaInsert();
    $sold = new \Anonym\Sold();


    $parser->getFile('data.xml');

    $properties = $parser->parse(__DIR__ . '/data.xml');

    $ids = $parser->getIds();

    $sold->makeSoldItems($ids);
    $parser->saveIds($ids);
    
    $i=0;
    foreach ($properties as $property) {
        $i++;
        if($i==3) break;
        $added = $insert->insert($property);

        echo print_r($property);

        if ($added['already_added'] === true) {
            continue;
        }

        $property['images'] = $added['images'];
        $metaInserter->insert($added['post_id'], $property);
    }


    echo sprintf('%d içerik işlendi', count($ids));

} catch (Exception $e) {
    echo $e->getMessage();
}





