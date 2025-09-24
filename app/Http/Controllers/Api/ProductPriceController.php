<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductShopScraper;
use Illuminate\Http\JsonResponse;

class ProductPriceController extends Controller
{
    public function getPrice(Request $request): JsonResponse
    {
        $url = $request->query('url');
        if (!$url) {
            return response()->json(['error' => 'Missing url parameter'], 400);
        }
        $scraper = app(ProductShopScraper::class);
        $price = $scraper->scrapePriceFromUrl($url);
        if ($price === null) {
            return response()->json([
                'error' => 'Price not found',
            ], 404);
        }
        return response()->json([
            'price' => $price
        ]);
    }
}
