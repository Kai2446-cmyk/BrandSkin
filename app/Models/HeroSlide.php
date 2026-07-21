<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HeroSlide extends Model { protected $fillable=['label','title','subtitle','image','alt','sort_order','is_active']; protected $casts=['is_active'=>'boolean']; }
