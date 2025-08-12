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
use Pkg6\Consoles\Watch\Engine\SwooleWatchWatchEngine;
use Symfony\Component\Console\Input\InputOption;

class WatchRunCommand extends Command
{
    protected $name = 'watch:run';
    protected $description = 'Watch directories for changes and run a command.';

    protected $engines = [
        'swoole' => SwooleWatchWatchEngine::class,
    ];

    public function __construct()
    {
        parent::__construct();
        $this
            ->addOption("engine", "E", InputOption::VALUE_OPTIONAL, "Watch engine", "swoole")
            ->addOption('path', '', InputOption::VALUE_OPTIONAL, "Directory to watch for changes.", getcwd())
            ->addOption('exclude', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Exclude files or directories.', WatchEngineInterface::defaultExclude)
            ->addOption('command', '', InputOption::VALUE_OPTIONAL, 'Command to execute on change.');
    }

    protected function handle()
    {
        $engine = $this->input->getOption('engine');
        $path = $this->input->getOption('path');
        $exclude = $this->input->getOption('exclude');
        $command = $this->input->getOption('command');
        if ( ! isset($this->engines[$engine])) {
            $this->error("Unsupported engine: {$engine}");

            return self::FAILURE;
        }
        /**
         * @var WatchEngineInterface $engineObject
         */
        $engineObject = new $this->engines[$engine];
        $engineObject->setPath($path);
        $engineObject->setCommand($command);
        if (count($exclude) > 0) {
            $exclude = array_merge($exclude, WatchEngineInterface::defaultExclude);
            $engineObject->setExclude($exclude);
        }
        try {
            $engineObject->run($this);

            return self::SUCCESS;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
