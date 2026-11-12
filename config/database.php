<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'educare_crm';
const DB_USER = 'root';
const DB_PASS = '123456';

function dbUser(): string
{
    $user = getenv('EDUCARE_DB_USER');
    return $user === false || $user === '' ? DB_USER : $user;
}

function dbPassword(): string
{
    $password = getenv('EDUCARE_DB_PASS');
    return ($password !== false && $password !== '') ? $password : DB_PASS;
}

// Tạo một kết nối PDO dùng lại trong suốt vòng đời của request hiện tại.
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            dbUser(),
            dbPassword(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
    }

    return $pdo;
}
