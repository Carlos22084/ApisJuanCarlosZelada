<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    public function __invoke(Request $request)
    {
        $question = Str::lower($request->validate(['message' => ['required','string','max:500']])['message']);
        $answer = match (true) {
            Str::contains($question, ['precio','costo','cuanto','cuánto']) => 'El precio depende del tamaño, material y estado del mueble. Use “Personalizar” para enviar los detalles; la cotización base se confirma después de la evaluación.',
            Str::contains($question, ['tiempo','demora','tarda','entrega']) => 'Un trabajo suele tomar entre 7 y 15 días. La fecha exacta depende del servicio y de la disponibilidad del material.',
            Str::contains($question, ['estado','seguimiento','pedido']) => 'Puede consultar el avance desde “Seguimiento” usando el código AYA de su pedido.',
            Str::contains($question, ['material','tela','cuero']) => 'Trabajamos con chenille, terciopelo, cuerina, cuero y telas antimanchas. Para hogares con niños o mascotas recomendamos opciones resistentes y fáciles de limpiar.',
            Str::contains($question, ['color','combina','recomienda']) => 'Para espacios pequeños funcionan bien tonos claros y neutros; para uso intenso, tonos medios y texturas jaspeadas disimulan mejor el desgaste.',
            Str::contains($question, ['ubicación','ubicacion','donde','dónde']) => 'A&A Multiservicios atiende en Cochabamba. Puede registrar su solicitud y coordinaremos la visita o recepción del mueble.',
            default => 'Puedo orientarle sobre precios, materiales, colores, tiempos de entrega y seguimiento de pedidos. ¿Sobre cuál de estos temas desea información?',
        };
        return response()->json(['answer' => $answer]);
    }
}
