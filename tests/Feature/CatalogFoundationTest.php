<?php

namespace Tests\Feature;

use App\Link;
use App\Product;
use App\ProductLink;
use App\ProductPriceSnapshot;
use App\Program;
use App\Services\ProductPriceSnapshotService;
use App\Services\ProductService;
use App\Services\ProductRankingService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class CatalogFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_records_source_and_preserves_unknown_values(): void
    {
        $productLink = $this->createProductLink();
        $snapshot = app(ProductPriceSnapshotService::class)->record($productLink, [
            'source' => 'test-adapter',
            'external_offer_id' => 'offer-1',
            'observed_at' => '2026-08-25 10:00:00',
            'price' => 999.50,
            'currency' => 'INR',
            'availability' => 'in_stock',
            'rating' => 4.25,
            'rating_count' => 12,
            'original_price' => 1299.00,
            'discount_percent' => 23.06,
            'metadata' => ['fixture' => true],
        ]);

        $this->assertInstanceOf(ProductPriceSnapshot::class, $snapshot);
        $this->assertSame('test-adapter', $snapshot->source);
        $this->assertSame('999.50', $snapshot->price);
        $this->assertSame('in_stock', $snapshot->availability);
        $this->assertSame(['fixture' => true], $snapshot->metadata);

        $unknown = app(ProductPriceSnapshotService::class)->record($productLink, [
            'source' => 'test-adapter',
            'external_offer_id' => 'offer-1',
            'observed_at' => '2026-08-25 11:00:00',
            'availability' => 'unknown',
        ]);

        $this->assertNull($unknown->price);
        $this->assertNull($unknown->rating);
        $this->assertSame(2, $productLink->priceSnapshots()->count());
    }

    public function test_latest_and_history_are_source_filtered_and_time_ordered(): void
    {
        $productLink = $this->createProductLink();
        $service = app(ProductPriceSnapshotService::class);
        $service->record($productLink, [
            'source' => 'source-a',
            'observed_at' => '2026-08-25 09:00:00',
            'price' => 100,
        ]);
        $service->record($productLink, [
            'source' => 'source-b',
            'observed_at' => '2026-08-25 12:00:00',
            'price' => 80,
        ]);
        $service->record($productLink, [
            'source' => 'source-a',
            'observed_at' => '2026-08-25 13:00:00',
            'price' => 90,
        ]);

        $this->assertSame('90.00', $service->latest($productLink, 'source-a')->price);
        $this->assertSame('80.00', $service->latest($productLink, 'source-b')->price);
        $this->assertSame(['90.00', '100.00'], $service->history($productLink, 'source-a')->pluck('price')->all());
        $this->assertSame('90.00', $productLink->fresh()->latestPriceSnapshot->price);
    }

    public function test_snapshot_rejects_invalid_values(): void
    {
        $productLink = $this->createProductLink();
        $service = app(ProductPriceSnapshotService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->record($productLink, [
            'source' => 'test-adapter',
            'rating' => 6,
        ]);
    }

    public function test_comparison_uses_latest_snapshots_and_never_infers_availability(): void
    {
        $firstLink = $this->createProductLink();
        $secondProgram = Program::create([
            'name' => 'Second Fixture Program',
            'slug' => 'second-fixture-' . uniqid(),
            'type' => Program::TYPE_ECOMMERCE,
            'merchant_name' => 'Second Fixture Merchant',
            'merchant_url' => 'https://second-merchant.example.test',
            'status' => Program::STATUS_ACTIVE,
            'commission_structure' => ['fixture' => true],
        ]);
        $secondUser = User::create([
            'name' => 'Second Fixture User',
            'email' => 'second-fixture-' . uniqid() . '@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);
        $secondLink = Link::create([
            'program_id' => $secondProgram->id,
            'user_id' => $secondUser->id,
            'original_url' => 'https://second-merchant.example.test/product',
            'affiliate_url' => 'https://second-merchant.example.test/product?ref=fixture',
            'short_code' => 'second-fixture-' . uniqid(),
            'is_active' => true,
        ]);
        $secondProductLink = ProductLink::create([
            'product_id' => $firstLink->product_id,
            'program_id' => $secondProgram->id,
            'link_id' => $secondLink->id,
            'price' => 200,
            'currency' => 'INR',
            'availability' => 'unknown',
            'is_best_price' => false,
        ]);

        $service = app(ProductPriceSnapshotService::class);
        $service->record($firstLink, [
            'source' => 'fixture-a',
            'observed_at' => '2026-08-25 09:00:00',
            'price' => 1000,
            'currency' => 'INR',
            'metadata' => ['fixture' => true],
        ]);
        $service->record($firstLink, [
            'source' => 'fixture-a',
            'observed_at' => '2026-08-25 10:00:00',
            'price' => 950,
            'metadata' => ['fixture' => true],
        ]);
        $service->record($secondProductLink, [
            'source' => 'fixture-b',
            'observed_at' => '2026-08-25 09:30:00',
            'price' => 800,
            'currency' => 'INR',
            'availability' => 'in_stock',
            'rating' => 4.2,
            'metadata' => ['fixture' => true],
        ]);

        $offers = app(ProductService::class)->comparePrices($firstLink->product_id);

        $this->assertCount(2, $offers);
        $this->assertSame($secondProductLink->id, $offers->first()['id']);
        $this->assertSame('800.00', $offers->first()['price']);
        $this->assertTrue($offers->first()['is_lowest_known_price']);
        $this->assertSame('950.00', $offers->last()['price']);
        $this->assertFalse($offers->last()['is_lowest_known_price']);
        $this->assertTrue($offers->last()['is_fixture']);
        $this->assertNull($offers->last()['availability']);
        $this->assertCount(2, $offers->last()['history']);
    }

    public function test_comparison_preview_keeps_financial_features_disabled_by_default(): void
    {
        $this->assertTrue((bool) config('comparison.preview_enabled'));
        $this->assertFalse((bool) config('comparison.rewards_enabled'));
        $this->assertFalse((bool) config('comparison.vouchers_enabled'));
        $this->assertFalse((bool) config('comparison.gifts_enabled'));
    }

    public function test_preview_gate_hides_public_comparison_and_outbound_click_entry_points(): void
    {
        config(['comparison.preview_enabled' => false]);

        $this->get(route('products.index'))->assertNotFound();
        $this->getJson(route('products.index'))
            ->assertStatus(404)
            ->assertJson([
                'status' => 'unavailable',
                'message' => 'Comparison preview is disabled',
            ]);
        $this->get(route('products.buy', ['productId' => 1, 'programId' => 1]))
            ->assertNotFound();
    }

    public function test_public_comparison_pages_show_source_boundaries(): void
    {
        $productLink = $this->createProductLink();
        app(ProductPriceSnapshotService::class)->record($productLink, [
            'source' => 'local-preview',
            'observed_at' => '2026-08-25 14:00:00',
            'price' => 999,
            'metadata' => ['fixture' => true, 'data_class' => 'local_fixture'],
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Compare products across merchants')
            ->assertSee('Local fixture')
            ->assertDontSee('Highest Commission');

        $this->get(route('products.show', $productLink->product_id))
            ->assertOk()
            ->assertSee('Compare recorded offers')
            ->assertSee('local-preview')
            ->assertSee('Local fixture')
            ->assertSee('Availability unavailable')
            ->assertDontSee('Best Commission')
            ->assertDontSee('>In Stock<');

        $this->getJson(route('products.show', $productLink->product_id))
            ->assertOk()
            ->assertJsonMissingPath('data.product.active_commissions');
    }

    public function test_ranking_requires_explicit_weights_and_uses_stable_ordering(): void
    {
        $service = new ProductRankingService();
        $weights = [
            'price' => 50,
            'availability' => 20,
            'rating' => 10,
            'price_history' => 5,
            'demand' => 10,
            'referral_margin' => 5,
        ];
        $offers = [
            [
                'id' => 'offer-b',
                'features' => [
                    'price' => 0.8,
                    'availability' => 1,
                    'rating' => 0.8,
                    'price_history' => 0.5,
                    'demand' => 0.5,
                    'referral_margin' => 1,
                ],
            ],
            [
                'id' => 'offer-a',
                'features' => [
                    'price' => 0.8,
                    'availability' => 1,
                    'rating' => 0.8,
                    'price_history' => 0.5,
                    'demand' => 0.5,
                    'referral_margin' => 1,
                ],
            ],
        ];

        $ranked = $service->rank($offers, $weights);
        $this->assertSame(['offer-a', 'offer-b'], array_column($ranked, 'id'));
        $this->assertSame($ranked[0]['ranking_score'], $ranked[1]['ranking_score']);
    }

    private function createProductLink(): ProductLink
    {
        $user = User::create([
            'name' => 'Catalog Fixture User',
            'email' => 'catalog-fixture-' . uniqid() . '@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);
        $program = Program::create([
            'name' => 'Catalog Fixture Program',
            'slug' => 'catalog-fixture-' . uniqid(),
            'type' => Program::TYPE_ECOMMERCE,
            'merchant_name' => 'Catalog Fixture Merchant',
            'merchant_url' => 'https://merchant.example.test',
            'status' => Program::STATUS_ACTIVE,
            'commission_structure' => ['fixture' => true],
        ]);
        $product = Product::create([
            'name' => 'Catalog Fixture Product',
            'category' => 'fixture',
            'status' => Product::STATUS_ACTIVE,
        ]);
        $link = Link::create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'original_url' => 'https://merchant.example.test/product',
            'affiliate_url' => 'https://merchant.example.test/product?ref=fixture',
            'short_code' => 'fixture-' . uniqid(),
            'is_active' => true,
        ]);

        return ProductLink::create([
            'product_id' => $product->id,
            'program_id' => $program->id,
            'link_id' => $link->id,
            'price' => 100,
            'currency' => 'INR',
            'availability' => 'unknown',
            'is_best_price' => false,
        ]);
    }
}
