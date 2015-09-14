<?php

/**
 * @file MyPaydateCalculator.class.php
 *
 * Return next 10 paydates from today, given a paydate in the past, and a paydate model.
 *
 * == HACKING / EXTENDING ==
 * In PHP 5.4+, we'd use Traits, since the isWeekend(), increase/decreaseDate
 * and related methods would not normally change between implementations. In
 * PHP < 5.4, variant calculators can extend this class, and should normally
 * only need to override $this->holidays and possibly calculateNextPaydates(),
 * and if implementing additional models, also advancePaydate().
 *
 * If you're dealing with a place where weekends are something other than "Saturday" and
 * "Sunday," then you'll also need to override isWeekend().
 *
 * Most of the intelligence behind these calculations is out-sourced to
 * strtotime(), so future hackers might like to review
 *  http://php.net/strtotime
 *  http://php.net/manual/en/datetime.formats.php
 *
 * == SPECS ==
 * Rules:
 * * A valid paydate is a date that is neither a holiday or a weekend.
 * * If a paydate falls on a weekend, increase date until a valid date is reached.
 * * If a paydate falls on a holiday, decrease date until a valid date is reached.
 * * Holiday adjustment takes precedence over weekend adjustment
 * * The two given paydates given to your class will not be adjusted by weekends or a holiday
 * * "next" paydate cannot be today
 * * generated paydates need to be the next paydates (from today forward)
 *
 * Paydate Models:
 * MONTHLY   - A person is paid on the same day of the month every month, for instance, 1/17/2012 and 2/17/2012
 *  -- If the numeric date does not occur in the following month because it is at the end of a longer month,
 *  -- the the paydate returned will be the 1st of the next month. So, for instance, March 1 follows January 30.
 * BIWEEKLY  - A person is paid on the same day of the week every other week, for instance, 4/6/2012 and  4/20/2012
 * WEEKLY    - A person is paid on the same day of the week every week, for instance 4/9/2012 and 4/16/2012
 *
 * @author Matt Chapman <Matt@NinjitsuWeb.com>
 */

require_once 'PaydateCalculator.interface.inc';

class MyPaydateCalculator implements PaydateCalculator
{
  // Holiday Dates specified as Unix Timestamps.
  // @see scripts/convert_holiday_format.php
  // source data: ['01-01-2014','20-01-2014','17-02-2014','26-05-2014','04-07-2014','01-09-2014','13-10-2014','11-11-2014','27-11-2014','25-12-2014','01-01-2015','19-01-2015','16-02-2015','25-05-2015','03-07-2015','07-09-2015','12-10-2015','11-11-2015','26-11-2015','25-12-2015']
  public $holidays = array(1388563200, 1390204800, 1392624000, 1401087600, 1404457200, 1409554800, 1413183600, 1415692800, 1417075200, 1419494400, 1420099200, 1421654400, 1424073600, 1432537200, 1435906800, 1441609200, 1444633200, 1447228800, 1448524800, 1451030400);

  // Use constants so we can change these in one place, if they ever need to change in the future.
  const MONTHLY = 'MONTHLY';
  const BIWEEKLY = 'BIWEEKLY';
  const WEEKLY = 'WEEKLY';


  // ISO-8601 Date format numeric representation for Friday; used for calculating weekends.
  const FRIDAY = 5;

  // Allow to be constructed with alternate holiday set or start date.
  function __construct($today = NULL, $holidays = NULL) {
    //This is the timezone we used for generating our holiday timestamps.
    date_default_timezone_set('America/Los_Angeles');
    if (is_array($holidays)) {
      $this->holidays = $holidays;
    }
    $this->today = $today ? strtotime($today) : time();
  }

  /**
   * This function takes a paydate model and a paydate and generates the next
   * $number_of_paydates paydates.
   *
   * @param string $paydate_model The paydate model, one of the items in the spec.
   * @param string $paydate_one   An example paydate IN THE PAST as a string in Y-m-d format.
   * @param int $number_of_paydates The number of paydates to generate.
   *
   * @return array the next paydates (from today) as strings in Y-m-d format
   */
  public function calculateNextPaydates($paydate_model, $paydate_one, $number_of_paydates = 10)
  {
    $output = array();
    $last = strtotime($paydate_one);

    // We require the next dates after today.
    while ($last <= $this->today) {
      $last = $this->advancePaydate($last, $paydate_model);
    }

    while (count($output) < $number_of_paydates) {
      $adjusted = $this->adjustPaydate($last);
      $output[] = date('Y-m-d', $adjusted);
      $last = $this->advancePaydate($last, $paydate_model);
    }

    return $output;
  }

