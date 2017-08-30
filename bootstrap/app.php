<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

$app = new Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
	    'db' => [
	    	'driver' => 'mysql',
	    	'host'	=>	'localhost',
	    	'database'	=> 's3_skeleton',
	    	'username'	=> 'root',
	    	'password'	=> 'mysql',
	    	'charset'	=> 'utf8',
	    	'collation' => 'utf8_unicode_ci',
	    	'prefix'	=> ''
	    ]
    ]
]);

$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function($container) use($capsule) {
	return $capsule;
};

$container['auth'] = function($container){
	return new \App\Auth\Auth;
};

// Twig
$container['view'] = function ($container) {
	$view = new Slim\Views\Twig(__DIR__ . '/../resources/views', [
		'debug' => true,
		'cache' => false,
	]);
	$uri = explode("/",$container['request']->getUri());
	$basePath = $uri[0]."//".$uri[2]."/public";
	$view->addExtension( new \Slim\Views\TwigExtension(
		$container->router,
		$basePath,
		$container->request->getUri()
	));
	$view->addExtension(new \Twig_Extension_Debug());
	$view['baseUrl'] = $basePath;
	if(isset($_SESSION['user'])){
		$view->getEnvironment()->addGlobal('auth', [
			'check'	=> $container->auth->check(),
			'user'	=> $container->auth->user(),
		]);	
	}	
	return $view;
};

$container['HomeController'] = function($container){
	return new \App\Controller\HomeController($container);
};

$container['AuthController'] = function($container){
	return new \App\Controller\Auth\AuthController($container);
};


require_once __DIR__ . '/../app/routes.php';
