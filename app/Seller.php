<?php

namespace App;

class Seller extends User
{
    public function product(){
        return $this->hasMany(Product::class);
    }
}
