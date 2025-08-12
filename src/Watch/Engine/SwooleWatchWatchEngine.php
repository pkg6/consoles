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

namespace Pkg6\Consoles\Watch\Engine;

use Pkg6\Console\Command;
use Pkg6\Consoles\Watch\WatchEngine;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Timer;

class SwooleWatchWatchEngine extends WatchEngine
{
    protected $lastSnapshot = [];
    protected $pending = [];

    protected $scanIntervalMs = 800;
    protected $debounceMs = 600;
    protected $debounceCheckMs = 200;

    protected $isRunning = false;
    protected $needRunAgain = false;

    public function run(Command $command)
    {
        if ( ! extension_loaded('swoole')) {
            throw new RuntimeException("Swoole extension is required");
        }
        if ( ! $this->path) {
            throw new RuntimeException("Watch path is not set");
        }

        $command->line("<info>Watching path:</info> {$this->path}");
        if ($this->exclude) {
            $command->line("<comment>Exclude:</comment> " . implode(", ", $this->exclude));
        }
        if ($this->command) {
            $command->line("<comment>Will execute command on changes:</comment> {$this->command}");
        }

        $this->lastSnapshot = $this->scanAllFiles();

        Coroutine\run(function () use ($command) {
            Timer::tick($this->scanIntervalMs, function () {
                $this->scanAndDetect();
            });

            Timer::tick($this->debounceCheckMs, function () use ($command) {
                $this->processPending($command);
            });

            while (true) {
                Coroutine::sleep(10);
            }
        });
    }

    protected function scanAndDetect()
    {
        $current = $this->scanAllFiles();
        $nowMs = (int) (microtime(true) * 1000);

        foreach ($current as $file => $hash) {
            if ( ! isset($this->lastSnapshot[$file])) {
                $this->addPendingEvent($file, 'create', $nowMs);
            } elseif ($this->lastSnapshot[$file] !== $hash) {
                $this->addPendingEvent($file, 'modify', $nowMs);
            }
        }

        foreach ($this->lastSnapshot as $file => $hash) {
            if ( ! isset($current[$file])) {
                $this->addPendingEvent($file, 'delete', $nowMs);
            }
        }

        $this->lastSnapshot = $current;
    }

    protected function addPendingEvent($file, $event, $nowMs)
    {
        if (isset($this->pending[$file])) {
            $oldEvent = $this->pending[$file]['event'];
            if ($oldEvent === 'delete' && $event !== 'delete') {
                $this->pending[$file]['event'] = $event;
            } elseif ($oldEvent === 'create' && $event === 'modify') {
                // 保持 create
            } else {
                $this->pending[$file]['event'] = $event;
            }
            $this->pending[$file]['last_ts'] = $nowMs;
        } else {
            $this->pending[$file] = [
                'event' => $event,
                'first_ts' => $nowMs,
                'last_ts' => $nowMs,
            ];
        }
    }

    protected function processPending(Command $output)
    {
        $nowMs = (int) (microtime(true) * 1000);
        $readyFiles = [];

        foreach ($this->pending as $file => $meta) {
            if ($nowMs - $meta['last_ts'] >= $this->debounceMs) {
                $readyFiles[$file] = $meta;
            }
        }

        if (empty($readyFiles)) {
            return;
        }

        // 合并日志打印
        foreach ($readyFiles as $file => $meta) {
            $ts = date('Y-m-d H:i:s', (int) ($meta['last_ts'] / 1000));
            $output->line("[{$ts}] <info>" . strtoupper($meta['event']) . "</info> -> {$file}");
        }

        // 清空已处理事件
        foreach ($readyFiles as $file => $meta) {
            unset($this->pending[$file]);
        }

        if ($this->isRunning) {
            // 命令正在执行，标记需要重新执行一次
            $this->needRunAgain = true;

            return;
        }

        $this->executeCommand($output);
    }

    protected function executeCommand(Command $output)
    {
        if ( ! $this->command) {
            return;
        }
        $this->isRunning = true;
        $this->needRunAgain = false;
        Coroutine::create(function () use ($output) {
            $this->runCommand($output);
        });
    }
}
