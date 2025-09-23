<?php

use App\Models\Product;
use App\Models\Shop;
use function Livewire\Volt\{state};

state([
    'name' => '',
    'shopUrl' => '',
    'shops' => [],
    'error' => null,
    'success' => null,
]);

$addShop = function () {
    $url = trim($this->shopUrl);
    $this->error = null;
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $this->error = 'Please enter a valid shop URL.';
        return;
    }
    if (in_array($url, $this->shops)) {
        $this->error = 'This shop URL is already added.';
        return;
    }
    $this->shops[] = $url;
    $this->shopUrl = '';
};

$removeShop = function ($idx) {
    array_splice($this->shops, $idx, 1);
};

$createProduct = function () {
    $this->error = null;
    $this->success = null;
    $name = trim($this->name);
    if ($name === '') {
        $this->error = 'Product name is required.';
        return;
    }
    if (count($this->shops) === 0) {
        $this->error = 'Please add at least one shop.';
        return;
    }
    $user = auth()->user();
    $product = Product::create([
        'name' => $name,
        'user_id' => $user->id,
    ]);
    foreach ($this->shops as $url) {
        Shop::create([
            'url' => $url,
            'product_id' => $product->id,
        ]);
    }
    $this->name = '';
    $this->shops = [];
    $this->shopUrl = '';
    $this->success = 'Product created successfully!';
};

?>

<div class="p-6 lg:p-8 bg-white shadow rounded-lg">
    <h2 class="text-xl font-semibold mb-4">Create Product</h2>
    <form wire:submit.prevent="createProduct" class="mb-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
            <input type="text" wire:model.defer="name" class="block w-full border-gray-300 rounded px-3 py-2" required />
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Shops</label>
            <div class="flex gap-2 mb-2">
                <input type="url" wire:model.defer="shopUrl" placeholder="Enter shop URL" class="flex-1 border-gray-300 rounded px-3 py-2" />
                <button type="button" wire:click="addShop" class="px-3 py-2 bg-blue-600 text-white rounded">Add</button>
            </div>
            <ul class="mb-2">
                @foreach($shops as $idx => $url)
                    @php
                        $domain = parse_url($url, PHP_URL_HOST);
                        $favicon = $domain ? 'https://www.google.com/s2/favicons?domain=' . $domain : null;
                    @endphp
                    <li class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 shadow-sm hover:shadow transition-all mb-4">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            @if($favicon)
                                <img src="{{ $favicon }}" alt="favicon" class="h-5 w-5 rounded" onerror="this.style.display='none'" />
                            @endif
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="truncate text-gray-800 hover:underline" title="{{ $url }}">
                                {{ strlen($url) > 100 ? substr($url, 0, 100) . '…' : $url }}
                            </a>
                        </div>
                        <button type="button" wire:click="removeShop({{ $idx }})" class="ml-4 px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition">Remove</button>
                    </li>
                @endforeach
            </ul>
        </div>
        @if($error)
            <div class="text-red-600 mb-2">{{ $error }}</div>
        @endif
        @if($success)
            <div class="text-green-700 font-bold mb-2">{{ $success }}</div>
        @endif
        <button type="submit" class="mt-4 px-3 py-2 bg-blue-600 text-white rounded hover:bg-green-700 focus:bg-green-700">Submit</button>
    </form>
</div>
