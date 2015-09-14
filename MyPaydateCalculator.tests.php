<?php

require 'MyPaydateCalculator.class.php';


function test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected) {
  $c = new MyPaydateCalculator($pay_date);
  $res = $c->calculateNextPaydates($model, $pay_date, 1);
  $status = $res[0] === $expected ? 'PASSED' : 'FAILED';
  print "$status: $test_note (got: {$res[0]})\n";
}
$test_id = 0;

// TEST
$test_id++;
$test_note = 'checking BIWEEKLY before a holiday that is on a Monday';
$pay_date = '2014-05-12';
$model = 'BIWEEKLY';
$expected_due_date = '2014-05-23';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);


// TEST
$test_id++;
$test_note = 'checking MONTHLY before a holiday that is on a Monday';
$pay_date = '2014-04-26';
$model = 'MONTHLY';
$expected_due_date = '2014-05-23';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);

// TEST
$test_id++;
$test_note = 'checking MONTHLY before a holiday';
$pay_date = '2014-11-25';
$model = 'MONTHLY';
$expected_due_date = '2014-12-24';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);

// TEST
$test_id++;
$test_note = 'checking MONTHLY before a Saturday';
$pay_date = '2014-04-03';
$model = 'MONTHLY';
$expected_due_date = '2014-05-05';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);

// TEST
$test_id++;
$test_note = 'checking MONTHLY on the 31st';
$pay_date = '2014-03-31';
$model = 'MONTHLY';
$expected_due_date = '2014-05-01';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);


// TEST
$test_id++;
$test_note = 'checking MONTHLY on the 31st with a weekend';
$pay_date = '2014-01-31';
$model = 'MONTHLY';
$expected_due_date = '2014-03-03';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);


// TEST
$test_id = 1;
$test_note = 'on a Tuesday with no holidays';
$pay_date = '2009-04-29';
$model = 'WEEKLY';
$expected_due_date = '2009-05-06';
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);


// TEST
$test_id++;
$test_note = 'on a Monday dealing with a holiday';
$pay_date = '2014-05-19';
$model = 'WEEKLY';
$expected_due_date = '2014-05-23'; // The Friday before the weekend
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);


// TEST
$test_id++;
$test_note = 'on a Sunday with no holidays';
$pay_date = '2014-04-27';
$model = 'WEEKLY';
$expected_due_date = '2014-05-05'; // The Monday after the weekend
test_calculate_due_date($test_id, $test_note, $model, $pay_date, $expected_due_date);

