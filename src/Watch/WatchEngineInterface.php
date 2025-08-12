<?php

/*
 * This file is part of the pkg6/consoles
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Pkg6\Consoles\Watch;

use Pkg6\Console\Command;

interface WatchEngineInterface
{
    const defaultExclude = [
        // IDE / 系统文件
        '.idea', '.vscode', '.fleet', '.history', '.DS_Store', 'Thumbs.db',
        // 依赖
        'vendor', 'node_modules', '.npm', '.yarn', '.pnpm',
        // 构建 / 缓存
        'build', 'dist', 'out', '.next', '.nuxt', '.expo', '.parcel-cache', '.cache', '.tmp', 'tmp', 'temp', ".php-cs-fixer.cache",
        // 运行时
        'runtime', 'public/storage', 'storage', 'database', 'bootstrap/cache',
        // 日志 / 进程文件
        'logs', '.logs', '*.log', '*.pid',
        // 测试
        'test', 'tests', 'coverage', 'tests_output',
        // 文档 / License
        'README', 'README.md', 'LICENSE', 'CHANGELOG.md',
        // 环境 / 配置
        '.env.*', '.dockerignore', '.gitattributes', '.gitignore', '.editorconfig',
        // CI/CD（可选排除）
        '.github', '.gitlab-ci.yml', '.circleci', '.azure-pipelines.yml',
    ];

    public function setPath($path);

    public function setExclude($exclude);

    public function setCommand($command);

    public function run(Command $command);
}
