# swoft-laravel-databases
集成 laravel orm 组件至 swoft 

php >7.2, swoole > 4.0.3, swoft > v1.9 (暂不支持v2.x) 

## 技术细节
1. 技术目标：
    * 独立使用 laravel database, 文档和使用方式参考lavarl 社区文档
    * 在不扩大代码量的情况下，尽可能地迁移 laravel database 的功能: QueryBuilder, Model, ORM, 配置项
    * 支持swoole Coroutine\MySQL 异步客户端
    * ~~mysql 连接池~~
2. 实现
    * 代码部分主要参照 [官方文档](https://github.com/illuminate/database) ，在它的基础之上，整合laravel database数据库配置加载方式
    * 参考 database 本身的mysql driver实现，对Coroutine\MySQL的相关调用做了一层代理封装, 使其可以与复用mysql driver部分的代码
## 安装
在composer.json require 中添加如依赖
```json
...
    "require": {
        ...
        "swoft-laravel/database": "^1.0.3"
        ...
    }
....
```
更新依赖
```bash
# 更新所有依赖
composer update 
# 或者只更新生产环境依赖
composer update --no-dev
```
在swoole目录下新建文件  config\database.php
```php
return [
    // 默认连接
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'default' => [
            // 数据库驱动, mysql 为同步版, comysql 为异步客户端
            'driver' => 'comysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'your database'),
            'username' => env('DB_USERNAME', 'you user'),
            'password' => env('DB_PASSWORD', 'your password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => 'your table prefix',
            'strict' => true,
            'engine' => null,
        ],
        'other' => [
            // 数据库驱动, mysql 为同步版, comysql 为异步客户端
            'driver' => 'comysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'your database'),
            'username' => env('DB_USERNAME', 'you user'),
            'password' => env('DB_PASSWORD', 'your password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => 'your table prefix',
            'strict' => true,
            'engine' => null,
        ],
    ],
];
```
如果需要使用 swoole 异步客户端，需要绑定命名空间下的事件监听器
在配置文件 config\properties\app.php 添加如下配置
```php
return [
    ...
    'bootScan'     => [
        ...
        'SwoftLaravel\\Database\\Listener',
        ...
    ],
    ...
];
```
或者手工管理
初始化连接
```php
use SwoftLaravel\Database\Capsule;

class OnWorkerStartListener implements WorkerStartInterface {
    public function onWorkerStart(Server $server, int $workerId, bool $isWorker) {
        if ($isWorker){
            $confPath = BASE_PATH.'/config/database.php';
            DatabaseServiceProvider::init($confPath);
        }
    }
}

```
主动释放连接，使用comysql不主动释放会导致连接数暴涨
```php
use SwoftLaravel\Database\Capsule;
class ResourceReleaseListener implements EventHandlerInterface {
    public function handle(EventInterface $event){
        Capsule::collectResource();
    }
}
```
## 使用
```php 
use SwoftLaravel\Database\Capsule as DB;
class Controller {
    public function demo(){
        $user = Capsule::table('user')
            ->where('name', 'ricky')
            >where('mobile', 13800138000)
            ->get();
    }
}

//----------------------------------------------
use Illuminate\Database\Eloquent\Model;
class User extends Model {
    protected $table = 'user';
}
class Controller {
    public function demo(){
        $user = User::find(6);
    }
}

```
