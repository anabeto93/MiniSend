<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends \Tests\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
