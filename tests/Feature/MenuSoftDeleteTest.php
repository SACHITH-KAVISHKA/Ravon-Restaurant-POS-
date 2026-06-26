<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemModifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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
}
