<?php
use App\Models\Product;
use App\Models\Shop;
use function Livewire\Volt\{state, mount};

state(['product' => null, 'shops' => [], 'shopUrl' => '']);

mount(function (Product $product) {
    $this->product = $product;
    $this->shops = $product->shops()->get();
});

$saveProduct = function () {
    $this->product->save();
};

$addShop = function () {
    if ($this->shopUrl) {
        $shop = new Shop(['url' => $this->shopUrl, 'product_id' => $this->product->id]);
        $shop->save();
        $this->shops = $this->product->shops()->get();
        $this->shopUrl = '';
    }
};

$removeShop = function ($shopId) {
    Shop::where('id', $shopId)->where('product_id', $this->product->id)->delete();
    $this->shops = $this->product->shops()->get();
};

$scrapeAllShops = function () {
    $scraper = app(\App\Services\ProductShopScraper::class);
    $scraper->scrapeProductShops($this->product);
    $this->shops = $this->product->shops()->get();
};

?>

<div class="p-6 lg:p-8 bg-white shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-lg">

    @if($product)
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Shops</h3>
                <button wire:click="scrapeAllShops" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:bg-blue-700 disabled:opacity-50" wire:loading.attr="disabled">
                    <span wire:loading.remove>Scrape All Shops</span>
                    <span wire:loading>Scraping...</span>
                </button>
            </div>
            <form wire:submit.prevent="addShop" class="mb-4 flex gap-2">
                <input type="url" wire:model.defer="shopUrl" placeholder="Shop URL" class="flex-1 border-gray-300 rounded px-3 py-2" />
                <button type="button" wire:click="addShop" class="px-3 py-2 bg-blue-600 text-white rounded">Add</button>
            </form>
            <ul>
                @php
                    $sortedShops = collect($shops)->sortBy(function($shop) {
                        return is_null($shop->price) ? INF : $shop->price;
                    });
                @endphp
                @forelse ($sortedShops as $shop)
                    @php
                        $domain = parse_url($shop->url, PHP_URL_HOST);
                        $favicon = $domain ? 'https://www.google.com/s2/favicons?domain=' . $domain : null;
                    @endphp
                    <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 shadow-sm hover:shadow transition-all mb-4">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            @if($favicon)
                                <img src="{{ $favicon }}" alt="favicon" class="h-5 w-5 rounded" onerror="this.style.display='none'" />
                            @endif
                            <a href="{{ $shop->url }}" target="_blank" rel="noopener noreferrer" class="truncate text-gray-800 hover:underline" title="{{ $shop->url }}">
                                {{ strlen($shop->url) > 100 ? substr($shop->url, 0, 100) . '…' : $shop->url }}
                            </a>
                        </div>
                        <div class="flex items-center gap-4 ml-4">
                            <span class="font-bold text-base text-gray-800 min-w-[80px] text-right ml-8">
                                @if($shop->price !== null)
                                    ${{ number_format($shop->price, 2) }}
                                @else
                                    <span class="italic text-gray-400 font-normal">No price</span>
                                @endif
                            </span>
                            <button wire:click="removeShop({{ $shop->id }})" class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition">Remove</button>
                        </div>
                    </li>
                @empty
                    <li class="text-gray-400">No shops added.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
