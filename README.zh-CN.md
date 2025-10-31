# PHP Pools

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![License](https://img.shields.io/packagist/l/tourze/php-pools.svg?style=flat-square)](https://packagist.org/packages/tourze/php-pools)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

一个简单轻量的 PHP 长连接池管理库。此库提供了一个易于使用的 API，
用于创建、管理和重用数据库连接或其他外部服务连接。

## 目录

- [特性](#特性)
- [安装](#安装)
- [系统要求](#系统要求)
- [快速开始](#快速开始)
- [高级用法](#高级用法)
- [API 参考](#api-参考)
- [安全性](#安全性)
- [贡献](#贡献)
- [许可证](#许可证)

## 特性

- 🔧 简单直观的连接池管理 API
- 🔄 自动连接重用和生命周期管理
- 🔗 支持任何类型的连接（PDO、Redis 等）
- 📊 连接池统计和监控功能
- 🛡️ 内置重试和重连逻辑
- 🎯 多连接池分组管理
- 📦 零依赖且框架无关

## 安装

```bash
composer require tourze/php-pools
```

## 系统要求

- PHP 8.1 或更高版本

## 快速开始

### 基本连接池使用

```php
use PDO;
use Utopia\Pools\Pool;

// 创建一个包含 5 个连接的连接池
$pool = new Pool('mysql-pool', 5, function() {
    $pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});

// 从连接池获取连接
$connection = $pool->pop();
$pdo = $connection->getResource();

// 使用连接
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([1]);
$user = $stmt->fetch();

// 将连接返回给连接池
$pool->push($connection);
```

### 推荐模式：使用 `use()` 方法

为了更好的资源管理，使用 `use()` 方法自动处理连接生命周期：

```php
$result = $pool->use(function(PDO $pdo) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([1]);
    return $stmt->fetch();
});
```

## 高级用法

### 连接池配置

```php
$pool = new Pool('mysql-pool', 10, $connectionFactory);

// 配置重试行为
$pool->setRetryAttempts(3);     // 连接池为空时重试 3 次
$pool->setRetrySleep(1);        // 重试间隔 1 秒

// 配置重连行为
$pool->setReconnectAttempts(3); // 连接失败时重试 3 次
$pool->setReconnectSleep(2);    // 重连间隔 2 秒
```

### 连接池管理

```php
// 检查连接池状态
echo $pool->count();   // 可用连接数
echo $pool->isEmpty(); // 检查连接池是否为空
echo $pool->isFull();  // 检查连接池是否已满

// 回收所有连接
$pool->reclaim();      // 将所有活动连接返回到连接池
```

### 连接池分组

```php
use Utopia\Pools\Group;

$group = new Group();

// 将多个连接池添加到分组
$group->add($mysqlPool);
$group->add($redisPool);

// 从分组获取连接池
$mysql = $group->get('mysql-pool');
$redis = $group->get('redis-pool');

// 配置分组中的所有连接池
$group->setReconnectAttempts(3);
$group->setReconnectSleep(5);
```

### 连接工厂最佳实践

```php
// 良好实践：延迟连接创建与适当的错误处理
$pool = new Pool('redis-pool', 5, function() {
    $redis = new Redis();
    if (!$redis->connect('127.0.0.1', 6379)) {
        throw new \Exception('Failed to connect to Redis');
    }
    return $redis;
});

// 良好实践：连接验证
$pool = new Pool('pdo-pool', 10, function() {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
    ]);
    
    // 测试连接
    $pdo->query('SELECT 1');
    
    return $pdo;
});
```

## API 参考

### Pool 类

- `Pool::__construct(string $name, int $size, callable $factory)` - 构造函数
- `Pool::use(callable $callback): mixed` - 使用托管连接执行回调
- `Pool::pop(): Connection` - 从连接池获取连接
- `Pool::push(Connection $connection): void` - 将连接返回到连接池
- `Pool::count(): int` - 获取可用连接数
- `Pool::isEmpty(): bool` - 检查连接池是否为空
- `Pool::isFull(): bool` - 检查连接池是否已满
- `Pool::reclaim(): void` - 将所有活动连接返回到连接池

### Connection 类

- `Connection::getID(): string` - 获取连接唯一标识符
- `Connection::getResource(): mixed` - 获取底层连接资源

### 异常类

- `PoolConnectionException` - 连接创建失败
- `PoolEmptyException` - 连接池为空且无可用连接
- `PoolInvalidUsageException` - 连接池使用无效
- `PoolNotFoundException` - 在分组中找不到连接池

## 安全性

### 连接安全

- 始终使用参数化查询防止 SQL 注入
- 绝不在代码中存储敏感凭据；使用环境变量
- 实施适当的连接超时和限制
- 监控连接使用情况以检测异常

### 最佳实践

```php
// 使用环境变量存储凭据
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

// 始终使用预处理语句
$result = $pool->use(function(PDO $pdo) use ($userId) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
});
```

## 贡献

请查看 [CONTRIBUTING.md](CONTRIBUTING.md) 了解详情。

## 许可证

MIT 许可证 (MIT)。详情请查看 [License File](LICENSE)。
