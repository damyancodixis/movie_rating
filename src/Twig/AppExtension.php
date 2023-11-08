<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [
            new TwigFilter('shorten_number', [$this, 'shortenNumber']),
        ];
    }

    public function shortenNumber(int $number): string
    {
        if ($number >= 1_000_000) {
            return round($number / 1_000_000, 1) . 'M';
        }
        if ($number >= 100_000) {
            return round($number / 1000) . 'K';
        }

        return number_format($number);
    }
}
