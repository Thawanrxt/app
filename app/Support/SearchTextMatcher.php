<?php

namespace App\Support;

use Illuminate\Support\Collection;

class SearchTextMatcher
{
    public static function matches(string|array|null $haystack, ?string $needle): bool
    {
        $normalizedNeedle = self::normalize($needle);

        if ($normalizedNeedle === '') {
            return true;
        }

        $normalizedHaystack = self::normalize(is_array($haystack) ? implode(' ', $haystack) : $haystack);

        if ($normalizedHaystack === '') {
            return false;
        }

        if (str_contains($normalizedHaystack, $normalizedNeedle)) {
            return true;
        }

        foreach (self::tokens($normalizedHaystack) as $token) {
            foreach (self::tokenVariants($token) as $variant) {
                if ($variant !== '' && str_starts_with($variant, $normalizedNeedle)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function matchesFromStart(string|array|null $haystack, ?string $needle): bool
    {
        $normalizedNeedle = self::normalize($needle);

        if ($normalizedNeedle === '') {
            return true;
        }

        $normalizedHaystack = self::normalize(is_array($haystack) ? implode(' ', $haystack) : $haystack);

        if ($normalizedHaystack === '') {
            return false;
        }

        if (str_starts_with($normalizedHaystack, $normalizedNeedle)) {
            return true;
        }

        foreach (self::tokens($normalizedHaystack) as $token) {
            foreach (self::tokenVariants($token) as $variant) {
                if ($variant !== '' && str_starts_with($variant, $normalizedNeedle)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, callable|string>  $extractors
     */
    public static function filterByPriority(Collection $items, array $extractors, ?string $needle): Collection
    {
        $normalizedNeedle = self::normalize($needle);

        if ($normalizedNeedle === '') {
            return $items->values();
        }

        foreach ($extractors as $extractor) {
            $matched = $items
                ->filter(function ($item) use ($extractor, $normalizedNeedle): bool {
                    $value = is_callable($extractor)
                        ? $extractor($item)
                        : data_get($item, (string) $extractor);

                    return self::matchesFromStart($value, $normalizedNeedle);
                })
                ->values();

            if ($matched->isNotEmpty()) {
                return $matched;
            }
        }

        return collect();
    }

    public static function normalize(?string $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @return array<int, string>
     */
    public static function tokens(string $value): array
    {
        return array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', self::normalize($value)) ?: [],
            static fn (string $token): bool => $token !== ''
        ));
    }

    /**
     * @return array<int, string>
     */
    private static function tokenVariants(string $token): array
    {
        $variants = [$token];
        $withoutLeadingVowels = preg_replace('/^[เแโใไ]+/u', '', $token) ?? $token;

        if ($withoutLeadingVowels !== '' && $withoutLeadingVowels !== $token) {
            $variants[] = $withoutLeadingVowels;
        }

        return array_values(array_unique($variants));
    }
}
