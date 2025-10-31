# PHP Pools

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![License](https://img.shields.io/packagist/l/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

A simple and lightweight library for managing long-living connection pools in PHP. 
This library provides an easy-to-use API for creating, managing, and reusing database connections 
or other external service connections.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [System Requirements](#system-requirements)
- [Quick Start](#quick-start)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

## Features

- 🔧 Simple and intuitive API for connection pool management
- 🔄 Automatic connection reuse and lifecycle management
- 🔗 Support for any type of connection (PDO, Redis, etc.)
- 📊 Pool statistics and monitoring capabilities
- 🛡️ Built-in retry and reconnection logic
- 🎯 Connection grouping for multiple pools
- 📦 Zero dependencies and framework-agnostic

## Installation

```bash
composer require tourze/php-pools
```

## System Requirements

- PHP 8.1 or higher

## Quick Start

### Basic Pool Usage

```php
use PDO;
use Utopia\Pools\Pool;

// Create a pool with 5 connections
$pool = new Pool('mysql-pool', 5, function() {
    $pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});

// Get a connection from the pool
$connection = $pool->pop();
$pdo = $connection->getResource();

// Use the connection
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([1]);
$user = $stmt->fetch();

// Return the connection to the pool
$pool->push($connection);
```

### Recommended Pattern: Using the `use()` Method

For better resource management, use the `use()` method which automatically handles 
connection lifecycle:

```php
$result = $pool->use(function(PDO $pdo) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([1]);
    return $stmt->fetch();
});
```

## Advanced Usage

### Pool Configuration

```php
$pool = new Pool('mysql-pool', 10, $connectionFactory);

// Configure retry behavior
$pool->setRetryAttempts(3);     // Retry 3 times when pool is empty
$pool->setRetrySleep(1);        // Wait 1 second between retries

// Configure reconnection behavior
$pool->setReconnectAttempts(3); // Retry 3 times when connection fails
$pool->setReconnectSleep(2);    // Wait 2 seconds between reconnection attempts
```

### Pool Management

```php
// Check pool status
echo $pool->count();   // Number of available connections
echo $pool->isEmpty(); // Check if pool is empty
echo $pool->isFull();  // Check if pool is full

// Reclaim all connections
$pool->reclaim();      // Return all active connections to the pool
```

### Pool Groups

```php
use Utopia\Pools\Group;

$group = new Group();

// Add multiple pools to a group
$group->add($mysqlPool);
$group->add($redisPool);

// Get pools from group
$mysql = $group->get('mysql-pool');
$redis = $group->get('redis-pool');

// Configure all pools in the group
$group->setReconnectAttempts(3);
$group->setReconnectSleep(5);
```

### Connection Factory Best Practices

```php
// Good: Lazy connection creation with proper error handling
$pool = new Pool('redis-pool', 5, function() {
    $redis = new Redis();
    if (!$redis->connect('127.0.0.1', 6379)) {
        throw new \Exception('Failed to connect to Redis');
    }
    return $redis;
});

// Good: Connection validation
$pool = new Pool('pdo-pool', 10, function() {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
    ]);
    
    // Test connection
    $pdo->query('SELECT 1');
    
    return $pdo;
});
```

## API Reference

### Pool Class

- `Pool::__construct(string $name, int $size, callable $factory)`
- `Pool::use(callable $callback): mixed` - Execute callback with managed connection
- `Pool::pop(): Connection` - Get a connection from the pool
- `Pool::push(Connection $connection): void` - Return a connection to the pool
- `Pool::count(): int` - Get number of available connections
- `Pool::isEmpty(): bool` - Check if pool is empty
- `Pool::isFull(): bool` - Check if pool is full
- `Pool::reclaim(): void` - Return all active connections to the pool

### Connection Class

- `Connection::getID(): string` - Get connection unique identifier
- `Connection::getResource(): mixed` - Get the underlying connection resource

### Exception Classes

- `PoolConnectionException` - Connection creation failed
- `PoolEmptyException` - Pool is empty and no connections available
- `PoolInvalidUsageException` - Invalid pool usage
- `PoolNotFoundException` - Pool not found in group

## Security

### Connection Security

- Always use parameterized queries to prevent SQL injection
- Never store sensitive credentials in code; use environment variables
- Implement proper connection timeouts and limits
- Monitor connection usage to detect anomalies

### Best Practices

```php
// Use environment variables for credentials
$pool = new Pool('secure-db', 5, function() {
    return new PDO(
        $_ENV['DB_DSN'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
});

// Always use prepared statements
$result = $pool->use(function(PDO $pdo) use ($userId) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
});
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
