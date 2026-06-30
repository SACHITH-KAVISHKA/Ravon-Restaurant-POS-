<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemModifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MenuSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'superadmin']);

        // Create admin user
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');

        // Create a category
        $this->category = Category::create([
            'name' => 'Food',
            'slug' => 'food',
            'is_active' => true,
        ]);
    }

    /**
     * Test menu item soft delete.
     */
    public function test_menu_item_soft_delete(): void
    {
        $item = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Chicken Burger',
            'price' => 500,
            'status' => 1,
        ]);

        $this->actingAs($this->adminUser);

        // Send delete request
        $response = $this->delete(route('menu.items.destroy', $item));
        $response->assertRedirect(route('menu.index'));

        // Assert record still exists in the database
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => 0,
        ]);

        // Assert direct queries do not return the inactive item
        $this->assertEquals(0, Item::count());

        // Assert we can load it using withoutGlobalScope
        $this->assertEquals(1, Item::withoutGlobalScope('active')->count());
    }

    /**
     * Test menu item portion soft delete.
     */
    public function test_item_portion_soft_delete(): void
    {
        $item = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Chicken Burger',
            'price' => 500,
            'status' => 1,
        ]);

        $portion = $item->modifiers()->create([
            'name' => 'Large',
            'type' => 'size',
            'price_adjustment' => 200,
            'is_active' => true,
            'status' => 1,
        ]);

        $this->actingAs($this->adminUser);

        // Send delete request for modifier
        $response = $this->delete(route('menu.modifiers.destroy', $portion));
        $response->assertRedirect(route('menu.items.edit', $item));

        // Assert record still exists in the database as inactive
        $this->assertDatabaseHas('item_modifiers', [
            'id' => $portion->id,
            'status' => 0,
        ]);

        // Assert active queries do not fetch the inactive portion
        $this->assertEquals(0, ItemModifier::count());

        // Assert we can load it using withoutGlobalScope
        $this->assertEquals(1, ItemModifier::withoutGlobalScope('active')->count());
    }

    /**
     * Test activating inactive items and portions.
     */
    public function test_activate_item_and_portion(): void
    {
        $item = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Chicken Burger',
            'price' => 500,
            'status' => 0, // Inactive
        ]);

        $portion = $item->modifiers()->create([
            'name' => 'Large',
            'type' => 'size',
            'price_adjustment' => 200,
            'is_active' => true,
            'status' => 0, // Inactive
        ]);

        $this->actingAs($this->adminUser);

        // Activate item
        $responseItem = $this->post(route('menu.items.activate', $item->id));
        $responseItem->assertRedirect(route('menu.index'));

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => 1,
        ]);

        // Activate portion
        $responsePortion = $this->post(route('menu.modifiers.activate', $portion->id));
        $responsePortion->assertRedirect(route('menu.index'));

        $this->assertDatabaseHas('item_modifiers', [
            'id' => $portion->id,
            'status' => 1,
        ]);
    }

    /**
     * Test JSON endpoints for inactive items and portions.
     */
    public function test_inactive_endpoints(): void
    {
        $item = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Chicken Burger',
            'price' => 500,
            'status' => 0, // Inactive
        ]);

        $portion = $item->modifiers()->create([
            'name' => 'Large',
            'type' => 'size',
            'price_adjustment' => 200,
            'is_active' => true,
            'status' => 0, // Inactive
        ]);

        $this->actingAs($this->adminUser);

        // Test GET /menu-items/inactive
        $responseItems = $this->get(route('menu.items.inactive'));
        $responseItems->assertStatus(200);
        $responseItems->assertJsonFragment([
            'name' => 'Chicken Burger',
            'status' => 0,
        ]);

        // Test GET /menu-item-portions/inactive
        $responsePortions = $this->get(route('menu.portions.inactive'));
        $responsePortions->assertStatus(200);
        $responsePortions->assertJsonFragment([
            'name' => 'Large',
            'status' => 0,
        ]);
    }

    /**
     * Test active price list Excel export.
     */
    public function test_export_price_list(): void
    {
        // 1. Setup Active and Inactive Items
        // Active item without portions
        $activeItemNoPortions = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Chicken Burger',
            'price' => 1250,
            'status' => 1,
        ]);

        // Active item with portions
        $activeItemWithPortions = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Pizza',
            'price' => 2000,
            'status' => 1,
        ]);

        // Active portion
        $activePortionLarge = $activeItemWithPortions->modifiers()->create([
            'name' => 'Large',
            'type' => 'size',
            'price_adjustment' => 2500,
            'is_active' => true,
            'status' => 1,
        ]);

        // Inactive portion (should not be in export)
        $inactivePortionSmall = $activeItemWithPortions->modifiers()->create([
            'name' => 'Small',
            'type' => 'size',
            'price_adjustment' => 1200,
            'is_active' => true,
            'status' => 0,
        ]);

        // Inactive item (should not be in export)
        $inactiveItem = Item::create([
            'category_id' => $this->category->id,
            'name' => 'Old Tea',
            'price' => 150,
            'status' => 0,
        ]);

        $this->actingAs($this->adminUser);

        // 2. Send Export Request
        $response = $this->get(route('menu.items.export-price-list'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="POS_Price_List_' . date('Y-m-d') . '.xlsx"');

        // 3. Load spreadsheet content from streamed output
        $content = $response->streamedContent();
        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_');
        file_put_contents($tempFile, $content);

        try {
            $spreadsheet = IOFactory::load($tempFile);
            $sheet = $spreadsheet->getActiveSheet();

            // Verify title
            $this->assertEquals('POS Price List', $sheet->getTitle());

            // Row 1: Headers
            $this->assertEquals('Category', $sheet->getCell('A1')->getValue());
            $this->assertEquals('Item Name', $sheet->getCell('B1')->getValue());
            $this->assertEquals('Portion', $sheet->getCell('C1')->getValue());
            $this->assertEquals('Price', $sheet->getCell('D1')->getValue());

            // Header Style checks
            $headerStyle = $sheet->getStyle('A1')->getFill();
            $this->assertEquals(Fill::FILL_SOLID, $headerStyle->getFillType());
            $this->assertEquals('FF667EEA', $headerStyle->getStartColor()->getARGB());
            $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
            $this->assertEquals('FFFFFFFF', $sheet->getStyle('A1')->getFont()->getColor()->getARGB());

            // Frozen panes check
            $this->assertEquals('A2', $sheet->getFreezePane());

            // Row 2: Chicken Burger (active item without portions)
            // Sorted alphabetically: Category (Food), Item Name (Chicken Burger)
            $this->assertEquals('Food', $sheet->getCell('A2')->getValue());
            $this->assertEquals('Chicken Burger', $sheet->getCell('B2')->getValue());
            $this->assertEquals('-', $sheet->getCell('C2')->getValue());
            $this->assertEquals(1250, $sheet->getCell('D2')->getValue());
            $this->assertEquals('"Rs. " #,##0', $sheet->getStyle('D2')->getNumberFormat()->getFormatCode());
            $this->assertEquals(Alignment::HORIZONTAL_RIGHT, $sheet->getStyle('D2')->getAlignment()->getHorizontal());

            // Row 3: Pizza (Parent item row)
            $this->assertEquals('Food', $sheet->getCell('A3')->getValue());
            $this->assertEquals('Pizza', $sheet->getCell('B3')->getValue());
            $this->assertEquals('', $sheet->getCell('C3')->getValue());
            $this->assertEquals('', $sheet->getCell('D3')->getValue());

            // Zebra striping color for Row 2 (even) vs Row 3 (odd)
            $row2Fill = $sheet->getStyle('A2')->getFill();
            $this->assertEquals(Fill::FILL_SOLID, $row2Fill->getFillType());
            $this->assertEquals('FFF9FAFB', $row2Fill->getStartColor()->getARGB());

            $row3Fill = $sheet->getStyle('A3')->getFill();
            $this->assertNotEquals('FFF9FAFB', $row3Fill->getStartColor()->getARGB()); // odd rows should have default/no fill

            // Row 4: Pizza portion Large (indented, portion name, price adjustment)
            $this->assertEquals('', $sheet->getCell('A4')->getValue());
            $this->assertEquals('  ↳ Large', $sheet->getCell('B4')->getValue());
            $this->assertEquals('Large', $sheet->getCell('C4')->getValue());
            $this->assertEquals(2500, $sheet->getCell('D4')->getValue());
            $this->assertEquals('"Rs. " #,##0', $sheet->getStyle('D4')->getNumberFormat()->getFormatCode());

            // Check zebra striping for Row 4 (even)
            $row4Fill = $sheet->getStyle('A4')->getFill();
            $this->assertEquals(Fill::FILL_SOLID, $row4Fill->getFillType());
            $this->assertEquals('FFF9FAFB', $row4Fill->getStartColor()->getARGB());

            // Assert Old Tea (inactive) is not exported
            // There shouldn't be any more rows after Row 4.
            $this->assertEmpty($sheet->getCell('A5')->getValue());
            $this->assertEmpty($sheet->getCell('B5')->getValue());

            // Check cell borders exist
            $border = $sheet->getStyle('A2')->getBorders()->getTop();
            $this->assertEquals(Border::BORDER_THIN, $border->getBorderStyle());
            $this->assertEquals('FFE5E7EB', $border->getColor()->getARGB());

        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
