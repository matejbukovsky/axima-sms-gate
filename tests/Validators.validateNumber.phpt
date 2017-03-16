<?php
/**
 * @author Tomáš Blatný
 */

use Axima\SmsGate\Validators;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

Assert::true(Validators::validateNumber('123456789'));
Assert::true(Validators::validateNumber('+420123456789'));
Assert::true(Validators::validateNumber('00420123456789'));
Assert::false(Validators::validateNumber('00+420123456789'));
Assert::false(Validators::validateNumber('123456789a'));
Assert::false(Validators::validateNumber('123456a789a'));
Assert::false(Validators::validateNumber('123 456 789'));
Assert::false(Validators::validateNumber('+420 123456789'));
