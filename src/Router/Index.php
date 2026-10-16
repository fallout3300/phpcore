<?php

namespace App\Router;

use App\Core\Router;

use function App\Router\Middleware\json;
use function App\Router\Middleware\preWrapper;

$router = new Router();

$router->use('*', json(...));
$router->use('*', preWrapper(...));

$router->get(
	'/',
	function ($req, $res, $next) {
		$res->json($req);
	},
);

$router->get(
	'html',
	function ($req, $res, $next) {
		$res->html('<h1>HTML</h1>');
	},
);

$router->get(
	'json',
	function ($req, $res, $next) {
		$res->json([
			'some' => 'data',
			'русский' => 'текст',
			'date' => new \DateTime(),
			'smile' => '😀',
			'data' => $req->body,
		]);
	},
);

$router->post(
	'/',
	/* preWrapper(...), */
	function ($req, $res) {
		$res->json($req);
	},
)->description('returns request data');

$router->get('routes', function ($req, $res) {
	global $router;
	$routes = $router->getRoutes();
	$final = [];

	/** @var 'path' | 'method' | 'raw' $sort */
	$sort = $req->query['sort'] ?? 'path';

	if ($sort === 'path') {
		foreach ($routes as $path => $route) {
			$rawMethods = array_keys($route);
			$methods = [];

			foreach ($rawMethods as $key => $method) {
				$methods[$method] = $route[$method]->description ?? '-';
			}

			$final[$path] = $methods;
		}
	} elseif ($sort === 'method') {
		foreach ($routes as $path => $route) {
			$methods = array_keys($route);
			foreach ($methods as $method) {
				$final[$method][$path] = $route[$method]->description ?? '-';
			}
		}
	} elseif ($sort === 'raw') {
		$final = $routes;
	}

	/* $res->text('test'); */
	$res->json($final);
});

$router->post('cookie', function ($req, $res) {
	$res->cookie('name', 'value');
	$res->json('ok');
});

$router->get('cookie', function ($req, $res) {
	$res->json($req->cookies);
});

$router->get('*', function ($req, $res) {
	$res->status(404)->json([ 'status' => 'not found' ]);
	/* $res */
	/* 	->status(302) */
	/* 	->setHeader('Location', 'routes') */
	/* 	->text('redirect') */
	;
})->description('redirect to routes page');

$router->listen();
