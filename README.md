# Consoles

Consoles 是一个功能强大且灵活的 PHP 命令行解决方案，既可作为独立的 CLI 工具使用，也可无缝集成到现有项目中。它内置了多种实用功能模块，包括计划任务调度、Phar 打包、文件监听等，帮助开发者高效构建与管理命令行应用。

## 项目特点

- 简洁易用的命令行接口，基于 Symfony Console 组件
- 强大的计划任务调度系统（基于 cron 表达式），支持并发执行
- 便捷的 Phar 打包功能，轻松将项目打包成单个可执行文件
- 智能的文件监听功能，支持文件变更时自动执行命令
- 良好的扩展性，可以轻松添加自定义命令和功能
- 支持依赖注入和服务容器，便于功能扩展和测试
- 详细的日志记录和错误处理机制

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

### 初始化项目

使用初始化命令快速创建一个新的 Consoles 项目：

```bash
# 创建项目目录
mkdir my-console-app && cd my-console-app

# 初始化项目
consoles init
```

初始化成功后，项目结构如下：
```
my-console-app/
├── app/
│   └── ConsoleCliApp.php  # 应用入口类
├── composer.json          # Composer 配置文件
└── consoles               # 可执行脚本
```

### 运行应用

初始化后，可以通过以下命令运行应用：

```bash
# 查看可用命令
php consoles list

# 运行特定命令
php consoles [command-name]
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

## 计划任务调度

Consoles 提供了强大的计划任务调度系统，可以基于 cron 表达式执行各种任务，包括命令、闭包函数和外部脚本。

~~~shell
#  列出所有的计划任务
consoles schedule:list

# 执行计划任务（需要加入到系统定时任务中）
consoles schedule:run

# 执行计划任务
consoles schedule:work
~~~

## 文件监听功能

Consoles 提供了文件监听功能，可以监控指定目录的文件变化，并在文件变化时自动执行命令。

### 使用方法

```bash
# 基本用法（监听当前目录，文件变化时执行指定命令）
consoles watch:run --command="php consoles greeting"

# 监听特定目录
consoles watch:run --path=/path/to/directory --command="php consoles greeting"

# 排除某些文件或目录
consoles watch:run --exclude=vendor/ --exclude=node_modules/ --command="php consoles greeting"

# 指定监听引擎（默认使用 swoole）
consoles watch:run --engine=swoole --command="php consoles greeting"
```

### 可用选项

- `--path=PATH`: 要监听的目录路径，默认为当前目录。
- `--exclude=EXCLUDE`: 要排除的文件或目录，可以多次指定。
- `--command=COMMAND`: 文件变化时要执行的命令。
- `--engine=ENGINE`: 监听引擎，默认为 'swoole'。

### 示例：开发过程中自动测试

```bash
# 监听 src 目录下的文件变化，变化时执行测试命令
consoles watch:run --path=src/ --command="vendor/bin/phpunit"
```

## 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/fooBar`)
3. 提交更改 (`git commit -am 'Add some fooBar'`)
4. 推送到分支 (`git push origin feature/fooBar`)
5. 创建新的 Pull Request

## 许可证

本项目使用 MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。
