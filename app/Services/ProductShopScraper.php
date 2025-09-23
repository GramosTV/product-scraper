<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Log;

class ProductShopScraper
{
    public function scrapeShop(Shop $shop): ?float
    {
        try {
            $escapedUrl = escapeshellarg($shop->url);
            $nodeScript = base_path('scrape-html.cjs');
            $command = "node $nodeScript $escapedUrl 2>&1";
            Log::debug('Executing Playwright command', ['command' => $command]);

            $html = shell_exec($command);

            if (!$html) {
                Log::warning('No HTML returned', ['url' => $shop->url]);
                return null;
            }

            Log::debug('HTML fetched', ['length' => strlen($html)]);

            // --- 1. JSON-LD ---
            preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/is', $html, $matches);
            foreach ($matches[1] as $jsonText) {
                $json = json_decode($jsonText, true);
                if (is_array($json)) {
                    $price = $this->extractPriceFromJsonLd($json);
                    if ($price !== null) return $price;
                }
            }

            // --- 2. DOM XPath parsing ---
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            $queries = [
                '//*[@itemprop="price"]',
                '//meta[@itemprop="price"]/@content',
                '//*[@property="product:price" or @property="schema:price" or @property="product:sale_price"]',
                '//meta[@property="product:price" or @property="schema:price" or @property="product:sale_price"]/@content',
                '//*[@property="product:price:amount" or @property="product:sale_price:amount"]',
                '//meta[@property="product:price:amount" or @property="product:sale_price:amount"]/@content',
            ];
            foreach ($queries as $query) {
                $nodes = $xpath->query($query);
                foreach ($nodes as $node) {
                    $price = $node instanceof \DOMAttr ? $node->value : $node->nodeValue;
                    Log::debug('Found price', ['price' => $price]);
                    $price = $this->normalizePrice($price);
                    if (is_numeric($price)) return (float)$price;
                }
            }

        } catch (\Exception $e) {
            Log::error('Scraper error for shop ' . $shop->id, ['message' => $e->getMessage()]);
        }

        return null;
    }

    protected function normalizePrice(string $price): ?float
    {
        $price = trim($price);
        $price = preg_replace('/[^\d.,]/', '', $price);

        if ($price === '') {
            return null;
        }


        if (preg_match('/\d+\.\d{3},\d{2}/', $price)) {
            $price = str_replace(['.', ','], ['', '.'], $price);
        }

        elseif (strpos($price, ',') !== false && strpos($price, '.') === false) {
            $price = str_replace(',', '.', $price);
        }

        elseif (preg_match('/\d+,\d{3}\.\d{2}/', $price)) {
            $price = str_replace(',', '', $price);
        }

        return is_numeric($price) ? (float)$price : null;
    }

    protected function extractPriceFromJsonLd(array $json): ?float
    {
        $keys = ['offers.price', 'price', 'schema:price'];
        foreach ($keys as $key) {
            $value = $this->arrayDotGet($json, $key);
            if (is_numeric($value)) return (float)$value;
        }

        // If JSON-LD is array of objects
        foreach ($json as $obj) {
            if (is_array($obj)) {
                $price = $this->extractPriceFromJsonLd($obj);
                if ($price !== null) return $price;
            }
        }

        return null;
    }

    protected function arrayDotGet(array $array, string $key)
    {
        foreach (explode('.', $key) as $segment) {
            if (isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }
        return $array;
    }

    public function scrapePriceFromUrl(string $url): ?float
    {
        $shop = new Shop(['url' => $url]);
        return $this->scrapeShop($shop);
    }

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
