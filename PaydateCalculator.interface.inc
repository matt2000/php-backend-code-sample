<?php
/**
 * @file PaydateCalculator.interface.inc
 *
 * An interface for calculating Paydates, given rules about how to handle
 * weekends and holidays.
 */

interface PaydateCalculator {

  /**
   * This function takes a paydate model and two paydates and generates the next $number_of_paydates paydates.
   *
   * @param string $paydate_model The paydate model, one of the items in the spec
   * @param string $paydate_one   An example paydate as a string in Y-m-d format, different from the second
   * @param int $number_of_paydates The number of paydates to generate
   *
   * @return array the next paydates (from today) as strings in Y-m-d format
   */
  public function calculateNextPaydates($paydate_model, $paydate_one, $number_of_paydates);

  /**
   * This function determines whether a given date in Y-m-d format is a holiday.
   *
   * @param string $date A date as a string formatted as Y-m-d
   *
   * @return boolean whether or not the given date is on a holiday
   */
  public function isHoliday($date);

  /**
   * This function determines whether a given date in Y-m-d format is on a weekend.
   *
   * @param string $date A date as a string formatted as Y-m-d
   *
   * @return boolean whether or not the given date is on a weekend
   */
  public function isWeekend($date);

  /**
   * This function determines whether a given date in Y-m-d format is a valid paydate according to specification rules.
   *
   * @param string $date A date as a string formatted as Y-m-d
   *
   * @return boolean whether or not the given date is a valid paydate
   */
  public function isValidPaydate($date);

  /**
   * This function increases a given date in Y-m-d format by $count $units
   *
   * @param string $date A date as a string formatted as Y-m-d
   * @param integer $count The amount of units to increment
   *
   * @return string the calculated day's date as a string in Y-m-d format
   */
  public function increaseDate($date, $count, $unit = 'days');

  /**
   * This function decreases a given date in Y-m-d format by $count $units
   *
   * @param string $date A date as a string formatted as Y-m-d
   * @param integer $count The amount of units to decrement
   *
   * @return string the calculated day's date as a string in Y-m-d format
   */
  public function decreaseDate($date, $count, $unit = 'days');

}