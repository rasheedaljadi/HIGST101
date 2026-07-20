<?php

namespace Tests\Unit\AliExpress;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Lightweight test case for pure-unit AliExpress mapping tests.
 *
 * It boots the Laravel application (so config() and other container helpers
 * work — the mapper reads aliexpress.import.* config) but deliberately does
 * NOT use DatabaseTransactions, since these tests touch no database. The
 * framework base class manages application setUp/tearDown so global state is
 * restored between tests.
 */
abstract class MapperTestCase extends BaseTestCase
{
    //
}
