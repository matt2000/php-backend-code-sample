#!/usr/bin/php
<?php
/**
 * This is a small command line code-generator utility to help convert dates
 * for computational efficiency.
 *
 * We receive our holidays in day-month-year format, but it's easier to work
 * with them as unix timestamps. This script takes in an array in the source
 * and outputs PHP code containing the dates as timestamps. This output can be
 * copy/pasted or otherwise inserted into the application code.
 *
 * Why go to the trouble? I'm assuming our company will grow to millions of
 * employees worldwide, and our application code may be run many thousands
 * of times per day. By pre-calculating these values once, we save significant
 * computational effort, assuming the lifetime of this application is measured
 * in years.
 *
 * Also, I have the free time to do it. Of course, this is clearly a silly
 * premature-optimization, if working under severe time pressure.
 *
 * @todo Allow date list to be provided as command line arguments.
 *
 * @author Matt Chapman <Matt@NinjitsuWeb.com>
 */

date_default_timezone_set('America/Los_Angeles');

// The original holidays given, in day-month-year format.
$holidays = array('01-01-2014','20-01-2014','17-02-2014','26-05-2014','04-07-2014','01-09-2014','13-10-2014','11-11-2014','27-11-2014','25-12-2014','01-01-2015','19-01-2015','16-02-2015','25-05-2015','03-07-2015','07-09-2015','12-10-2015','11-11-2015','26-11-2015','25-12-2015');

$timestamps = array_map('strtotime', $holidays);

echo $output = 'public static $holidays = array('. implode(', ', $timestamps) . ');';