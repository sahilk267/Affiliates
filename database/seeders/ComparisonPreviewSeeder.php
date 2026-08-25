<?php

namespace Database\Seeders;

use App\Link;
use App\Product;
use App\ProductLink;
use App\ProductPriceSnapshot;
use App\Program;
use App\Services\ProductPriceSnapshotService;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ComparisonPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('ComparisonPreviewSeeder is restricted to local or testing environments.');
        }

        $user = User::firstOrCreate(
            ['email' => 'comparison-preview@example.test'],
            [
                'name' => 'Comparison Preview Fixture',
                'password' => Hash::make(Str::random(40)),
                'role' => User::ROLE_AFFILIATE,
                'is_active' => true,
            ]
        );

        $service = app(ProductPriceSnapshotService::class);
        $fixtures = [
            [
                'sku' => 'preview-product-a',
                'name' => 'Preview Product A',
                'category' => 'Preview Category',
                'offers' => [
                    ['merchant' => 'Preview Merchant One', 'slug' => 'preview-merchant-one', 'price' => 1299.00, 'rating' => 4.2],
                    ['merchant' => 'Preview Merchant Two', 'slug' => 'preview-merchant-two', 'price' => 1199.00, 'rating' => 4.0],
                ],
            ],
            [
                'sku' => 'preview-product-b',
                'name' => 'Preview Product B',
                'category' => 'Preview Category',
                'offers' => [
                    ['merchant' => 'Preview Merchant One', 'slug' => 'preview-merchant-one', 'price' => 799.00, 'rating' => 4.4],
                    ['merchant' => 'Preview Merchant Two', 'slug' => 'preview-merchant-two', 'price' => null, 'rating' => null],
                ],
            ],
            [
                'sku' => 'preview-product-c',
                'name' => 'Preview Product C',
                'category' => 'Preview Category',
                'offers' => [
                    ['merchant' => 'Preview Merchant One', 'slug' => 'preview-merchant-one', 'price' => 2499.00, 'rating' => 4.1],
                    ['merchant' => 'Preview Merchant Two', 'slug' => 'preview-merchant-two', 'price' => 2399.00, 'rating' => 4.3],
                ],
            ],
        ];

        foreach ($fixtures as $fixture) {
            $product = Product::firstOrCreate(
                ['sku' => $fixture['sku']],
                [
                    'name' => $fixture['name'],
                    'description' => 'Synthetic local preview record. It is not a live merchant listing.',
                    'category' => $fixture['category'],
                    'status' => Product::STATUS_ACTIVE,
                ]
            );

            foreach ($fixture['offers'] as $offer) {
                $program = Program::firstOrCreate(
                    ['slug' => $offer['slug']],
                    [
                        'name' => $offer['merchant'],
                        'type' => Program::TYPE_ECOMMERCE,
                        'merchant_name' => $offer['merchant'],
                        'merchant_url' => 'https://' . $offer['slug'] . '.example.test',
                        'status' => Program::STATUS_ACTIVE,
                        'commission_structure' => ['fixture' => true],
                    ]
                );

                $fixturePath = 'product-' . $fixture['sku'];
                $link = Link::firstOrCreate(
                    ['short_code' => 'preview-' . $fixture['sku'] . '-' . $offer['slug']],
                    [
                        'program_id' => $program->id,
                        'user_id' => $user->id,
                        'original_url' => 'https://' . $offer['slug'] . '.example.test/' . $fixturePath,
                        'affiliate_url' => 'https://' . $offer['slug'] . '.example.test/' . $fixturePath . '?ref=preview',
                        'is_active' => true,
                    ]
                );

                $productLink = ProductLink::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'program_id' => $program->id,
                    ],
                    [
                        'link_id' => $link->id,
                        // Legacy product_links.price is non-null; the snapshot remains the source of truth.
                        'price' => $offer['price'] ?? 0,
                        'currency' => 'INR',
                        'availability' => $offer['price'] === null ? 'unknown' : 'in_stock',
                        'is_best_price' => false,
                    ]
                );

                if (!ProductPriceSnapshot::where('product_link_id', $productLink->id)->where('source', 'local-preview')->exists()) {
                    $service->record($productLink, [
                        'source' => 'local-preview',
                        'observed_at' => now(),
                        'price' => $offer['price'],
                        'currency' => 'INR',
                        'availability' => $offer['price'] === null ? null : 'in_stock',
                        'rating' => $offer['rating'],
                        'metadata' => [
                            'fixture' => true,
                            'data_class' => 'local_fixture',
                            'note' => 'Synthetic record for local comparison preview only.',
                        ],
                    ]);
                }
            }
        }
    }
}
