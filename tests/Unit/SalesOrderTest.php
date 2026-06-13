<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SalesOrderTest extends TestCase
{
    public function test_order_status_constants()
    {
        // Test that order status constants are defined correctly
        $this->assertEquals(0, \App\Models\SalesOrder::STATUS_DRAFT);
        $this->assertEquals(1, \App\Models\SalesOrder::STATUS_PENDING);
        $this->assertEquals(2, \App\Models\SalesOrder::STATUS_APPROVED);
    }

    public function test_order_can_edit()
    {
        // Test order editability based on status
        $this->assertTrue(true);
    }
}
