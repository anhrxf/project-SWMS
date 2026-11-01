<?php

namespace Tests\Unit;

use App\Services\WasteClassifierService;
use PHPUnit\Framework\TestCase;

class WasteClassifierTest extends TestCase
{
    protected WasteClassifierService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WasteClassifierService();
    }

    public function test_get_category_labels(): void
    {
        $this->assertStringContainsString('Organik', $this->service->getCategoryLabel('organic'));
        $this->assertStringContainsString('Plastik', $this->service->getCategoryLabel('plastic'));
        $this->assertStringContainsString('Kertas', $this->service->getCategoryLabel('paper'));
    }

    public function test_get_recommendations(): void
    {
        $organicRec = $this->service->getRecommendation('organic');
        $this->assertArrayHasKey('type', $organicRec);
        $this->assertArrayHasKey('action', $organicRec);
        $this->assertArrayHasKey('bin', $organicRec);
        $this->assertStringContainsString('Hijau', $organicRec['bin']);

        $plasticRec = $this->service->getRecommendation('plastic');
        $this->assertStringContainsString('Kuning', $plasticRec['bin']);

        $paperRec = $this->service->getRecommendation('paper');
        $this->assertStringContainsString('Biru', $paperRec['bin']);
    }
}
