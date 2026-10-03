<?php

use App\models\User;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new \App\DB(
	[
		'host'     => $_ENV['DB_HOST'],
		'database' => $_ENV['DB_NAME'],
		'user'     => $_ENV['DB_USER'],
		'password' => $_ENV['DB_PASSWORD'],
	],
);

$result = $db->query('
    SELECT * FROM users u
    WHERE u.username = "Alina";
');


/* print_r($result); */
new User();

