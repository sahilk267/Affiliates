<?php

namespace App\Services;

use InvalidArgumentException;

class ProductRankingService
{
    public const FEATURES = [
        'price',
        'availability',
        'rating',
        'price_history',
        'demand',
        'referral_margin',
    ];

    /**
     * Score already-normalized offer features using explicitly supplied weights.
     * Each feature must be in the range 0..1 and weights must sum to 100.
     * The service does not decide how raw prices or demand are normalized.
     *
     * @param array<string, float|int> $features
     * @param array<string, float|int> $weights
     */
    public function score(array $features, array $weights): float
    {
        $this->validateWeights($weights);
        $score = 0.0;

        foreach (self::FEATURES as $feature) {
            $value = $features[$feature] ?? null;
            if (!is_numeric($value) || (float) $value < 0 || (float) $value > 1) {
                throw new InvalidArgumentException("{$feature} must be a normalized number between 0 and 1.");
            }
            $score += (float) $value * ((float) $weights[$feature] / 100);
        }

        return round($score, 8);
    }

    /**
     * Rank offers by supplied features and weights using a stable caller-provided key.
     *
     * @param iterable<int|string, array<string, mixed>> $offers
     * @param array<string, float|int> $weights
     * @return array<int, array<string, mixed>>
     */
    public function rank(iterable $offers, array $weights): array
    {
        $this->validateWeights($weights);
        $ranked = [];

        foreach ($offers as $position => $offer) {
            if (!isset($offer['id'])) {
                throw new InvalidArgumentException('Every ranked offer must have a stable id.');
            }

            $offer['ranking_score'] = $this->score(
                (array) ($offer['features'] ?? []),
                $weights
            );
            $offer['_stable_position'] = $position;
            $ranked[] = $offer;
        }

        usort($ranked, function (array $left, array $right): int {
            $scoreOrder = $right['ranking_score'] <=> $left['ranking_score'];
            if ($scoreOrder !== 0) {
                return $scoreOrder;
            }

            return (string) $left['id'] <=> (string) $right['id'];
        });

        return array_map(function (array $offer): array {
            unset($offer['_stable_position']);
            return $offer;
        }, $ranked);
    }

    /**
     * @param array<string, float|int> $weights
     */
    private function validateWeights(array $weights): void
    {
        $missing = array_diff(self::FEATURES, array_keys($weights));
        if ($missing !== []) {
            throw new InvalidArgumentException('Weights are missing: ' . implode(', ', $missing));
        }

        $total = 0.0;
        foreach (self::FEATURES as $feature) {
            if (!is_numeric($weights[$feature]) || (float) $weights[$feature] < 0) {
                throw new InvalidArgumentException("{$feature} weight must be a non-negative number.");
            }
            $total += (float) $weights[$feature];
        }

        if (abs($total - 100.0) > 0.000001) {
            throw new InvalidArgumentException('Ranking weights must sum to 100.');
        }
    }
}
