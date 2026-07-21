<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
 public function run(): void
 {
  DB::table('hero_slides')->insert([
   ['label'=>'POWERSTAY MATTE FOUNDATION','title'=>"24H AIRBRUSHED\nSMOOTH COVER",'subtitle'=>'THE TRUEST MATCH IN 25 EXTENSIVE SHADES','image'=>'https://img.rocket.new/generatedImages/rocket_gen_img_1deedb7f9-1769009931732.png','alt'=>'Professional makeup artist applying foundation','sort_order'=>1,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
   ['label'=>'ULTRA GLOW SERUM','title'=>"RADIANCE IN\nEVERY DROP",'subtitle'=>'VITAMIN C + NIACINAMIDE FOR GLASS SKIN','image'=>'https://images.unsplash.com/photo-1639616624509-668b3866469c','alt'=>'Luxury skincare products on dark marble','sort_order'=>2,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
   ['label'=>'POWERSKIN COLLECTION','title'=>"DISCOVER YOUR\nTRUE GLOW",'subtitle'=>'PREMIUM SKINCARE ENGINEERED FOR EVERY SKIN TYPE','image'=>'https://img.rocket.new/generatedImages/rocket_gen_img_1e5104695-1772073714103.png','alt'=>'Beautiful woman with flawless skin','sort_order'=>3,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
  ]);
  $products=[
   ['GLOWSKIN PERFECT COVER POWDER FOUNDATION','Foundation',185000,185000,'https://images.unsplash.com/photo-1590156423656-60c3f02a5e80','NEW',1,0,null,0,0,['#F5D5B0','#E8B887','#C9936A','#A67550','#7A4F2E','#5C3520']],
   ['GLOWSKIN ULTRA COVER LIQUID MATTE FOUNDATION','Foundation',170000,170000,'https://img.rocket.new/generatedImages/rocket_gen_img_1164641cc-1772071557956.png','NEW',1,1,1,0,0,['#FAEBD7','#F5CBA7','#E59866','#CA6F1E','#935116','#6E2C00']],
   ['GLOWSKIN POWERSKIN RADIANT TINTED MOISTURIZER','Skincare',164000,164000,'https://img.rocket.new/generatedImages/rocket_gen_img_19bbf9167-1772070088385.png','NEW',1,1,3,0,0,['#FFF8F0','#FAE5D3','#F0C27F','#E59866','#BA4A00']],
   ['GLOWSKIN POWERSTAY TRANSFERPROOF MATTE LIP CREAM','Lip',143000,143000,'https://images.unsplash.com/photo-1619229904468-2569e8e4e671','NEW',1,1,4,1,50,['#E8A0BF','#C0392B','#8E0000','#4A0000','#2C0000']],
   ['GLOWSKIN ULTRA COVER LIQUID MATT FOUNDATION','Foundation',170000,170000,'https://img.rocket.new/generatedImages/rocket_gen_img_17580e43b-1772528743793.png',null,0,1,1,0,0,['#FAEBD7','#F5CBA7','#E59866','#CA6F1E','#935116']],
   ['GLOWSKIN SILKY SMOOTH TRANSLUCENT POWDER 35G','Powder',167000,167000,'https://img.rocket.new/generatedImages/rocket_gen_img_1e265116a-1764715771790.png',null,0,1,2,0,0,['#FFF5E4','#F5DEB3','#DEB887','#C4A882']],
   ['GLOWSKIN POWDER FOUNDATION SALE','Foundation',92500,185000,'https://images.unsplash.com/photo-1642500737439-b8aea1c25f8f',null,0,0,null,1,50,['#F5D5B0','#E8B887']],
   ['GLOWSKIN ULTRA GLOW VITAMIN C SERUM','Serum',110000,220000,'https://images.unsplash.com/photo-1721925366845-da09de1675a3',null,0,0,null,1,50,['#C4922A','#E8B84B']],
   ['GLOWSKIN PROFESSIONAL MAKEUP BRUSH SET','Tools',125000,250000,'https://images.unsplash.com/photo-1556774943-97545845b53e',null,0,0,null,1,50,['#C4922A','#6B4B1D']],
   ['GLOWSKIN HYDRATING FACIAL MIST','Skincare',57500,115000,'https://img.rocket.new/generatedImages/rocket_gen_img_1bad9ac31-1771520861429.png',null,0,0,null,1,50,['#B7D1C0','#4A7A3A']],
  ];
  foreach($products as $p){DB::table('products')->insert(['name'=>$p[0],'slug'=>Str::slug($p[0]),'subtitle'=>'COVER MELT AND BLUR POWDER','description'=>'Achieve a flawless, airbrushed complexion with GlowSkin. This product keeps the original cosmetic brand content from the front-end concept and is ready to be edited from the database.','price'=>$p[2],'original_price'=>$p[3],'category'=>$p[1],'image'=>$p[4],'alt'=>$p[0],'badge'=>$p[5],'is_new_arrival'=>$p[6],'is_best_seller'=>$p[7],'best_seller_rank'=>$p[8],'is_on_sale'=>$p[9],'discount_percentage'=>$p[10],'colors'=>json_encode($p[11]),'selected_color'=>$p[11][0],'stock'=>100,'created_at'=>now(),'updated_at'=>now()]);}
  $articles=[
   ['Easy Ways to Create Perfect Eyebrow Shape for Your Face','Tutorial','https://img.rocket.new/generatedImages/rocket_gen_img_10cdc502c-1770098259973.png'],['Create Neat and Natural Brow Look with GlowSkin Brow Pencil','How To','https://images.unsplash.com/photo-1631192491916-93a18d8eecf3'],['Must Know: Difference Between Compact Powder and Two Way Cake','Tips','https://images.unsplash.com/photo-1685764290212-b379813f47c1'],['Skincare Routine for Oily Skin: Morning to Night Guide','Skincare','https://images.unsplash.com/photo-1552046123-6e6bbe0f06d4'],['Top 5 Ingredients to Look for in a Moisturizer','Skincare','https://img.rocket.new/generatedImages/rocket_gen_img_11d486f43-1772227160918.png'],['How to Layer Skincare Products in the Right Order','Tips','https://img.rocket.new/generatedImages/rocket_gen_img_12f3361f9-1772057256259.png'],['The Ultimate Guide to Choosing the Right Foundation Shade','Makeup','https://img.rocket.new/generatedImages/rocket_gen_img_1d79a4177-1772070077523.png'],['Glow Up: How to Achieve Dewy Skin All Day Long','Tutorial','https://img.rocket.new/generatedImages/rocket_gen_img_19b6a4141-1772127356540.png'],['Lip Care 101: Exfoliate, Hydrate, and Protect Your Lips','Tips','https://img.rocket.new/generatedImages/rocket_gen_img_19a943f51-1767837012499.png']
  ];
  foreach($articles as $a){DB::table('articles')->insert(['title'=>$a[0],'slug'=>Str::slug($a[0]),'excerpt'=>'Beauty article content prepared from the original front-end data.','content'=>'This article is dummy database content generated from the available front-end structure. Replace this text from the database or admin module later while keeping the same design layout.','image'=>$a[2],'alt'=>$a[0],'category'=>$a[1],'tag'=>$a[1],'published_at'=>'2026-06-30','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);}
  DB::table('skin_types')->insert([
   ['code'=>'dry','label'=>'DRY SKIN','icon'=>'SunIcon','color'=>'#C4922A','description'=>'Feels tight, looks flaky. Needs intense hydration and barrier-repair ingredients.','products_count'=>48,'tags'=>json_encode(['Intense Hydration','Barrier Repair','Rich Moisturizers','Gentle Cleansers']),'created_at'=>now(),'updated_at'=>now()],
   ['code'=>'normal','label'=>'NORMAL SKIN','icon'=>'SparklesIcon','color'=>'#4A7A3A','description'=>'Balanced oil and moisture. Focus on maintenance and protection.','products_count'=>62,'tags'=>json_encode(['Daily Protection','Antioxidants','Balanced Formula','Preventive Care']),'created_at'=>now(),'updated_at'=>now()],
   ['code'=>'oily','label'=>'OILY SKIN','icon'=>'BeakerIcon','color'=>'#5B8A4A','description'=>'Excess sebum, prone to shine and breakouts. Needs oil-control and lightweight formulas.','products_count'=>55,'tags'=>json_encode(['Oil Control','Mattifying','Non-Comedogenic','Pore Minimizing']),'created_at'=>now(),'updated_at'=>now()],
  ]);
  DB::table('site_settings')->insert([['key'=>'web_name','value'=>'GlowSkin'],['key'=>'instagram','value'=>'https://instagram.com/glowskin.official'],['key'=>'logo','value'=>'assets/images/app_logo.png']]);
 }
}
