<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductionTask;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name'=>'Administrador A&A','email'=>'admin@aya.bo','phone'=>'70700001','address'=>'Sacaba, Cochabamba','role'=>'admin','active'=>true,'password'=>'Admin123!']);
        $worker = User::create(['name'=>'Miguel Quispe','email'=>'trabajador@aya.bo','phone'=>'70700002','role'=>'worker','active'=>true,'password'=>'Trabajador123!']);
        $client = User::create(['name'=>'María Fernández','email'=>'cliente@aya.bo','phone'=>'70700003','address'=>'Av. Circunvalación, Cochabamba','role'=>'client','active'=>true,'password'=>'Cliente123!']);
        $client2 = User::create(['name'=>'Carlos Rojas','email'=>'carlos@example.com','phone'=>'76411223','address'=>'Zona Norte, Cochabamba','role'=>'client','active'=>true,'password'=>'Cliente123!']);

        $services = collect([
            ['name'=>'Tapizado de living','slug'=>'retapizado-living','description'=>'Renovación integral de juegos de living: desmontaje, reparación, espuma, tela y acabado.','icon'=>'sofa','base_price'=>1200,'estimated_days'=>12],
            ['name'=>'Tapizado de sillones','slug'=>'tapizado-sillones','description'=>'Restauración personalizada de sillones individuales con materiales de alta resistencia.','icon'=>'armchair','base_price'=>480,'estimated_days'=>7],
            ['name'=>'Tapizado de sillas','slug'=>'tapizado-sillas','description'=>'Renovación de asientos y respaldos para sillas de comedor, oficina o decoración.','icon'=>'chair','base_price'=>140,'estimated_days'=>5],
            ['name'=>'Cabeceras personalizadas','slug'=>'cabeceras','description'=>'Diseño y fabricación de cabeceras a medida en el estilo, color y textura elegidos.','icon'=>'bed','base_price'=>650,'estimated_days'=>9],
        ])->map(fn($data) => Service::create($data));

        $materials = collect([
            ['name'=>'Chenille Premium','category'=>'Tela','color'=>'Arena','color_hex'=>'#C8AD8D','stock'=>32,'minimum_stock'=>8,'unit_price'=>68],
            ['name'=>'Terciopelo Soft','category'=>'Tela','color'=>'Verde bosque','color_hex'=>'#315C4D','stock'=>18,'minimum_stock'=>6,'unit_price'=>82],
            ['name'=>'Cuerina Náutica','category'=>'Sintético','color'=>'Café coñac','color_hex'=>'#9A5F37','stock'=>4,'minimum_stock'=>7,'unit_price'=>95],
            ['name'=>'Lino antimanchas','category'=>'Tela','color'=>'Gris piedra','color_hex'=>'#898783','stock'=>25,'minimum_stock'=>8,'unit_price'=>76],
            ['name'=>'Espuma densidad 30','category'=>'Relleno','color'=>'Blanco','color_hex'=>'#ECE9E1','unit'=>'plancha','stock'=>3,'minimum_stock'=>5,'unit_price'=>125],
        ])->map(fn($data) => Material::create($data));

        $this->call(UpholsteryCatalogSeeder::class);

        $orders = [
            ['client_id'=>$client->id,'service_id'=>$services[0]->id,'material_id'=>$materials[1]->id,'code'=>'AYA-2608-K7P2A','status'=>'in_progress','priority'=>'high','furniture_type'=>'Living de 3 piezas','quantity'=>3,'style'=>'Contemporáneo','primary_color'=>'Verde bosque','dimensions'=>'3 plazas + 2 individuales','description'=>'Retapizado completo, reemplazo de espuma y reparación del brazo derecho.','requested_date'=>now()->subDays(8),'delivery_date'=>now()->addDays(5),'subtotal'=>2860,'discount'=>160,'total'=>2700,'advance'=>1500],
            ['client_id'=>$client2->id,'service_id'=>$services[2]->id,'material_id'=>$materials[0]->id,'code'=>'AYA-2608-M4Q8Z','status'=>'confirmed','priority'=>'normal','furniture_type'=>'6 sillas de comedor','quantity'=>6,'style'=>'Clásico','primary_color'=>'Arena','dimensions'=>'Asiento 45 x 45 cm','description'=>'Renovar asientos conservando la estructura de madera.','requested_date'=>now()->subDays(3),'delivery_date'=>now()->addDays(8),'subtotal'=>960,'discount'=>60,'total'=>900,'advance'=>450],
            ['client_id'=>$client->id,'service_id'=>$services[3]->id,'material_id'=>$materials[3]->id,'code'=>'AYA-2608-R9C3L','status'=>'ready','priority'=>'normal','furniture_type'=>'Cabecera Queen','quantity'=>1,'style'=>'Capitoné moderno','primary_color'=>'Gris piedra','dimensions'=>'160 x 120 cm','description'=>'Cabecera acolchada con botones forrados y montaje incluido.','requested_date'=>now()->subDays(15),'delivery_date'=>now()->addDay(),'subtotal'=>850,'discount'=>0,'total'=>850,'advance'=>850],
        ];
        foreach ($orders as $orderData) {
            $order = Order::create($orderData);
            foreach (['Evaluación y desarmado','Corte de materiales','Costura','Tapizado','Acabado y control de calidad'] as $i => $name) {
                $completed = $order->status === 'ready' || ($order->status === 'in_progress' && $i < 2);
                ProductionTask::create(['order_id'=>$order->id,'assigned_to'=>$worker->id,'name'=>$name,'sequence'=>$i+1,'status'=>$completed?'completed':($order->status==='in_progress'&&$i===2?'in_progress':'pending'),'started_at'=>$completed?now()->subDays(3):null,'completed_at'=>$completed?now()->subDays(2):null]);
            }
            OrderStatusHistory::create(['order_id'=>$order->id,'user_id'=>$admin->id,'status'=>'pending','comment'=>'Pedido recibido.']);
            if ($order->status !== 'pending') OrderStatusHistory::create(['order_id'=>$order->id,'user_id'=>$admin->id,'status'=>$order->status,'comment'=>'Avance actualizado por el taller.']);
        }
    }
}
