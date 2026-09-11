<?php

class Seo
{
    public const LOCALES = [
        'fr' => 'fr_FR',
        'en' => 'en_GB',
        'it' => 'it_IT',
        'de' => 'de_DE',
        'nl' => 'nl_NL',
        'pl' => 'pl_PL',
    ];

    public static function pages(): array
    {
        return ['home', 'reserver', 'contact', 'mentions'];
    }

    public static function description(string $page): string
    {
        $key = match ($page) {
            'reserver' => 'meta.booking_desc',
            'contact' => 'meta.contact_desc',
            'mentions' => 'meta.legal_desc',
            default => 'meta.description',
        };
        $value = t($key);
        return $value === $key ? t('meta.description') : $value;
    }

    public static function image(): string
    {
        return asset('img/hero/sea-view.jpg');
    }

    public static function outputRobots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /api\n";
        echo 'Sitemap: ' . base_url('sitemap.xml') . "\n";
        exit;
    }

    public static function outputSitemap(): void
    {
        $urls = [];
        foreach (self::pages() as $page) {
            foreach (I18n::LANGS as $lang) {
                $loc = url_for($page, [], $lang);
                $alternates = [];
                foreach (I18n::LANGS as $alt) {
                    $alternates[$alt] = url_for($page, [], $alt);
                }
                $alternates['x-default'] = url_for($page, [], config('default_lang', 'en'));
                $urls[] = ['loc' => $loc, 'alternates' => $alternates];
            }
        }
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            foreach ($url['alternates'] as $lang => $href) {
                echo '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($lang, ENT_XML1, 'UTF-8') . '" href="' . htmlspecialchars($href, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "\" />\n";
            }
            echo "  </url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public static function jsonLd(string $page): array
    {
        $home = url_for('home');
        $minRate = 250;
        try {
            foreach (Pricing::seasons() as $s) {
                if (!(int) $s['is_closed'] && (float) $s['nightly_rate'] > 0) {
                    $minRate = min($minRate, (float) $s['nightly_rate']);
                }
            }
        } catch (Throwable $e) {
            // Schema still valid without live rates.
        }
        $data = [
            '@context' => 'https://schema.org',
            '@type' => ['VacationRental', 'LodgingBusiness'],
            'name' => 'Villa Mariolu Beach',
            'description' => t('meta.description'),
            'url' => $home,
            'image' => self::image(),
            'email' => setting('email', 'VillaMarioluBeach@gmail.com'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Via Patroclo',
                'addressLocality' => 'Budoni',
                'postalCode' => '07051',
                'addressRegion' => 'SS',
                'addressCountry' => 'IT',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => 40.6975,
                'longitude' => 9.7252,
            ],
            'checkinTime' => setting('checkin_time', '16:00'),
            'checkoutTime' => setting('checkout_time', '10:00'),
            'occupancy' => [
                '@type' => 'QuantitativeValue',
                'minValue' => 2,
                'maxValue' => 6,
            ],
            'numberOfBedrooms' => 2,
            'identifier' => [
                ['@type' => 'PropertyValue', 'name' => 'CIN', 'value' => setting('cin', 'IT090091C2000S5635')],
                ['@type' => 'PropertyValue', 'name' => 'IUN', 'value' => setting('iun', 'S5635')],
            ],
            'priceRange' => '€' . (int) $minRate . '+ / night',
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Sea view'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Air conditioning'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Private parking'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Wi-Fi'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Beach 5 minutes walk'],
            ],
            'sameAs' => array_values(array_filter([
                setting('instagram'),
                setting('facebook'),
                setting('google_reviews'),
            ])),
        ];
        if ($page === 'reserver') {
            $data['potentialAction'] = [
                '@type' => 'ReserveAction',
                'target' => url_for('reserver'),
            ];
        }
        return $data;
    }
}
