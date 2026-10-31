<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_the_material_inventory(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(route('materials.index'))
            ->assertOk()
            ->assertSee('Materiales e inventario')
            ->assertSee('Nombre del color')
            ->assertSee('Color visual')
            ->assertDontSee('Color hexadecimal')
            ->assertSee('Chenille Premium');
    }

    public function test_worker_can_view_the_material_inventory(): void
    {
        $worker = User::where('role', 'worker')->firstOrFail();

        $this->actingAs($worker)->get(route('materials.index'))
            ->assertOk()
            ->assertSee('Control de existencias');
    }

    public function test_client_cannot_access_the_material_inventory(): void
    {
        $client = User::where('role', 'client')->firstOrFail();

        $this->actingAs($client)->get(route('materials.index'))->assertForbidden();
        $this->actingAs($client)->post(route('materials.store'), $this->validPayload())->assertForbidden();
    }

    public function test_admin_can_create_a_material_with_valid_data(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('materials.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('materials', [
            'name' => 'Microfibra Test',
            'color' => 'Café claro',
            'color_hex' => '#A66A45',
            'stock' => 24,
        ]);
    }

    public function test_required_material_fields_are_validated(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('materials.store'), [])
            ->assertSessionHasErrors(['name', 'category', 'color', 'visual_color', 'unit', 'stock', 'minimum_stock', 'unit_price']);

        $this->assertDatabaseMissing('materials', ['name' => 'Microfibra Test']);
    }

    public function test_negative_stock_and_prices_are_rejected(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('materials.store'), array_merge($this->validPayload(), [
            'stock' => -1,
            'minimum_stock' => -2,
            'unit_price' => -10,
        ]))->assertSessionHasErrors(['stock', 'minimum_stock', 'unit_price']);
    }

    public function test_invalid_visual_color_is_rejected(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('materials.store'), array_merge($this->validPayload(), [
            'visual_color' => 'cafe',
        ]))->assertSessionHasErrors('visual_color');
    }

    public function test_admin_can_update_an_existing_material(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $material = Material::firstOrFail();

        $this->actingAs($admin)->put(route('materials.update', $material), array_merge($this->validPayload(), [
            'name' => 'Chenille Actualizado',
            'stock' => 40,
        ]))->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Chenille Actualizado',
            'stock' => 40,
        ]);
    }

    public function test_materials_can_be_filtered_by_name_or_color(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(route('materials.index', ['search' => 'Verde bosque']))
            ->assertOk()
            ->assertSee('Terciopelo Soft')
            ->assertDontSee('Chenille Premium');
    }

    public function test_low_stock_materials_are_identified_in_the_list(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('materials.index'));

        $response->assertOk()->assertSee('Stock bajo')->assertSee('Disponible');
        $this->assertTrue(Material::where('name', 'Cuerina Náutica')->firstOrFail()->low_stock);
    }

    public function test_complete_upholstery_catalog_contains_ten_types_and_fifty_four_variants(): void
    {
        $upholstery = Material::whereIn('category', ['Tela', 'Sintético', 'Cuero']);

        $this->assertSame(54, $upholstery->count());
        $this->assertSame(10, $upholstery->distinct()->count('name'));
        $this->assertDatabaseHas('materials', ['name' => 'Bouclé contemporáneo', 'color' => 'Marfil']);
        $this->assertDatabaseHas('materials', ['name' => 'Microfibra Microsuede', 'color' => 'Camel']);
        $this->assertDatabaseHas('materials', ['name' => 'Jacquard decorativo', 'color' => 'Beige dorado']);
        $this->assertDatabaseHas('materials', ['name' => 'Poliéster impermeable Petra', 'color' => 'Chocolate']);
        $this->assertDatabaseHas('materials', ['name' => 'Cuerina PU Semicuero', 'color' => 'Negro']);
        $this->assertDatabaseHas('materials', ['name' => 'Cuero natural', 'category' => 'Cuero']);
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Microfibra Test',
            'category' => 'Tela',
            'color' => 'Café claro',
            'visual_color' => '#A66A45',
            'unit' => 'metro',
            'stock' => 24,
            'minimum_stock' => 6,
            'unit_price' => 72.50,
            'active' => 1,
        ];
    }
}
