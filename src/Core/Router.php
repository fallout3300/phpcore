<?php

namespace App\Core;

class Route {
	public string $path;
	public string $method;
	public string $description;
	/** @var callable[] */
	public array $actions;

	/**
	 * @param callable(mixed $request, mixed $resolve, ?callable $next): void $actions
	 */
	public function __construct(string $method, string $path, callable ...$actions) {
		$this->method  = $method;
		$this->path    = $path;
		$this->actions = $actions;
	}

	public function description(string $description): self {
		$this->description = $description;
		return $this;
	}

	private int $actionIndex = 0;
	public function next(?Request $request = null, ?Response $resolve = null): void {
		$nextAction = $this->actions[$this->actionIndex++] ?? null;

		if (!$nextAction) {
			throw new \Exception('Next is not found');
		}

		$nextAction($request, $resolve, [$this, 'next']);
	}
}

class Router {
	/** @var Route[][] */
	private array $routes = [];
	private array $middlewares = [];
	public string $prefix = '/';

	public function __construct() {
	}

	/**
	 * Регистрация цепочки Middleware или экшенов для маршрута.
	 *
	 * @param callable(Request, Response, callable ...$actions): void
	 * 		...$actions Список вызываемых функций.
	 */
	public function get(string $path, callable ...$actions): Route {
		$method = 'GET';
		return $this->createRoute($method, $path, ...$actions);
	}

	/**
	 * Регистрация цепочки Middleware или экшенов для маршрута.
	 *
	 * @param callable(Request, Response): void ...$actions Список вызываемых функций.
	 */
	public function post(string $path, callable ...$actions): Route {
		$method = 'POST';
		return $this->createRoute($method, $path, ...$actions);
	}

	public function put(string $path, callable ...$actions): Route {
		$method = 'PUT';
		return $this->createRoute($method, $path, ...$actions);
	}

	public function any(string $path, callable ...$actions): array {
		return [
			$this->createRoute('GET', $path, ...$actions),
			$this->createRoute('POST', $path, ...$actions),
			$this->createRoute('PUT', $path, ...$actions),
			$this->createRoute('DELETE', $path, ...$actions),
		];
	}

	public function delete(string $path, callable ...$actions): Route {
		$method = 'DELETE';
		return $this->createRoute($method, $path, ...$actions);
	}

	public function createRoute(string $method, string $path, callable ...$actions): Route {

		foreach ($this->middlewares as $middleware) {
			[ 'path' => $midPath, 'callable' => $callable ] = $middleware;
			if ($midPath === '*' || preg_match('~^' . $midPath . '~u', $path)) {
				$actions = [$callable, ...$actions];
			}
		}

		return $this->routes[$path][$method] = new Route($method, $path, ...$actions);
	}

	public function use(string $path, callable $callable): self {
		$this->middlewares[] = [ 'path' => $path, 'callable' => $callable ];
		return $this;
	}

	public function run(
		string $path,
		string $method,
		Request $request = new Request(),
		Response $response = new Response(),
	): Response {
		$route = $this->routes[$path][$method] ?? null;

		if (!$route) {
			if ($this->routes['*'][$method]) {
				$route = $this->routes['*'][$method];
			} else {
				throw new \Exception("Route is not found: {$path} {$method};");
			}
		}

		ob_start();
		$route->next($request, $response);

		return $response;
	}

	public function listen(): self {
		$request = new Request();
		$method = $request->method;
		$path = $request->path;

		$response = $this->run($path, $method, $request);
		echo $response->rawBody;
		return $this;
	}

	public function getRoutes(): array {
		return $this->routes;
	}
}

function clearPath(string $path): string {
	$res     = preg_replace('~\?.*~u', '', $path);
	if ($res !== '/') {
		$res = preg_replace('~^\/~u', '', $res);
		$res = preg_replace('~\/$~u', '', $res);
	}
	return $res;
}

#[\AllowDynamicProperties]
class Request {
	public string $path;
	public string $method;
	public string $rawBody;
	public string $rawQuery;

	public array $query;
	public array $body;
	public array $headers;
	public array $cookies;
	public array $server;
	public array $rawFiles;
	/** @var \SplFileInfo[] */
	public array $files;

	public function __construct() {
		// Clear path
		$this->path     = clearPath($_SERVER['REQUEST_URI']);

		$this->method   = $_SERVER['REQUEST_METHOD'];

		$this->query    = $_GET;

		$this->body     = $_POST;
		$this->rawBody  = file_get_contents('php://input');
		$this->rawQuery = $_SERVER['QUERY_STRING'];

		$this->headers  = getallheaders();
		$this->cookies  = $_COOKIE;
		$this->rawFiles = $_FILES;
		$this->files    = array_map(fn ($file) => new \SplTempFileObject($file['tmp_name']), $_FILES);
		/* $this->server   = $_SERVER; */
	}
}


