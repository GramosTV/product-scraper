<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Log;

class ProductShopScraper
{
    /**
     * Scrape a shop URL for price using JSON-LD, Schema.org, Microdata, or RDFa.
     */
    public function scrapeShop(Shop $shop): ?float
    {
        try {
            $html = @file_get_contents($shop->url);
            if (!$html) return null;

            // Try JSON-LD first
            if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/is', $html, $matches)) {
                $json = json_decode($matches[1], true);
                if (is_array($json)) {
                    $price = $this->extractPriceFromJsonLd($json);
                    if ($price !== null) return $price;
                }
            }

            // Try Microdata/RDFa (simple approach)
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            // Look for itemprop="price"
            $nodes = $xpath->query('//*[@itemprop="price"]');
            foreach ($nodes as $node) {
                $price = $node->nodeValue;
                if (is_numeric($price)) return (float)$price;
            }

            // Look for meta property="price"
            $nodes = $xpath->query('//meta[@itemprop="price"]');
            foreach ($nodes as $node) {
                $price = $node->getAttribute('content');
                if (is_numeric($price)) return (float)$price;
            }
        } catch (\Exception $e) {
            Log::error('Scraper error for shop ' . $shop->id . ': ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Extract price from JSON-LD (Schema.org Product)
     */
    protected function extractPriceFromJsonLd(array $json): ?float
    {
        if (isset($json['offers']['price']) && is_numeric($json['offers']['price'])) {
            return (float)$json['offers']['price'];
        }
        if (isset($json['price']) && is_numeric($json['price'])) {
            return (float)$json['price'];
        }
        // Sometimes JSON-LD is an array of objects
        if (isset($json[0]) && is_array($json[0])) {
            foreach ($json as $obj) {
                $price = $this->extractPriceFromJsonLd($obj);
                if ($price !== null) return $price;
            }
        }
        return null;
    }

    /**
     * Scrape all shops and update their prices.
     */
    public function scrapeAllShops(): int
    {
        $count = 0;
        foreach (Shop::all() as $shop) {
            $price = $this->scrapeShop($shop);
            if ($price !== null) {
                $shop->price = $price;
                $shop->save();
                $count++;
            }
        }
        return $count;
    }
}


