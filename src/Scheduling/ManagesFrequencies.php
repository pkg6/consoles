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

trait ManagesFrequencies
{

    /**
     * @param $expression
     *
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * @param $startTime
     * @param $endTime
     *
     * @return Event
     */
    public function between($startTime, $endTime)
    {
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * @param $startTime
     * @param $endTime
     *
     * @return Event
     */
    public function unlessBetween($startTime, $endTime)
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * @param $startTime
     * @param $endTime
     *
     * @return \Closure
     */
    private function inTimeInterval($startTime, $endTime)
    {
        return function () use ($startTime, $endTime) {
            //new DateTime()
            if ($startTime instanceof \DateTime) {
                $startTime = $startTime->getTimestamp();
            }
            //new DateTime()
            if ($endTime instanceof \DateTime) {
                $endTime = $endTime->getTimestamp();
            }
            // 2022-4-1 12:00:00
            if (is_string($startTime) && strtotime($startTime) != -1) {
                $startTime = strtotime($startTime);
            }
            // 2022-4-1 12:00:00
            if (is_string($endTime) && strtotime($endTime) != -1) {
                $startTime = strtotime($endTime);
            }
            $now = (new \DateTime())->getTimestamp();

            return $now >= $startTime && $now <= $endTime;
        };
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->spliceIntoPosition(1, '*');
    }

    /**
     * Schedule the event to run every two minutes.
     *
     * @return $this
     */
    public function everyTwoMinutes()
    {
        return $this->spliceIntoPosition(1, '*/2');
    }

    /**
     * Schedule the event to run every three minutes.
     *
     * @return $this
     */
    public function everyThreeMinutes()
    {
        return $this->spliceIntoPosition(1, '*/3');
    }

    /**
     * Schedule the event to run every four minutes.
     *
     * @return $this
     */
    public function everyFourMinutes()
    {
        return $this->spliceIntoPosition(1, '*/4');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * Schedule the event to run every fifteen minutes.
     *
     * @return $this
     */
    public function everyFifteenMinutes()
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->spliceIntoPosition(1, 0);
    }

    /**
     * Schedule the event to run hourly at a given offset in the hour.
     *
     * @param array|int $offset
     *
     * @return $this
     */
    public function hourlyAt($offset)
    {
        $offset = is_array($offset) ? implode(',', $offset) : $offset;

        return $this->spliceIntoPosition(1, $offset);
    }

    /**
     * Schedule the event to run every two hours.
     *
     * @return $this
     */
    public function everyTwoHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/2');
    }

    /**
     * Schedule the event to run every three hours.
     *
     * @return $this
     */
    public function everyThreeHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/3');
    }

    /**
     * Schedule the event to run every four hours.
     *
     * @return $this
     */
    public function everyFourHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/4');
    }

    /**
     * Schedule the event to run every six hours.
     *
     * @return $this
     */
    public function everySixHours()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*/6');
    }

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0);
    }

    /**
     * Schedule the command at a given time.
     *
     * @param string $time
     *
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param string $time
     *
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int) $segments[0])
            ->spliceIntoPosition(1, count($segments) === 2 ? (int) $segments[1] : '0');
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @param int $first
     * @param int $second
     *
     * @return $this
     */
    public function twiceDaily($first = 1, $second = 13)
    {
        return $this->twiceDailyAt($first, $second, 0);
    }

    /**
     * Schedule the event to run twice daily at a given offset.
     *
     * @param int $first
     * @param int $second
     * @param int $offset
     *
     * @return $this
     */
    public function twiceDailyAt($first = 1, $second = 13, $offset = 0)
    {
        $hours = $first . ',' . $second;

        return $this->spliceIntoPosition(1, $offset)
            ->spliceIntoPosition(2, $hours);
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->days('1-5');
    }

    /**
     * Schedule the event to run only on weekends.
     *
     * @return $this
     */
    public function weekends()
    {
        return $this->days('6,0');
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(5, 0);
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param array|mixed $dayOfWeek
     * @param string $time
     *
     * @return $this
     */
    public function weeklyOn($dayOfWeek, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->days($dayOfWeek);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1);
    }

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * @param int $dayOfMonth
     * @param string $time
     *
     * @return $this
     */
    public function monthlyOn($dayOfMonth = 1, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth);
    }

    /**
     * Schedule the event to run twice monthly at a given time.
     *
     * @param int $first
     * @param int $second
     * @param string $time
     *
     * @return $this
     */
    public function twiceMonthly($first = 1, $second = 16, $time = '0:0')
    {
        $daysOfMonth = $first . ',' . $second;

        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $daysOfMonth);
    }

    /**
     * Schedule the event to run on the last day of the month.
     *
     * @param string $time
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function lastDayOfMonth($time = '0:0')
    {
        $this->dailyAt($time);
        $this->dailyAt($time);
        $last = (new \DateTime())->setDate(date('Y'), date('m'), 1)->add(\DateInterval::createFromDateString('+1 month -1 day'));

        return $this->spliceIntoPosition(3, $last->format('d'));
    }

    /**
     * Schedule the event to run quarterly.
     *
     * @return $this
     */
    public function quarterly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, '1-12/3');
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, 1);
    }

    /**
     * Schedule the event to run yearly on a given month, day, and time.
     *
     * @param int $month
     * @param int|string $dayOfMonth
     * @param string $time
     *
     * @return $this
     */
    public function yearlyOn($month = 1, $dayOfMonth = 1, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(3, $dayOfMonth)
            ->spliceIntoPosition(4, $month);
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param array|mixed $days
     *
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * @param $position
     * @param $value
     *
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);
        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }
}
