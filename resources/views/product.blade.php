<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('products') }}" class="hover:underline text-gray-600">Products</a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-700 font-semibold">{{ $product->name }}</span>
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <livewire:product :product="$product" />
            </div>
        </div>
    </div>
</x-app-layout>
