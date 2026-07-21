<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name','email','password','role','provider','profile_image'];
    protected $hidden = ['password'];

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }
}
