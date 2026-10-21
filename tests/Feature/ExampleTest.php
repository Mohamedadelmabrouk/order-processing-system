<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * test health endpoint returns successful response
     */
    public function testHealth(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }
}