class Response {
	/** @var array<string, callable> */
	private array $dynamicMethods = [];
	public int $statusCode;
	public mixed $rawBody;
	public mixed $body;
	public array $headers;
	public string $charset;
	/**
	 * @var \Closure[] (string, string): mixed
	 */
	public array $handlers;
	public array $cookies;

	public function __construct() {
		$this->statusCode = 200;
		$this->headers    = [];
		$this->charset    = 'utf-8';
		$this->rawBody    = '';
		$this->body       = '';
		$this->cookies    = [];

		$this->handlers[] = function ($body) {
			return $this->body;
		};
	}

	public function close(): self {
		http_response_code($this->statusCode);
		foreach ($this->headers as $name => $value) {
			header("{$name}: {$value}");
		}

		if ($this->body == '') {
			$this->body = ob_get_clean();
		} else {
			ob_end_clean();
		}

		foreach (array_reverse($this->handlers) as $handler) {
			$res = $handler->call(
				$this,
				$this->body,
				$this->headers['Content-Type'],
			);

			if ($res !== false) {
				$this->rawBody = $res;
				break;
			}
		}

		foreach ($this->cookies as $name => $cookie) {
			setcookie(
				$name,
				$cookie['value'],
				[
					'expires'  => $cookie['expires'],
					'path'     => $cookie['path'],
					'domain'   => $cookie['domain'],
					'secure'   => $cookie['secure'],
					'httponly' => $cookie['httpOnly'],
					'samesite' => $cookie['sameSite'],
				],
			);
		}

		/* $this->body = $this->handler->call($this, $this->rawBody); */

		/* echo $this->rawBody; */
		return $this;
	}

	public function setContentType(string $value, ?string $charset = null): self {
		if ($charset) {
			$this->charset = $charset;
		}
		if (preg_match('~^(text|application)~u', $value)) {
			$value .= '; charset=' . $this->charset;
		}
		$this->headers['Content-Type'] = $value;
		return $this;
	}

	public function setHeader(string $name, string $value): self {
		$this->headers[$name] = $value;
		return $this;
	}

	public function send(string|null $contentType = 'text/html'): self {
		if (headers_sent()) {
			throw new \Exception('Headers already sent');
		}
		/* $this->headers */
		/* ['Content-Type'] = $contentType . '; charset=' . $this->charset; */

		$this->setContentType($contentType);

		$this->close();

		return $this;
	}

	public function text(mixed $body = null): self {
		if ($body !== null) {
			$this->body = $body;
		}
		$this->send('text/plain');
		return $this;
	}
	public function html(mixed $body = null): self {
		if ($body !== null) {
			$this->body = $body;
		}
		$this->send('text/html');
		return $this;
	}

	public function cookie(
		string $name,
		mixed $value,
		int $expires = 0,
		string $path = '/',
		string $domain = '',
		bool $secure = false,
		bool $httpOnly = false,
		string $sameSite = 'Lax',
	): self {
		$this->cookies[$name] = [
			'value'     => $value,
			'expires'   => $expires,
			'path'      => $path,
			'domain'    => $domain,
			'secure'    => $secure,
			'httpOnly'  => $httpOnly,
			'sameSite'  => $sameSite,
		];
		return $this;
	}

	public function redirect(string $url, int $statusCode = 302): self {
		return $this
			->status($statusCode)
			->setHeader('Location', $url)
			->send();
	}

	/**
	 * Устанавливает HTTP-статус ответа.
	 *
	 * @param int $statusCode HTTP-код ответа.
	 *        Допустимые значения:
	 *        - `200` : **OK** — Запрос успешно выполнен.
	 *        - `201` : **Created** — Ресурс успешно создан.
	 *        - `400` : **Bad Request** — Ошибка в синтаксисе запроса.
	 *        - `401` : **Unauthorized** — Требуется аутентификация.
	 *        - `403` : **Forbidden** — Сервер понял запрос, но доступ ограничен.
	 *        - `404` : **Not Found** — Страница или ресурс не найдены.
	 *        - `500` : **Internal Server Error** — Ошибка на стороне сервера.
	 *
	 * @return self
	 */
	public function status(int $statusCode): self {
		$this->statusCode = $statusCode;
		return $this;
	}

	public function addMethod(string $name, callable $closure): void {
		$this->dynamicMethods[$name] = $closure->bindTo($this, $this);
	}

	// Перехватчик вызовов несуществующих методов
	public function __call(string $name, array $arguments) {
		if (isset($this->dynamicMethods[$name])) {
			return call_user_func_array($this->dynamicMethods[$name], $arguments);
		}
		throw new \BadMethodCallException("Метод {$name} не существует.");
	}
}
