<?php

namespace App\Router\Middleware;

use App\Core\Request;
use App\Core\Response;

function json(Request $req, Response $res, callable $next) {
	if (isset($req->headers['Content-Type']) && $req->headers['Content-Type'] == 'application/json') {
		$json = json_decode($req->rawBody, true);
		if (!$json) {
			echo 'Invalid JSON';
			return $res->status(400)->send();
		}
		$req->body = $json;
	}

	$res->handlers[] = function ($body, $contentType): mixed {
		if (preg_match('/^application\/json/', $contentType) === 0) {
			return false;
		}
		$json = json_encode($body);

		if (!$json) {
			echo 'Invalid JSON from server';
			$this->status(500)->send();
		}

		return $json;
	};

	$res->addMethod('json', function (mixed $body = null): self {
		if ($body !== null) {
			$this->body = $body;
		}
		$this->send('application/json');
		return $this;
	});

	$next($req, $res);
};
