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
        $this->assertNull($attributes['last_delivered_at']);
    }

    public function test_first_delivery_sets_both_timestamps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:20:00'));

        $orderItem = new OrderItem([
            'quantity' => 8,
            'delivered_quantity' => 0,
        ]);

        $attributes = $orderItem->deliveryTrackingAttributes(3);

        $this->assertSame(3, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:20:00', $attributes['delivered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-23 10:20:00', $attributes['last_delivered_at']->format('Y-m-d H:i:s'));
    }

    public function test_quantity_increase_preserves_first_delivery_and_clears_final_timestamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:25:00'));

        $orderItem = new OrderItem([
            'quantity' => 5,
            'delivered_quantity' => 4,
            'delivered_at' => Carbon::parse('2026-04-23 10:18:00'),
            'last_delivered_at' => Carbon::parse('2026-04-23 10:19:00'),
        ]);

        $attributes = $orderItem->quantityIncreaseTrackingAttributes(8);

        $this->assertSame(3, $attributes['latest_added_quantity']);
        $this->assertSame(4, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:18:00', $attributes['delivered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-23 10:25:00', $attributes['preparing_at']->format('Y-m-d H:i:s'));
        $this->assertNull($attributes['last_delivered_at']);
    }

    public function test_full_delivery_sets_final_timestamp_each_time_completion_is_reached(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:30:00'));

        $orderItem = new OrderItem([
            'quantity' => 8,
            'delivered_quantity' => 4,
            'delivered_at' => Carbon::parse('2026-04-23 10:20:00'),
        ]);

        $attributes = $orderItem->deliveryTrackingAttributes(4);

        $this->assertSame(8, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:20:00', $attributes['delivered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-23 10:30:00', $attributes['last_delivered_at']->format('Y-m-d H:i:s'));
    }

    public function test_partial_delivery_updates_last_timestamp_without_changing_first_delivery_timestamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:32:00'));

        $orderItem = new OrderItem([
            'quantity' => 8,
            'delivered_quantity' => 4,
            'delivered_at' => Carbon::parse('2026-04-23 10:20:00'),
            'last_delivered_at' => Carbon::parse('2026-04-23 10:25:00'),
        ]);

        $attributes = $orderItem->deliveryTrackingAttributes(2);

        $this->assertSame(6, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:20:00', $attributes['delivered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-23 10:32:00', $attributes['last_delivered_at']->format('Y-m-d H:i:s'));
    }

    public function test_quantity_decrease_recalculates_final_timestamp_when_completion_is_restored(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-23 10:35:00'));

        $orderItem = new OrderItem([
            'quantity' => 8,
            'delivered_quantity' => 8,
            'delivered_at' => Carbon::parse('2026-04-23 10:20:00'),
            'last_delivered_at' => Carbon::parse('2026-04-23 10:30:00'),
        ]);

        $attributes = $orderItem->quantityDecreaseTrackingAttributes(6);

        $this->assertSame(6, $attributes['delivered_quantity']);
        $this->assertSame('2026-04-23 10:20:00', $attributes['delivered_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-23 10:35:00', $attributes['last_delivered_at']->format('Y-m-d H:i:s'));
    }
}