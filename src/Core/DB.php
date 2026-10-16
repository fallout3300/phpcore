<?php

namespace App\Core;

/**
 * Класс для управления учётными записями пользователей.
 * Предоставляет методы для авторизации, регистрации
 * и изменения профиля в системе.
 *
 * @author Имя Автора <author@example.com>
 * @package App
 */
class DB {
	private \PDO $pdo;

	public function __construct(array $config = []) {
		try {
			$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8";

			$this->pdo = new \PDO($dsn, $config['user'], $config['password'], $config['options'] ?? []);
		} catch (\Exception $e) {
			die('Error at db connection: ' . $e->getMessage());
		}
	}
	public function query(String $sql): array {
		try {
			$stmt = $this->pdo->query($sql);

			$res = $stmt->fetchAll();

			return $res;
		} catch (\Exception $e) {
			die('Error at db query: ' . $e->getMessage());
		}
	}
}
