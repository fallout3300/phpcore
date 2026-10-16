<?php

$paths = [
	__DIR__ . '/src/Router/Middleware',
];

foreach ($paths as $pathToAutoload) {
	if (is_dir($pathToAutoload)) {
		foreach (glob($pathToAutoload . '/*.php') as $filename) {
			require_once $filename;
		}
	}
}
