<?php

namespace App\Http\Controllers\Product;

use App\Buyer;
use App\Http\Controllers\ApiController;
use App\Product;
use App\Transaction;
use App\Transformers\TransactionTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBuyerTransactionController extends ApiController
{
    public function __construct(){
        parent::__construct();

        $this->middleware('transform.input:'.TransactionTransformer::class)->only(['store']);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Product $product, Buyer $buyer)
    {

        $rules = [
            'quantity' => 'required|integer|min:1'
        ];

        $this->validate($request, $rules);

        if($buyer->id == $product->seller->id){
            return $this->errorResponse('The buyer must be different from the seller', 409);
        }

        if ($buyer->isVerified()) {
            return $this->errorResponse('The buyer must be verified user', 409);
        }

        if ($product->seller->isVerified()) {
            return $this->errorResponse('The seller must be verified user', 409);
        }

        if (!$product->isAvailable()) {
            return $this->errorResponse('The product is not available', 409); 
        }

        if ($product->quantity < $request->quantity) {
            return $this->errorResponse('The product does not have enough units for this transactions', 409); 
        }

        return DB::transaction(function() use ($request, $product, $buyer){
            $product->quantity -= $request->quantity;
            $product->save();

            $transaction = Transaction::create([
                'quantity' => $request->quantity,
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
            ]);

            return $this->showOne($transaction, 201);
        });
    }
}