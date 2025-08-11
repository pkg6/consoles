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

namespace Pkg6\Consoles;

use Composer\InstalledVersions;
use Exception;
use Pkg6\Console\Application;
use Pkg6\Consoles\Phar\PharBuildCommand;
use Pkg6\Consoles\Scheduling\ScheduleAppTrait;
use Pkg6\Consoles\Scheduling\ScheduleListCommand;
use Pkg6\Consoles\Scheduling\ScheduleRunCommand;
use Pkg6\Consoles\Scheduling\ScheduleWorkCommand;
use Pkg6\Consoles\Watch\WatchRunCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class App
{
    use ScheduleAppTrait;

    /**
     * @var Application
     */
    protected $appaction;
    /**
     * @var array
     */
    protected $commands = [

        InitCommand::class,
        /***
         * 计划任务
         */
        ScheduleListCommand::class,
        ScheduleRunCommand::class,
        ScheduleWorkCommand::class,

        PharBuildCommand::class,

        //监听目录
        WatchRunCommand::class,
    ];
    /**
     * @var string|null
     */
    protected $version;

    public function __construct()
    {
        $this->setVersion();
        $this->setApplication();
    }

    /**
     * @return void
     */
    protected function commands()
    {
        // $this->addCommand(ScheduleInitCommand::class);
    }

    /**
     * @param string|array $commands
     *
     * @return $this
     */
    public function addCommand($commands)
    {
        $commands = (array) $commands;
        foreach ($commands as $command) {
            $this->commands[] = $command;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setVersion()
    {
        $this->version = InstalledVersions::getVersion("pkg6/consoles");

        return $this;
    }

    /**
     * @return $this
     */
    protected function setApplication()
    {
        $this->appaction = new Application('consoles', $this->version);

        return $this;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        if (is_null($this->appaction)) {
            $this->setApplication();
        }
        $this->appaction->resolveCommands($this->commands);

        return $this->appaction;
    }

    /**
     * @param       $command
     * @param array $parameters
     * @param       $outputBuffer
     *
     * @return int
     *
     * @throws \Exception
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        $this->bootstrap();

        return $this->getApplication()
            ->call($command, $parameters, $outputBuffer);
    }

    /**
     * @return void
     */
    protected function bootstrap()
    {
        $this->defineSchedule();
        $this->commands();
    }

    /**
     * @param $input
     * @param $output
     *
     * @return int
     *
     * @throws Exception
     */
    public function handle($input = null, $output = null)
    {
        $this->bootstrap();

        return $this->getApplication()
            ->run(
                $input ?: new ArgvInput(),
                $output ?: new ConsoleOutput()
            );
    }
}
