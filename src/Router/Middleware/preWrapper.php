<?php

namespace App\Router\Middleware;

function preWrapper (
	\App\Core\Request $req,
	\App\Core\Response $res,
	callable $next,
) {
	$res->addMethod('pre', function (mixed $body = null): self {
		if ($body !== null) {
			$this->body = $body;
		} else {
			$this->body = ob_get_contents();
		}
		$this->body = '<pre>' . $this->body . '</pre>';
		$this->send('text/html');
		return $this;
	});

	$next($req, $res);
};

