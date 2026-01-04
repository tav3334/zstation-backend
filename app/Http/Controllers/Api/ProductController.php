<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Liste tous les produits disponibles
     * GET /api/products
     */
    public function index()
    {
        $products = Product::where('is_available', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    /**
     * Vendre un produit
     * POST /api/products/sell
     */
    public function sell(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,mobile'
        ]);

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($request->product_id);

            // Vérifier le stock
            if (!$product->isInStock($request->quantity)) {
                return response()->json([
                    'message' => 'Stock insuffisant pour ce produit'
                ], 400);
            }

            // Calculer le prix total
            $totalPrice = $product->price * $request->quantity;

            // Créer la vente
            $sale = ProductSale::create([
                'product_id' => $product->id,
                'staff_id' => auth()->id(),
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
                'total_price' => $totalPrice,
                'payment_method' => $request->payment_method,
                'sale_date' => now()
            ]);

            // Déduire du stock
            $product->decrementStock($request->quantity);

            DB::commit();

            return response()->json([
                'message' => 'Vente enregistrée avec succès',
                'sale' => $sale->load('product'),
                'remaining_stock' => $product->fresh()->stock
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la vente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des ventes
     * GET /api/products/sales
     */
    public function sales(Request $request)
    {
        $limit = $request->get('limit', 50);

        $sales = ProductSale::with(['product', 'staff'])
            ->orderByDesc('sale_date')
            ->limit($limit)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'product_name' => $sale->product->name,
                    'product_size' => $sale->product->size,
                    'quantity' => $sale->quantity,
                    'unit_price' => (float) $sale->unit_price,
                    'total_price' => (float) $sale->total_price,
                    'payment_method' => $sale->payment_method,
                    'staff_name' => $sale->staff->name ?? 'N/A',
                    'sale_date' => $sale->sale_date->toISOString()
                ];
            });

        return response()->json(['sales' => $sales]);
    }

    /**
     * Mettre à jour le stock
     * PUT /api/products/{id}/stock
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0'
        ]);

        $product = Product::findOrFail($id);
        $product->update(['stock' => $request->stock]);

        return response()->json([
            'message' => 'Stock mis à jour',
            'product' => $product
        ]);
    }
}
