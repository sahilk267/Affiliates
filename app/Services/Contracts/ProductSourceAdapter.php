<?php

namespace App\Services\Contracts;

interface ProductSourceAdapter
{
    /**
     * Stable internal identifier for the approved source adapter.
     */
    public function source(): string;

    /**
     * Normalize one provider response into ProductPriceSnapshotService input.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function normalizeOffer(array $payload): array;
}
