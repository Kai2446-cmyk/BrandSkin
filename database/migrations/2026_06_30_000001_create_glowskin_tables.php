<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('hero_slides', function(Blueprint $t){$t->id();$t->string('label');$t->text('title');$t->text('subtitle');$t->text('image');$t->text('alt')->nullable();$t->unsignedInteger('sort_order')->default(1);$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('products', function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->string('subtitle')->nullable();$t->longText('description')->nullable();$t->unsignedInteger('price');$t->unsignedInteger('original_price')->nullable();$t->string('category')->nullable();$t->text('image')->nullable();$t->text('alt')->nullable();$t->string('badge')->nullable();$t->json('colors')->nullable();$t->string('selected_color')->nullable();$t->boolean('is_new_arrival')->default(false);$t->boolean('is_best_seller')->default(false);$t->unsignedInteger('best_seller_rank')->nullable();$t->boolean('is_on_sale')->default(false);$t->unsignedTinyInteger('discount_percentage')->default(0);$t->unsignedInteger('stock')->default(100);$t->timestamps();});
  Schema::create('articles', function(Blueprint $t){$t->id();$t->string('title');$t->string('slug')->unique();$t->text('excerpt')->nullable();$t->longText('content')->nullable();$t->text('image')->nullable();$t->text('alt')->nullable();$t->string('category')->nullable();$t->string('tag')->nullable();$t->date('published_at')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('skin_types', function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('label');$t->string('icon')->nullable();$t->string('color')->nullable();$t->text('description')->nullable();$t->unsignedInteger('products_count')->default(0);$t->json('tags')->nullable();$t->timestamps();});
  Schema::create('site_settings', function(Blueprint $t){$t->id();$t->string('key')->unique();$t->longText('value')->nullable();});
 }
 public function down(): void {Schema::dropIfExists('site_settings');Schema::dropIfExists('skin_types');Schema::dropIfExists('articles');Schema::dropIfExists('products');Schema::dropIfExists('hero_slides');}
};
