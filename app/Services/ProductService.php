<?php

namespace App\Services;

use App\Product;
use App\ProductLink;
use App\ProductCommission;
use App\ProductPriceSnapshot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Get products for the public comparison MVP.
     *
     * This path uses source-tagged snapshots only and intentionally avoids
     * commission-first ordering or inferred live values.
     */
    public function getComparisonProducts(array $filters = [], string $sortBy = 'price', int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::active()->with([
            'productLinks.program',
            'productLinks.latestPriceSnapshot',
        ]);

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('brand', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->get()->map(function (Product $product): Product {
            $snapshots = $product->productLinks
                ->map(fn (ProductLink $link) => $link->latestPriceSnapshot)
                ->filter();
            $knownPrices = $snapshots
                ->filter(fn (ProductPriceSnapshot $snapshot): bool => $snapshot->price !== null)
                ->map(fn (ProductPriceSnapshot $snapshot): float => (float) $snapshot->price);

            $product->comparison_min_price = $knownPrices->isNotEmpty() ? $knownPrices->min() : null;
            $product->comparison_offer_count = $product->productLinks->count();
            $product->comparison_observed_at = $snapshots->max('observed_at');
            $product->comparison_has_fixture = $snapshots->contains(function (ProductPriceSnapshot $snapshot): bool {
                return is_array($snapshot->metadata) && ($snapshot->metadata['fixture'] ?? false) === true;
            });

            return $product;
        });

        $products = match ($sortBy) {
            'name' => $products->sortBy(fn (Product $product): string => mb_strtolower($product->name)),
            'newest' => $products->sortByDesc(fn (Product $product) => $product->created_at),
            default => $products->sort(function (Product $left, Product $right): int {
                $leftPrice = $left->comparison_min_price ?? INF;
                $rightPrice = $right->comparison_min_price ?? INF;

                return $leftPrice <=> $rightPrice ?: $left->id <=> $right->id;
            }),
        };

        $products = $products->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = max(1, min($perPage, 100));

        return new LengthAwarePaginator(
            $products->forPage($page, $perPage)->values(),
            $products->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Get products with pagination and filters
     *
     * @param array $filters
     * @param string $sortBy
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProducts(array $filters = [], string $sortBy = 'commission', int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with(['productLinks.program', 'activeCommissions.program']);

        // Apply filters
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', Product::STATUS_ACTIVE);
        }

        // Apply sorting
        if ($sortBy === 'commission') {
            // Sort by highest commission rate
            $query->leftJoin('product_commissions', function ($join) {
                $join->on('products.id', '=', 'product_commissions.product_id')
                     ->where('product_commissions.status', 'active');
            })
            ->select('products.*')
            ->selectRaw('MAX(product_commissions.commission_rate) as max_commission')
            ->groupBy('products.id')
            ->orderBy('max_commission', 'desc');
        } elseif ($sortBy === 'price') {
            // Sort by lowest price
            $query->leftJoin('product_links', 'products.id', '=', 'product_links.product_id')
                  ->select('products.*')
                  ->selectRaw('MIN(product_links.price) as min_price')
                  ->groupBy('products.id')
                  ->orderBy('min_price', 'asc');
        } elseif ($sortBy === 'price_desc') {
            // Sort by highest price
            $query->leftJoin('product_links', 'products.id', '=', 'product_links.product_id')
                  ->select('products.*')
                  ->selectRaw('MAX(product_links.price) as max_price')
                  ->groupBy('products.id')
                  ->orderBy('max_price', 'desc');
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', 'asc');
        } elseif ($sortBy === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            // Default: commission
            $query->leftJoin('product_commissions', function ($join) {
                $join->on('products.id', '=', 'product_commissions.product_id')
                     ->where('product_commissions.status', 'active');
            })
            ->select('products.*')
            ->selectRaw('MAX(product_commissions.commission_rate) as max_commission')
            ->groupBy('products.id')
            ->orderBy('max_commission', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get product with all links and commissions
     *
     * @param int $productId
     * @return Product|null
     */
    public function getProductWithLinks(int $productId): ?Product
    {
        return Product::with([
            'productLinks.program',
            'productLinks.link',
            'activeCommissions.program',
        ])->find($productId);
    }

    /**
     * Get one active product for the public comparison preview without loading
     * commission relations.
     */
    public function getComparisonProduct(int $productId): ?Product
    {
        return Product::active()->with([
            'productLinks.program',
            'productLinks.link',
            'productLinks.latestPriceSnapshot',
        ])->find($productId);
    }

    /**
     * Compare prices across platforms for a product
     *
     * @param int $productId
     * @return Collection
     */
    public function comparePrices(int $productId): SupportCollection
    {
        $product = Product::with([
            'productLinks.program',
            'productLinks.link',
            'productLinks.latestPriceSnapshot',
            'productLinks.priceSnapshots',
        ])->find($productId);

        if (!$product) {
            return collect();
        }

        $links = $product->productLinks
            ->map(function ($productLink) {
                $snapshot = $productLink->latestPriceSnapshot;
                $metadata = is_array($snapshot?->metadata) ? $snapshot->metadata : [];

                return [
                    'id' => $productLink->id,
                    'program' => $productLink->program,
                    'price' => $snapshot?->price,
                    'currency' => $snapshot?->currency,
                    'availability' => $snapshot?->availability,
                    'observed_at' => $snapshot?->observed_at,
                    'source' => $snapshot?->source,
                    'rating' => $snapshot?->rating,
                    'rating_count' => $snapshot?->rating_count,
                    'original_price' => $snapshot?->original_price,
                    'discount_percent' => $snapshot?->discount_percent,
                    'data_class' => $metadata['data_class'] ?? 'unclassified',
                    'is_fixture' => ($metadata['fixture'] ?? false) === true,
                    'has_snapshot' => $snapshot !== null,
                    'history' => $productLink->priceSnapshots
                        ->sortByDesc('observed_at')
                        ->take(30)
                        ->values(),
                    'is_lowest_known_price' => false,
                    'link' => $productLink->link,
                ];
            });

        $knownPrices = $links
            ->filter(fn (array $link): bool => $link['price'] !== null)
            ->map(fn (array $link): float => (float) $link['price']);
        $lowestKnownPrice = $knownPrices->isNotEmpty() ? $knownPrices->min() : null;

        return $links
            ->map(function (array $link) use ($lowestKnownPrice): array {
                $link['is_lowest_known_price'] = $lowestKnownPrice !== null
                    && $link['price'] !== null
                    && (float) $link['price'] === (float) $lowestKnownPrice;

                return $link;
            })
            ->sort(function (array $left, array $right): int {
                $leftPrice = $left['price'] === null ? INF : (float) $left['price'];
                $rightPrice = $right['price'] === null ? INF : (float) $right['price'];

                return $leftPrice <=> $rightPrice ?: $left['id'] <=> $right['id'];
            })
            ->values();
    }

    /**
     * Get best price for a product
     *
     * @param int $productId
     * @return ProductLink|null
     */
    public function getBestPrice(int $productId): ?ProductLink
    {
        return ProductLink::where('product_id', $productId)
            ->where('is_best_price', true)
            ->with(['program', 'link'])
            ->first();
    }

    /**
     * Get products by category
     *
     * @param string $category
     * @param int $limit
     * @return Collection
     */
    public function getProductsByCategory(string $category, int $limit = 20): Collection
    {
        return Product::where('category', $category)
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['productLinks.program', 'activeCommissions'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get products sorted by commission rate
     *
     * @param int $limit
     * @return Collection
     */
    public function getProductsByCommission(int $limit = 20): Collection
    {
        return Product::leftJoin('product_commissions', function ($join) {
                $join->on('products.id', '=', 'product_commissions.product_id')
                     ->where('product_commissions.status', 'active');
            })
            ->select('products.*')
            ->selectRaw('MAX(product_commissions.commission_rate) as max_commission')
            ->where('products.status', Product::STATUS_ACTIVE)
            ->groupBy('products.id')
            ->orderBy('max_commission', 'desc')
            ->with(['productLinks.program', 'activeCommissions'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get maximum commission rate for a product
     *
     * @param int $productId
     * @return float
     */
    public function getMaxCommissionRate(int $productId): float
    {
        $product = Product::find($productId);
        return $product ? $product->max_commission_rate : 0.0;
    }

    /**
     * Get best commission platform for a product
     *
     * @param int $productId
     * @return ProductCommission|null
     */
    public function getBestCommissionPlatform(int $productId): ?ProductCommission
    {
        $product = Product::find($productId);
        return $product ? $product->best_commission_platform : null;
    }

    /**
     * Search products with commission priority
     *
     * @param string $searchTerm
     * @param int $limit
     * @return Collection
     */
    public function searchProducts(string $searchTerm, int $limit = 20): Collection
    {
        $products = Product::where('status', Product::STATUS_ACTIVE)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('brand', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('category', 'LIKE', "%{$searchTerm}%");
            })
            ->with(['productLinks.program', 'activeCommissions.program'])
            ->get()
            ->map(function ($product) {
                return [
                    'product' => $product,
                    'max_commission' => $product->max_commission_rate,
                ];
            })
            ->sortByDesc('max_commission')
            ->take($limit)
            ->pluck('product');

        return $products;
    }

    /**
     * Update best price flags for a product
     *
     * @param int $productId
     * @return void
     */
    public function updateBestPriceFlags(int $productId): void
    {
        // Reset all best price flags
        ProductLink::where('product_id', $productId)
            ->update(['is_best_price' => false]);

        // Find lowest price
        $bestPriceLink = ProductLink::where('product_id', $productId)
            ->orderBy('price', 'asc')
            ->first();

        if ($bestPriceLink) {
            $bestPriceLink->update(['is_best_price' => true]);
        }
    }
}

