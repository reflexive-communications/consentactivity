<?php

namespace Civi\Consentactivity;

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Base class for headless tests.
 * It implements the before and teardown functions
 *
 * @group headless
 */
class HeadlessTestCase extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
{
    /**
     * Apply a forced rebuild of DB, thus
     * create a clean DB before running tests
     *
     * @throws \CRM_Extension_Exception_ParseException
     */
    public static function setUpBeforeClass(): void
    {
        // Resets DB
        Test::headless()
            ->install('rc-base')
            ->installMe(__DIR__)
            ->apply(true);
    }

    /**
     * @return void
     */
    public function setUpHeadless(): void
    {
    }
}
