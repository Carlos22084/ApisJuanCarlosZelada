<?php

namespace Tests\Feature;

use App\Models\AiDesign;
use App\Models\Material;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_pages_and_tracking_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Renovamos tus muebles');
        $this->get('/personalizar')->assertOk()->assertSee('Imagina tu mueble antes de tapizarlo');
        $this->get('/seguimiento?code=AYA-2608-K7P2A')->assertOk()->assertSee('Living de 3 piezas');
    }

    public function test_admin_can_log_in_and_view_management_dashboard(): void
    {
        $this->post('/ingresar', ['email' => 'admin@aya.bo', 'password' => 'Admin123!'])
            ->assertRedirect('/panel');
        $this->get('/panel')->assertOk()->assertSee('Resumen del taller');
        $this->get('/clientes')->assertOk()->assertSee('Gestión de clientes');
        $this->get('/materiales')->assertOk()->assertSee('Materiales e inventario');
        $this->get('/reportes')->assertOk()->assertSee('Reportes de pedidos');
        $this->get('/pedidos/1')->assertOk()->assertSee('Etapas de producción');
    }

    public function test_client_cannot_access_inventory(): void
    {
        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $this->actingAs($client)->get('/materiales')->assertForbidden();
        $this->actingAs($client)->get('/panel')->assertOk()->assertSee('Mis pedidos');
        $this->actingAs($client)->get('/pedidos/1')->assertOk()->assertSee('Resumen económico');
    }

    public function test_admin_can_create_an_order_with_production_stages(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $client = User::where('role', 'client')->firstOrFail();
        $service = Service::firstOrFail();
        $response = $this->actingAs($admin)->post('/pedidos', [
            'client_id' => $client->id, 'service_id' => $service->id, 'status' => 'pending',
            'priority' => 'normal', 'furniture_type' => 'Sillón de prueba',
            'description' => 'Trabajo completo de tapizado para verificación automática.',
            'subtotal' => 500, 'discount' => 20, 'advance' => 200,
        ]);
        $order = Order::orderByDesc('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(5, $order->tasks()->count());
        $this->assertEquals(480, $order->total);
    }

    public function test_assistant_answers_material_questions(): void
    {
        $this->postJson('/asistente', ['message' => '¿Qué material recomiendan?'])
            ->assertOk()->assertJsonStructure(['answer'])->assertJsonFragment(['answer' => 'Trabajamos con chenille, terciopelo, cuerina, cuero y telas antimanchas. Para hogares con niños o mascotas recomendamos opciones resistentes y fáciles de limpiar.']);
    }

    public function test_client_can_generate_an_ai_upholstery_design(): void
    {
        Storage::fake('public');
        config([
            'services.cloudflare_ai.account_id' => 'account-123',
            'services.cloudflare_ai.token' => 'cf-test-token',
            'services.cloudflare_ai.model' => '@cf/black-forest-labs/flux-2-klein-4b',
            'services.cloudflare_ai.width' => 1024,
            'services.cloudflare_ai.height' => 1024,
        ]);
        Http::fake(['api.cloudflare.com/*' => Http::response([
            'result' => ['image' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='],
            'success' => true,
        ])]);
        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $material = Material::firstOrFail();

        $response = $this->actingAs($client)->postJson('/personalizar/generar-ia', [
            'furniture_photo' => UploadedFile::fake()->createWithContent('sofa.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
            'material_id' => $material->id,
            'color' => $material->color,
            'style' => 'Contemporáneo',
            'furniture_type' => 'Sofá de tres plazas',
            'design_notes' => 'Conservar las patas de madera.',
        ]);

        $response->assertOk()->assertJsonStructure(['design_id', 'image_url', 'message']);
        $design = AiDesign::firstOrFail();
        $this->assertSame($client->id, $design->user_id);
        $response->assertJsonPath('image_url', route('customize.ai.image', $design, false));
        Storage::disk('public')->assertExists($design->original_path);
        Storage::disk('public')->assertExists($design->generated_path);
        $this->actingAs($client)
            ->get(route('customize.ai.image', $design))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.cloudflare.com/client/v4/accounts/account-123/ai/run/@cf/black-forest-labs/flux-2-klein-4b'
            && $request->hasHeader('Authorization', 'Bearer cf-test-token'));
    }

    public function test_client_can_recover_their_latest_generated_design(): void
    {
        Storage::fake('public');
        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $material = Material::firstOrFail();
        $design = AiDesign::create([
            'user_id' => $client->id,
            'material_id' => $material->id,
            'original_path' => 'designs/originals/sofa.jpg',
            'generated_path' => 'designs/generated/sofa.jpg',
            'color' => $material->color,
            'model' => 'test-model',
            'prompt' => 'Test prompt',
        ]);
        Storage::disk('public')->put($design->generated_path, 'fake-image');

        $this->actingAs($client)
            ->getJson(route('customize.ai.latest'))
            ->assertOk()
            ->assertJsonPath('design_id', $design->id)
            ->assertJsonPath('image_url', route('customize.ai.image', $design, false))
            ->assertJsonStructure(['created_at']);
    }

    public function test_ai_generation_reports_missing_cloudflare_credentials(): void
    {
        config([
            'services.cloudflare_ai.account_id' => null,
            'services.cloudflare_ai.token' => null,
        ]);

        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $material = Material::firstOrFail();

        $response = $this->actingAs($client)->postJson('/personalizar/generar-ia', [
            'furniture_photo' => UploadedFile::fake()->createWithContent('sofa.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
            'material_id' => $material->id,
            'color' => $material->color,
            'style' => 'Contemporáneo',
            'furniture_type' => 'Sofá de tres plazas',
        ]);

        $response->assertStatus(503)->assertJsonFragment([
            'message' => 'Cloudflare Workers AI no está configurado. Complete CLOUDFLARE_ACCOUNT_ID y CLOUDFLARE_API_TOKEN.',
        ]);
    }
}
