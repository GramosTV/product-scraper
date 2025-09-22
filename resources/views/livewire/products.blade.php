<?php

use App\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;
use function Livewire\Volt\{state, mount};

state(['products' => []]);

mount(function (Product $productModel, Authenticatable $user) {
    $this->products = $productModel->where('user_id', $user->id)->with('shops')->get();
});

?>

<div class="p-6 lg:p-8 bg-white shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-lg">
    <h2 class="mb-4 font-medium text-xl">Your Products</h2>
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
