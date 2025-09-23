<?php

namespace App\Services;

use App\Models\Shop;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ProductShopScraper
{
    /**
     * Scrape a shop URL for price using JSON-LD, Schema.org, Microdata, or RDFa.
     */
    public function scrapeShop(Shop $shop): ?float
    {
        try {
            $jar = new CookieJar();
            $client = new Client([
                'timeout' => 30,
                'cookies' => $jar,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Referer' => 'https://www.google.com/',
                ],
                'allow_redirects' => true,
            ]);
            $response = $client->request('GET', $shop->url);
            $html = $response->getBody()->getContents();
            Log::debug('Request sent', ['url' => $shop->url]);
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

            // Look for RDFa product:price and schema:price
            $nodes = $xpath->query('//*[@property="product:price" or @property="schema:price"]');
            foreach ($nodes as $node) {
                $price = $node->nodeValue;
                if (is_numeric($price)) return (float)$price;
            }
            $nodes = $xpath->query('//meta[@property="product:price" or @property="schema:price"]');
            foreach ($nodes as $node) {
                $price = $node->getAttribute('content');
                if (is_numeric($price)) return (float)$price;
            }
        } catch (RequestException $e) {
            Log::error('Guzzle request error for shop ' . $shop->id . ': ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Scraper error for shop ' . $shop->id . ': ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Scrape a price from a given URL (without needing a Shop model in the database).
     */
    public function scrapePriceFromUrl(string $url): ?float
    {
        $shop = new Shop(['url' => $url]);
        return $this->scrapeShop($shop);
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
        if (isset($json['schema:price']) && is_numeric($json['schema:price'])) {
            return (float)$json['schema:price'];
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