  /**
   * Gets the pay date from the given start date based on the model.
   *
   * @param unix_timestamp $start_day The day to calculate the pay date from.
   * @param string $pay_span One of the payment models.
   *
   * @return unix_timestamp A unix timestamp representing the following pay date.
   */
  public static function advancePaydate($start_day, $paydate_model)
  {
    switch ($paydate_model) {
      case self::WEEKLY:
        return strtotime("+1 week", $start_day);
        break;
      case self::BIWEEKLY:
        return strtotime("+2 weeks", $start_day);
        break;
      case self::MONTHLY:
        $paydate = strtotime("+1 month", $start_day);
        break;
    }
    return $paydate;
  }

  /**
   * Correct for holiday or weekend.
   * Holiday adjustment takes precedence over weekend.
   *
   * @param unix_timestamp $paydate The pay day calculated thus far.
   * @param internal $loop internal use only, to prevent infinite recursion.
   *
   * @return unix_timestamp A unix timestamp representing the modified pay day.
   */
  private function adjustPaydate($timestamp, $loop = 'weekend')
  {
    if ($this->isHolidayUnix($timestamp))
    {
      $timestamp = $this->shiftDateUnix($timestamp, "-1 day");
      $timestamp = $this->adjustPaydate($timestamp, 'holiday');
    }
    else if ($this->isWeekendUnix($timestamp))
    {
      $timestamp = $loop == 'weekend' ? strtotime("+1 day", $timestamp) : strtotime("-1 day", $timestamp);
      $timestamp = $this->adjustPaydate($timestamp, $loop);
    }
    return $timestamp;
  }

  /**
   * This function determines whether a given date in Y-m-d format is a holiday.
   *
   * @param string $date A date as a string formatted as Y-m-d
   *
   * @return boolean whether or not the given date is on a holiday
   */
  public function isHoliday($date)
  {
    return $this->isHolidayUnix(strtotime($date));
  }

  /**
   * @see isHoliday().
   *
   * @param unix_timestamp $timestamp
   *
   * @return boolean whether or not the given date is on a weekend
   */
  public function isHolidayUnix($timestamp)
  {
    return in_array($timestamp, $this->holidays);
  }

  /**
   * This function determines whether a given date in Y-m-d format is on a weekend.
   *
   * @param string $date A date as a string formatted as Y-m-d
   *
   * @return boolean whether or not the given date is on a weekend
   */
  public function isWeekend($date)
  {
    // 'N' parameter asks for ISO-8601 Numeric day of the week.
    return $this->isWeekendUnix(strtotime($date));
  }

  /**
   * @see isWeekend().
   *
   * @param unix_timestamp $timestamp
   *
   * @return boolean whether or not the given date is on a weekend
   */
  public static function isWeekendUnix($timestamp)
  {
    return date('N', $timestamp) > self::FRIDAY;
  }


  /**
   * This function determines whether a given date in Y-m-d format is a valid paydate according to specification rules.
   *
   * @param string $date A date as a string formatted as Y-m-d
   *
   * @return boolean whether or not the given date is a valid paydate
   */
  public function isValidPaydate($date)
  {
    return !$this->isWeekend($date) AND !$this->isHoliday($date);
  }



  /**
   * This function increases a given date in Y-m-d format by $count $units
   *
   * @param string $date A date as a string formatted as Y-m-d
   * @param integer $count The amount of units to increment
   *
   * @return string the calculated day's date as a string in Y-m-d format
   */
  public function increaseDate($date, $count, $unit = 'days')
  {
    return date('Y-m-d', $this->shiftDateUnix(strtotime($date), '+', $count, $unit));
  }

  /**
   * This function decreases a given date in Y-m-d format by $count $units
   *
   * @param string $date A date as a string formatted as Y-m-d
   * @param integer $count The amount of units to decrement
   *
   * @return string the calculated day's date as a string in Y-m-d format
   */
  public function decreaseDate($date, $count, $unit = 'days')
  {
    return date('Y-m-d', $this->shiftDateUnix(strtotime($date), '-', $count, $unit));
  }

  /**
   * Helper function modifies a unix timestamp by a positive or negative number of units.
   *
   * @param unix_timestamp $timestamp
   * @param string $direction '+' or '-'
   * @param integer $count The amount of units to decrement
   * @param string $unit A time period understood by strtotime.
   *
   * @return boolean whether or not the given date is on a weekend
   */
  public static function shiftDateUnix($timestamp, $direction, $count = NULL, $unit = NULL)
  {
    return strtotime("$direction$count $unit", $timestamp);
  }

}