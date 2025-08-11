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

namespace Pkg6\Consoles\Scheduling;

use Pkg6\Console\Command;
use Pkg6\Consoles\App;
use Symfony\Component\Process\Process;

class ScheduleRunCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'schedule:run
                            {--pool=1 : schedule run for pool process}
                            {--size=5 : The number of events to process running}';
    /**
     * @var string
     */
    protected $description = 'Run the scheduled commands';
    /**
     * @var Process
     */
    protected $running = [];
    /**
     * @var Schedule
     */
    protected $schedule;
    /**
     * @var bool
     */
    protected $eventsRan = false;

    /**
     * @return int
     */
    public function handle()
    {
        $this->schedule = App::$schedule;
        $pool = $this->option('pool');
        if ($pool) {
            $this->poolProcess();
        } else {
            $this->start();
        }
        if ( ! $this->eventsRan) {
            $this->info('No scheduled commands are ready to run.');
        }

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    protected function start()
    {
        foreach ($this->schedule->dueEvents() as $event) {
            if ( ! $event->filtersPass()) {
                continue;
            }
            $this->line('<info>[' . date('c') . '] Running scheduled command:</info> ' . $event->getSummaryForDisplay());
            try {
                $event->run();
                $this->eventsRan = true;
            } catch (\Throwable $e) {
                $this->error($e->getMessage() . ' ' . $e->getTraceAsString());
            }
        }
    }

    /**
     * @return void
     */
    protected function poolProcess()
    {
        $this->events = $this->schedule->dueEventsGenerator();
        $this->info('[' . date('c') . '] Running scheduled command for Pool');
        $this->startNextProcesses();
        while (count($this->running) > 0) {
            /** @var $event Event */
            foreach ($this->running as $index => $event) {
                try {
                    $process = $event->process;
                    $process && $process->checkTimeout();
                    $isRunning = ! is_null($process) ? $process->isRunning() : false;
                    if ( ! $isRunning) {
                        $process && $event->exitCode($process->getExitCode());
                        /*  event callAfterCallbacks **/
                        $event->callAfterCallbacks();
                        unset($this->running[$index]);
                        $this->startNextProcesses();
                    }
                } catch (\Exception|\Throwable $e) {
                    $this->error($e->getMessage());
                }
            }
            usleep(1000);
        }
    }

    /**
     * @return void
     */
    private function startNextProcesses()
    {

        $poolSize = $this->option('size');
        while (count($this->running) < $poolSize && $this->events->valid()) {
            /** @var $event Event */
            $event = $this->events->current();
            if ( ! $event->filtersPass()) {
                continue;
            }
            $this->line('<info>[' . date('c') . '] Running scheduled command:</info> ' . $event->getSummaryForDisplay());
            $event->pool();
            $event->run();
            $this->running[] = $event;
            $this->events->next();
        }
    }
}
