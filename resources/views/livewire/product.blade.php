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

?>

<div class="p-6 lg:p-8 bg-white shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-lg">
    <nav class="mb-6 text-sm text-gray-500">
        <a href="{{ route('products') }}" class="hover:underline">Products</a>
        <span class="mx-2">&gt;</span>
        <span class="text-gray-700 font-semibold">{{ $product?->name }}</span>
    </nav>

    @if($product)

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Shops</h3>
            <form wire:submit.prevent="addShop" class="mb-4 flex gap-2">
                <input type="url" wire:model="shopUrl" placeholder="Shop URL" class="flex-1 border-gray-300 rounded" />
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Add Shop</button>
            </form>
            <ul>
                @forelse ($shops as $shop)
                    @php
                        $domain = parse_url($shop->url, PHP_URL_HOST);
                        $favicon = $domain ? 'https://www.google.com/s2/favicons?domain=' . $domain : null;
                    @endphp
                    <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 shadow-sm hover:shadow transition-all mb-4">
                        <div class="flex items-center gap-2">
                            @if($favicon)
                                <img src="{{ $favicon }}" alt="favicon" class="h-5 w-5 rounded" onerror="this.style.display='none'" />
                            @endif
                            <span class="truncate text-gray-800">{{ $shop->url }}</span>
                        </div>
                        <button wire:click="removeShop({{ $shop->id }})" class="ml-4 px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition">Remove</button>
                    </li>
                @empty
                    <li class="text-gray-400">No shops added.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
