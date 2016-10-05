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
        'cache' => false#For delevelopment.
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
    $pdo->query("SET NAMES UTF8");
    return $pdo;
};
$GLOBALS['webinfo'] = array();
$result = $container->db->query("SELECT config,value FROM configs")->fetchAll();
foreach ($result as $conf) {
    $GLOBALS['webinfo'][$conf['config']] = $conf['value'];
}


// controllers
require 'src/controllers/home.php';
require 'src/controllers/manage.php';

$app->get('/test', '\HomeAction:test');
$app->post('/exam', '\HomeAction:exam');
$app->get('/manage', '\ManageAction:report');
$app->get('/manage/logout', '\ManageAction:logout');
$app->get('/manage/report/{id:[\d]+}', '\ManageAction:reportDetail');
$app->get('/manage/{params:.*}', '\ManageAction:report');
$app->post('/manage', '\ManageAction:login');
$app->post('/manage/{params:.*}', '\ManageAction:login');
$app->get('/{params:.*}', '\HomeAction:homepage');
$app->post('/{params:.*}', '\HomeAction:register');


$app->run();
