<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SkinType extends Model { protected $fillable=['code','label','icon','color','description','products_count','tags']; protected $casts=['tags'=>'array']; }
