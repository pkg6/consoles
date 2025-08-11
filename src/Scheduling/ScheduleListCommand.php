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

class ScheduleListCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'schedule:list';
    /**
     * @var string
     */
    protected $description = 'List the scheduled commands';

    /**
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        $schedule = App::$schedule;
        foreach ($schedule->events as $event) {
            $rows[] = [
                $event->command,
                $event->expression,
                $event->description,
                $event->getNextRunDate()->format('Y-m-d H:i:s P'),

            ];
        }
        $this->table([
            'Command',
            'Interval',
            'Description',
            'NextDue',
        ], $rows ?? []);

        return self::SUCCESS;
    }
}
