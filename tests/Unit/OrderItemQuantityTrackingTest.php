<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use Carbon\Carbon;
use Tests\TestCase;

class OrderItemQuantityTrackingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_initial_tracking_uses_initial_quantity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:15:00'));

        $orderItem = new OrderItem([
            'quantity' => 5,
        ]);

        $attributes = $orderItem->initialTrackingAttributes();

        $this->assertSame(5, $attributes['latest_added_quantity']);
        $this->assertSame(0, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:15:00', $attributes['preparing_at']->format('Y-m-d H:i:s'));
        $this->assertNull($attributes['delivered_at']);
    }

    public function test_quantity_increase_tracks_only_delta(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:20:00'));

        $orderItem = new OrderItem([
            'quantity' => 5,
            'delivered_quantity' => 4,
        ]);

        $attributes = $orderItem->quantityIncreaseTrackingAttributes(8);

        $this->assertSame(3, $attributes['latest_added_quantity']);
        $this->assertSame(4, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:20:00', $attributes['preparing_at']->format('Y-m-d H:i:s'));
        $this->assertNull($attributes['delivered_at']);
    }

    public function test_delivery_tracking_caps_at_total_quantity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:25:00'));

        $orderItem = new OrderItem([
            'quantity' => 8,
            'delivered_quantity' => 4,
        ]);

        $attributes = $orderItem->deliveryTrackingAttributes(6);

        $this->assertSame(8, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:25:00', $attributes['delivered_at']->format('Y-m-d H:i:s'));
    }
}