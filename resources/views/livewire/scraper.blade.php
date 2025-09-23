<?php

use App\Services\ProductShopScraper;
use function Livewire\Volt\{state};

state(['url' => '', 'price' => null, 'error' => null]);

$scrapePrice = function () {
    $this->error = null;
    $this->price = null;
    if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
        $this->error = 'Please enter a valid URL.';
        return;
    }
    try {
        $scraper = new ProductShopScraper();
        $price = $scraper->scrapePriceFromUrl($this->url);
        if ($price !== null) {
            $this->price = $price;
        } else {
            $this->error = 'Could not find a price on this page.';
        }
    } catch (\Exception $e) {
        $this->error = 'Error: ' . $e->getMessage();
    }
};

?>

<div class="p-6 lg:p-8 bg-white shadow rounded-lg">
    <h2 class="text-xl font-semibold mb-4">Scrape Product Price</h2>
    <form wire:submit.prevent="scrapePrice" class="mb-6 flex gap-2">
        <input type="url" wire:model="url" placeholder="Enter product shop URL" class="flex-1 border-gray-300 rounded px-3 py-2" required />
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50" wire:loading.attr="disabled">
            <span wire:loading>Scraping...</span>
            <span wire:loading.remove>Scrape</span>
        </button>
    </form>
    @if($price !== null)
        <div class="text-green-700 font-bold mb-2">Price: ${{ number_format($price, 2) }}</div>
    @endif
    @if($error)
        <div class="text-red-600 mb-2">{{ $error }}</div>
    @endif
</div>
