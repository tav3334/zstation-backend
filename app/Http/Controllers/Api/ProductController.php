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

    /**
     * Créer un nouveau produit
     * POST /api/products
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:snack,drink',
            'price' => 'required|numeric|min:0',
            'size' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string|max:10'
        ]);

        $product = Product::create([
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'size' => $request->size,
            'stock' => $request->stock,
            'is_available' => true,
            'image' => $request->image ?? '📦'
        ]);

        return response()->json([
            'message' => 'Produit créé avec succès',
            'product' => $product
        ], 201);
    }

    /**
     * Mettre à jour un produit
     * PUT /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|in:snack,drink',
            'price' => 'sometimes|required|numeric|min:0',
            'size' => 'nullable|string|max:50',
            'stock' => 'sometimes|required|integer|min:0',
            'is_available' => 'sometimes|boolean',
            'image' => 'nullable|string|max:10'
        ]);

        $product = Product::findOrFail($id);
        $product->update($request->all());

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'product' => $product
        ]);
    }

    /**
     * Supprimer un produit
     * DELETE /api/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Vérifier s'il y a des ventes liées
        $salesCount = ProductSale::where('product_id', $id)->count();

        if ($salesCount > 0) {
            // Ne pas supprimer, juste désactiver
            $product->update(['is_available' => false]);
            return response()->json([
                'message' => 'Produit désactivé (des ventes existent pour ce produit)',
                'product' => $product
            ]);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }

    /**
     * Liste des produits avec stock faible
     * GET /api/products/low-stock
     */
    public function lowStock()
    {
        $threshold = 10; // Stock minimum avant alerte

        $products = Product::where('is_available', true)
            ->where('stock', '<=', $threshold)
            ->orderBy('stock', 'asc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'size' => $product->size,
                    'category' => $product->category,
                    'stock' => $product->stock,
                    'price' => (float) $product->price,
                    'image' => $product->image
                ];
            });

        return response()->json([
            'products' => $products,
            'count' => $products->count()
        ]);
    }

    /**
     * Obtenir tous les produits (y compris inactifs) pour l'admin
     * GET /api/products/all
     */
    public function all()
    {
        $products = Product::orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }
}
