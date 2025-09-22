<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add price to shops
        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('url');
        });

        // Migrate price from products to shops
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $shop = DB::table('shops')->where('product_id', $product->id)->first();
            if ($shop && $product->price !== null) {
                DB::table('shops')->where('id', $shop->id)->update(['price' => $product->price]);
            }
        }

        // Remove price from products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        // Add price back to products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('description');
        });

        // Migrate price back from shops to products
        $shops = DB::table('shops')->whereNotNull('price')->get();
        foreach ($shops as $shop) {
            DB::table('products')->where('id', $shop->product_id)->update(['price' => $shop->price]);
        }

        // Remove price from shops
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};

