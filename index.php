<?php

spl_autoload_register(function (string $class) {
    $filename = "{$class}.php";
    require_once $filename;
});

new App\A();
new \A();


