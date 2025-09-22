<?php

use App\Models\Product;
use App\Services\ProductShopScraper;
use Illuminate\Contracts\Auth\Authenticatable;
use function Livewire\Volt\{state, mount};

state(['products' => []]);

mount(function (Product $productModel, Authenticatable $user) {
    $this->products = $productModel->where('user_id', $user->id)->with('shops')->get();
});

$scrapeShops = function () {
    $scraper = new ProductShopScraper();
    $count = $scraper->scrapeAllShops();
    $this->products = Product::where('user_id', auth()->id())->with('shops')->get();
}


?>

<div class="p-6 lg:p-8 bg-white shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-lg">
    <div class="flex justify-between items-center">  <h2 class="mb-4 font-medium text-xl">Your Products</h2>
        <div class="flex justify-end mb-4">
            <button
                wire:click="scrapeShops"
                class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 disabled:opacity-50"
                wire:loading.attr="disabled"
            >
                <span wire:loading>Scraping...</span>
                <span wire:loading.remove>Scrape All Shop Prices</span>
            </button>
        </div></div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($products as $product)
            <div class="p-4 bg-[#FDFDFC] rounded-lg shadow hover:shadow-lg transition-shadow border border-[#e3e3e0]">
                <h3 class="font-semibold text-lg mb-2">{{ $product->name }}</h3>
                @php
                    $lowestPrice = $product->shops->min('price');
                @endphp
                <p class="text-[#706f6c] mb-1">
                    @if($lowestPrice !== null)
                        ${{ number_format($lowestPrice, 2) }}
                    @else
                        <span class="italic text-gray-400">No price available</span>
                    @endif
                </p>
                <div class="text-xs text-gray-500">Added: {{ $product->created_at->format('M d, Y') }}</div>
            </div>
        @empty
            <div class="col-span-full text-center text-gray-500 py-8">
                No products found.
            </div>
        @endforelse
    </div>
</div>
