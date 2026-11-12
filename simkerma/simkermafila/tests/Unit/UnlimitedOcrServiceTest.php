<?php

namespace Tests\Unit;

use App\Services\UnlimitedOcrService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnlimitedOcrServiceTest extends TestCase
{
    public function test_it_sends_page_images_to_the_configured_unlimited_ocr_server(): void
    {
        config([
            'services.unlimited_ocr.url' => 'http://ocr.internal:10000/v1/chat/completions',
            'services.unlimited_ocr.token' => 'test-token',
            'services.unlimited_ocr.model' => 'Unlimited-OCR-2',
            'services.unlimited_ocr.tokens_per_page' => 8000,
        ]);
        Http::fake([
            'http://ocr.internal:10000/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'TENTANG KERJA SAMA PENDIDIKAN']],
                ],
            ]),
        ]);

        $imagePath = tempnam(sys_get_temp_dir(), 'unlimited-ocr-test-');
        file_put_contents($imagePath, 'fake-image-content');

        try {
            $text = app(UnlimitedOcrService::class)->recognizeImages([$imagePath]);
        } finally {
            unlink($imagePath);
        }

        $this->assertSame('TENTANG KERJA SAMA PENDIDIKAN', $text);
        Http::assertSent(fn ($request) => $request->url() === 'http://ocr.internal:10000/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['model'] === 'Unlimited-OCR-2'
            && $request['max_tokens'] === 8000
            && $request['messages'][0]['content'][0]['type'] === 'image_url'
            && str_contains($request['messages'][0]['content'][1]['text'], '<PAGE><image>'));
    }

    public function test_it_rejects_an_empty_page_list(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada gambar halaman PDF');

        app(UnlimitedOcrService::class)->recognizeImages([]);
    }
}
