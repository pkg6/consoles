# consoles

Consoles 是一个功能灵活的 PHP 命令行解决方案，既可作为独立的 CLI 工具使用，也可无缝集成到项目中。它内置了常用的命令行功能模块，如计划任务调度、Phar 打包等，帮助开发者高效构建与管理应用。

## 项目特点

- 简洁易用的命令行接口
- 强大的计划任务调度系统（基于 cron 表达式）
- 便捷的 Phar 打包功能
- 良好的扩展性，可以轻松添加自定义命令

## 安装

### 全局命令安装

~~~
composer global require pkg6/consoles
~~~

### 使用 Composer 安装

```bash
composer require pkg6/consoles
```

## 快速开始

## 初始化项目

使用初始化命令快速创建一个新的 console.cli 项目：

```bash
cd demo && consoles init
```

## Phar 打包

console.cli 提供了强大的 Phar 打包功能，可以将项目打包成单个 Phar 文件，方便分发和使用。

### 使用方法

```bash
consoles phar:build [options]
```

### 可用选项

- `-p, --path=PATH`: 项目根路径，默认为当前目录。
- `-b, --bin=BIN`: 默认执行的脚本路径，默认为 'bin/consoles'。
- `--name=NAME`: 设置 Phar 文件名称，如果不提供则使用项目名称。
- `--phar-version=VERSION`: 要编译的项目版本。
- `-M, --mount=MOUNT`: 要挂载的路径或目录，可以多次指定。

### 使用示例

```bash
# 基本用法（在当前目录构建）
consoles phar:build

# 指定项目根路径
consoles phar:build --path=/path/to/your/project

# 指定 Phar 文件名称
consoles phar:build --name=myapp

# 指定版本
consoles phar:build --phar-version=1.0.0

# 挂载额外的目录
consoles phar:build --mount=config/ --mount=resources/

# 组合使用多个选项
consoles phar:build --path=/path/to/your/project --name=myapp --phar-version=1.0.0 --mount=config/
```

### 注意事项

1. 确保 `phar.readonly` 配置已设置为 `Off`，否则无法创建 Phar 文件。
2. 项目必须已经安装了依赖（执行过 `composer install`）。
3. 如果不指定 Phar 文件名称，将默认使用 `composer.json` 中的项目名称。

## 计划任务和自定命令

你可以轻松添加自定义命令到应用中。下面是创建和使用自定义命令的完整示例：

### 创建创建基础app类

~~~php
<?php

namespace App;

use Pkg6\Consoles\App;
use Pkg6\Consoles\Scheduling\Schedule;

class MyApp extends App
{
    protected function schedule(Schedule $schedule)
    {
        // 每分钟执行一次命令
        $schedule->command('your:command')
                 ->everyMinute();
                  
        // 每两分钟执行一次
        $schedule->command('another:command')
                 ->everyTwoMinutes();
                  
        // 每天凌晨 1 点执行
        $schedule->exec('php script.php')
                 ->dailyAt('01:00');
                  
        // 使用自定义 cron 表达式（每小时的第 15 分钟执行）
        $schedule->call(function () {
            // 执行一些操作
            echo '任务执行成功';
        })->cron('15 * * * *');
    }
}

$app = new MyApp();
$app->handle();
~~~

### 创建命令类

创建一个新的命令类：

```php
<?php

namespace App;

use Pkg6\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class GreetingCommand extends Command
{

    protected $name = 'greeting';
    protected $description = 'greeting cmd';

    protected function handle()
    {
        //TODO
    }
}
```

> 可参考laravel命令行进行书写

### 注册命令

在应用中注册自定义命令：

```php
<?php

use App\MyApp;
use App\Commands\GreetingCommand;

// 创建应用实例
$app = new MyApp();
// 注册自定义命令
$app->addCommand(GreetingCommand::class);

// 运行应用
$app->handle();
```

### 使用命令

~~~
php consoles greeting
~~~

## 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/fooBar`)
3. 提交更改 (`git commit -am 'Add some fooBar'`)
4. 推送到分支 (`git push origin feature/fooBar`)
5. 创建新的 Pull Request

## 许可证

本项目使用 MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。
