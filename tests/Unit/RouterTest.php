<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\{ Response, Request, Router };
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Response::class)]
class RouterTest extends TestCase {
	private ?Response $response;

	public function setUp(): void {
		require_once 'src/Core/Router.php';
		$this->response = new Response();
		ob_start();
	}
	public function tearDown(): void {
		$this->response = null;
	}

	public function testText(): void {
		$this->response->text('Hello world');

		$expected = 'Hello world';

		$this->assertEquals($expected, $this->response->rawBody);
	}
	public function testTextContentType(): void {
		require_once 'src/Core/Router.php';
		$this->response = new Response();

		$this->response->text('Hello world');

		$expected = 'text/plain';

		$this->assertStringContainsString(
			$expected,
			$this->response->headers['Content-Type'],
		);
	}

	public function testEcho(): void {
		echo 'Hello world';
		$this->response->send();

		$expected = 'Hello world';

		$this->assertEquals($expected, $this->response->rawBody);
	}

	public function testStatusCode(): void {
		$statusCode = 404;

		$this->response
			 ->status($statusCode)
			 ->send()
		;

		$this->assertEquals($statusCode, $this->response->statusCode);
	}
}
