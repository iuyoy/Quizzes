<?php
session_start();

require 'src/vendor/autoload.php';
require 'src/public/config.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App(["settings" => $config]);

$container = $app->getContainer();
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('src/views', [
        #'cache' => 'src/cache'
        'cache' => false
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    return $view;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};
$GLOBALS['webinfo'] = array();
$result = $container->db->query("SELECT config,value FROM configs")->fetchAll();
foreach ($result as $conf) {
    $GLOBALS['webinfo'][$conf['config']] = $conf['value'];
}


// controllers
require 'src/controllers/home.php';

$app->get('/test', '\HomeAction:test');
$app->post('/exam', '\HomeAction:exam');
$app->get('/{params:.*}', '\HomeAction:homepage');
$app->post('/{params:.*}', '\HomeAction:register');

$app->run();
