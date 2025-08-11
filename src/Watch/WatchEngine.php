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
use RuntimeException;
use Symfony\Component\Finder\Finder;

abstract class WatchEngine implements WatchEngineInterface
{
    protected $path = '';
    protected $exclude = [];
    protected $command = null;
    protected $isRunning = false;
    protected $needRunAgain = false;

    public function setPath($path)
    {
        $real = realpath($path);
        if ($real === false || ! is_dir($real)) {
            throw new RuntimeException("Invalid watch path: {$path}");
        }
        $this->path = $real;
    }

    public function setExclude($exclude)
    {
        $this->exclude = (array) $exclude;
    }

    public function setCommand($command)
    {
        $this->command = $command;
    }

    protected function runCommand(Command $command)
    {
        $command->line("<comment>Executing command:</comment> {$this->command}");
        $process = proc_open($this->command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (is_resource($process)) {
            while ( ! feof($pipes[1])) {
                $line = fgets($pipes[1]);
                if ($line !== false) {
                    $command->line(rtrim($line));
                }
            }
            proc_close($process);
        }
        $command->line("<info>Command execution finished.</info>");
        $this->isRunning = false;

        if ($this->needRunAgain) {
            $this->needRunAgain = false;
            $this->executeCommand($command);
        }
    }

    protected function scanAllFiles()
    {
        $finder = new Finder();
        $finder->files()->in($this->path)->ignoreVCS(true);

        if ( ! empty($this->exclude)) {
            foreach ($this->exclude as $pattern) {
                if (substr($pattern, 0, 1) === '/' && substr($pattern, -1) === '/') {
                    $finder->notPath($pattern);
                } elseif (strpos($pattern, '*') !== false) {
                    $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                    $finder->notPath($regex);
                } else {
                    $finder->notPath($pattern);
                }
            }
        }
        $result = [];
        foreach ($finder as $file) {
            $realPath = $file->getRealPath();
            if ($realPath !== false) {
                clearstatcache(true, $realPath);
                $result[$realPath] = md5_file($realPath);
            }
        }

        return $result;
    }
}
