<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing inconsistent rows first.
        DB::table('order_items')
            ->where('status', 'delivered')
            ->whereColumn('delivered_quantity', '!=', 'quantity')
            ->update([
                'status' => 'preparing',
                'delivered_at' => null,
            ]);

        // Keep preparing/delivered aligned for rows already in preparing with full delivery.
        DB::table('order_items')
            ->where('status', 'preparing')
            ->whereColumn('delivered_quantity', 'quantity')
            ->update([
                'status' => 'delivered',
                'delivered_at' => DB::raw('COALESCE(delivered_at, updated_at, created_at)'),
            ]);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_order_items_delivery_status_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_order_items_delivery_status_bu');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_order_items_delivery_status_bi
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND NEW.delivered_quantity <> NEW.quantity THEN
        SET NEW.status = 'preparing';
        SET NEW.delivered_at = NULL;
    END IF;

    IF NEW.status = 'preparing' AND NEW.quantity > 0 AND NEW.delivered_quantity = NEW.quantity THEN
        SET NEW.status = 'delivered';
        SET NEW.delivered_at = COALESCE(NEW.delivered_at, CURRENT_TIMESTAMP);
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_order_items_delivery_status_bu
BEFORE UPDATE ON order_items
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND NEW.delivered_quantity <> NEW.quantity THEN
        SET NEW.status = 'preparing';
        SET NEW.delivered_at = NULL;
    END IF;

    IF NEW.status = 'preparing' AND NEW.quantity > 0 AND NEW.delivered_quantity = NEW.quantity THEN
        SET NEW.status = 'delivered';
        SET NEW.delivered_at = COALESCE(NEW.delivered_at, CURRENT_TIMESTAMP);
    END IF;
END
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_order_items_delivery_status_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_order_items_delivery_status_bu');
    }
};
