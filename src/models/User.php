<?php
namespace App\models;

declare(strict_types=1);

/**
 * Возвращает то же значение
 * 
 * @template T
 * @param T $var
 * @return T
 */
function my(mixed $var): mixed {
	return $var;
}

/**
 * This is a summation function
 * 
 * @param int $one -- first number to summation
 * @param int $two -- second number to summation
 */
function sum($one, $two): int {
	$res = my($one + $two);
	return $res;
}

sum(1, 2);

	/**
	 * 
	 * 
	 * 
	 * 
	 */
class User {
	static $users = [];

	public function __construct() {
		$this->__construct(); // __construct
	}
	public function create() {
		self::$users[] = $this;
	}
}



