<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Routes
require __DIR__ . '/../src/Router/Index.php';

/* $db = new DB( */
/* 	[ */
/* 		'host'     => $_ENV['DB_HOST'], */
/* 		'database' => $_ENV['DB_NAME'], */
/* 		'user'     => $_ENV['DB_USER'], */
/* 		'password' => $_ENV['DB_PASSWORD'], */
/* 	], */
/* ); */
/**/
/* $result = $db->query(' */
/* 	select * from users; */
/* '); */
/**/
/* echo '<pre>'; */
/* print_r($result); */
/* echo '</pre>'; */
