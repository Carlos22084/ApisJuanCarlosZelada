<?php
namespace App\Http\Controllers;

use App\Models\AiDesign;
use App\Models\Material;
use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AiDesignController extends Controller
{
    public function store(Request $request, CloudflareImageService $imageService)
    {
        $data = $request->validate([
            'furniture_photo' => ['required','image','mimes:jpeg,jpg,png,webp','max:10240'],
            'material_id' => ['required','exists:materials,id'],
            'color' => ['required','string','max:60'],
            'style' => ['nullable','string','max:80'],
            'furniture_type' => ['required','string','max:100'],
            'design_notes' => ['nullable','string','max:300'],
        ]);

        $material = Material::findOrFail($data['material_id']);
        $prompt = implode(' ', [
            'Use input image 0 as the source. Edit this furniture photograph as a professional photorealistic upholstery visualization.',
            'Preserve exactly the furniture geometry, proportions, camera angle, room, lighting and background.',
            "Replace only the visible upholstery with {$material->name}, category {$material->category}, in {$data['color']} color.",
            'Make the fabric texture, seams, folds and shadows physically believable. Do not add or remove furniture.',
            $data['style'] ? "Desired style: {$data['style']}." : '',
            $data['design_notes'] ?? '',
            'No text, labels, logos, people or watermarks. This is a visual estimate, not an exact manufacturing guarantee.',
        ]);

        try {
            $binary = $imageService->editUpholstery($request->file('furniture_photo'), $prompt);
            $token = now()->format('YmdHis').'-'.Str::lower(Str::random(8));
            $disk = $this->designsDisk();
            $originalPath = $request->file('furniture_photo')->storeAs('designs/originals', $token.'.'.$request->file('furniture_photo')->extension(), $disk);
            $generatedPath = 'designs/generated/'.$token.'.'.$this->imageExtension($binary);
            Storage::disk($disk)->put($generatedPath, $binary);
            $design = AiDesign::create([
                'user_id' => $request->user()->id,
                'material_id' => $material->id,
                'original_path' => $originalPath,
                'generated_path' => $generatedPath,
                'color' => $data['color'],
                'style' => $data['style'] ?? null,
                'model' => config('services.cloudflare_ai.model', '@cf/black-forest-labs/flux-2-klein-4b'),
                'prompt' => $prompt,
            ]);
            return response()->json([
                'message' => 'Propuesta visual generada correctamente.',
                'design_id' => $design->id,
                'image_url' => route('customize.ai.image', $design, false),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function image(Request $request, AiDesign $aiDesign)
    {
        $this->authorizeDesign($request, $aiDesign);
        $disk = Storage::disk($this->designsDisk());

        abort_unless($disk->exists($aiDesign->generated_path), 404);

        return $disk->response(
            $aiDesign->generated_path,
            null,
            ['Cache-Control' => 'private, no-store, max-age=0']
        );
    }

    public function original(Request $request, AiDesign $aiDesign)
    {
        $this->authorizeDesign($request, $aiDesign);
        $disk = Storage::disk($this->designsDisk());

        abort_unless($disk->exists($aiDesign->original_path), 404);

        return $disk->response(
            $aiDesign->original_path,
            null,
            ['Cache-Control' => 'private, no-store, max-age=0']
        );
    }

    public function latest(Request $request)
    {
        $design = AiDesign::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if (! $design || ! Storage::disk($this->designsDisk())->exists($design->generated_path)) {
            return response()->json(['message' => 'No se encontró una propuesta reciente.'], 404);
        }

        return response()->json([
            'design_id' => $design->id,
            'image_url' => route('customize.ai.image', $design, false),
            'created_at' => $design->created_at?->toIso8601String(),
        ]);
    }

    private function imageExtension(string $binary): string
    {
        $image = @getimagesizefromstring($binary);

        return match ($image['mime'] ?? null) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new \RuntimeException('Cloudflare devolvió un formato de imagen no compatible.'),
        };
    }

    private function designsDisk(): string
    {
        return config('filesystems.designs_disk', 'public');
    }

    private function authorizeDesign(Request $request, AiDesign $aiDesign): void
    {
        $user = $request->user();
        $canView = $aiDesign->user_id === $user->id
            || $user->role === 'admin'
            || ($user->role === 'worker'
                && $aiDesign->order?->tasks()->where('assigned_to', $user->id)->exists());

        abort_unless($canView, 403);
    }
}
