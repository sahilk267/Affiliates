<?php

namespace App\Services;

use App\ProductLink;
use App\ProductPriceSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductPriceSnapshotService
{
    /**
     * Record one source observation. Missing values remain null instead of being inferred.
     *
     * @param array<string, mixed> $observation
     */
    public function record(ProductLink $productLink, array $observation): ProductPriceSnapshot
    {
        $source = trim((string) ($observation['source'] ?? ''));
        if ($source === '') {
            throw new InvalidArgumentException('A source is required for every price snapshot.');
        }

        $observedAt = $observation['observed_at'] ?? now();
        try {
            $observedAt = Carbon::parse($observedAt);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('The observed_at value is invalid.', 0, $exception);
        }

        $price = $this->nullableNumber($observation['price'] ?? null, 'price', 12, 2);
        $originalPrice = $this->nullableNumber($observation['original_price'] ?? null, 'original_price', 12, 2);
        $rating = $this->nullableNumber($observation['rating'] ?? null, 'rating', 4, 2);
        $discountPercent = $this->nullableNumber($observation['discount_percent'] ?? null, 'discount_percent', 5, 2);

        if ($rating !== null && ($rating < 0 || $rating > 5)) {
            throw new InvalidArgumentException('rating must be between 0 and 5.');
        }
        if ($discountPercent !== null && ($discountPercent < 0 || $discountPercent > 100)) {
            throw new InvalidArgumentException('discount_percent must be between 0 and 100.');
        }

        $metadata = $observation['metadata'] ?? null;
        if ($metadata !== null && !is_array($metadata)) {
            throw new InvalidArgumentException('metadata must be an array or null.');
        }

        return DB::transaction(function () use (
            $productLink,
            $source,
            $observation,
            $observedAt,
            $price,
            $originalPrice,
            $rating,
            $discountPercent,
            $metadata
        ): ProductPriceSnapshot {
            return $productLink->priceSnapshots()->create([
                'source' => $source,
                'external_offer_id' => $this->nullableString($observation['external_offer_id'] ?? null),
                'observed_at' => $observedAt,
                'price' => $price,
                'currency' => $this->nullableString($observation['currency'] ?? $productLink->currency),
                'availability' => $this->nullableString($observation['availability'] ?? null),
                'rating' => $rating,
                'rating_count' => $this->nullableInteger($observation['rating_count'] ?? null),
                'original_price' => $originalPrice,
                'discount_percent' => $discountPercent,
                'metadata' => $metadata,
            ]);
        });
    }

    public function latest(ProductLink $productLink, ?string $source = null): ?ProductPriceSnapshot
    {
        $query = $productLink->priceSnapshots()->latest('observed_at');
        if ($source !== null) {
            $query->where('source', $source);
        }

        return $query->first();
    }

    public function history(ProductLink $productLink, ?string $source = null, int $limit = 100)
    {
        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('limit must be between 1 and 1000.');
        }

        $query = $productLink->priceSnapshots()->latest('observed_at');
        if ($source !== null) {
            $query->where('source', $source);
        }

        return $query->limit($limit)->get();
    }

    private function nullableNumber(mixed $value, string $field, int $precision, int $scale): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("{$field} must be numeric or null.");
        }

        $number = (float) $value;
        if ($number < 0) {
            throw new InvalidArgumentException("{$field} must not be negative.");
        }

        $integerDigits = $precision - $scale;
        if ($number >= (10 ** $integerDigits)) {
            throw new InvalidArgumentException("{$field} exceeds the supported range.");
        }

        return number_format($number, $scale, '.', '');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            throw new InvalidArgumentException('rating_count must be a non-negative integer or null.');
        }

        return (int) $value;
    }
}
