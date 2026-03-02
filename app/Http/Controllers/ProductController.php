<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\ProductLink;
use App\ProductCommission;
use App\Program;
use App\Link;
use App\Services\ProductService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display product listing (Consumer-facing)
     */
    public function index(Request $request)
    {
        $filters = [
            'category' => $request->get('category'),
            'brand' => $request->get('brand'),
            'search' => $request->get('search'),
            'status' => 'active',
        ];

        $sortBy = $request->get('sort', 'commission');
        $perPage = $request->get('per_page', 20);

        $products = $this->productService->getProducts($filters, $sortBy, $perPage);

        // For API requests
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        }

        // For web requests (will be used when views are created)
        return view('products.index', compact('products'));
    }

    /**
     * Display product detail page
     */
    public function show(Request $request, $id)
    {
        $product = $this->productService->getProductWithLinks($id);

        if (!$product) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }
            abort(404, 'Product not found');
        }

        $priceComparison = $this->productService->comparePrices($id);

        // For API requests
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'product' => $product,
                    'price_comparison' => $priceComparison,
                ]
            ]);
        }

        // For web requests
        return view('products.show', compact('product', 'priceComparison'));
    }

    /**
     * Redirect to merchant (Buy with me)
     */
    public function buy(Request $request, $productId, $programId)
    {
        $productLink = ProductLink::where('product_id', $productId)
            ->where('program_id', $programId)
            ->with(['link', 'program'])
            ->first();

        if (!$productLink || !$productLink->link) {
            return redirect()->back()->with('error', 'Product link not found');
        }

        // Track click (if user is logged in)
        if (auth()->check()) {
            // You can add click tracking here if needed
        }

        // Redirect to affiliate URL
        return redirect($productLink->link->affiliate_url);
    }

    // ========== ADMIN METHODS ==========

    /**
     * Display products list (Admin)
     */
    public function adminIndex(Request $request)
    {
        $query = Product::with(['productLinks.program', 'activeCommissions']);

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show create product form (Admin)
     */
    public function create()
    {
        $programs = Program::where('status', Program::STATUS_ACTIVE)->get();
        return view('admin.products.create', compact('programs'));
    }

    /**
     * Store new product (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $product = Product::create($request->only([
                'name', 'description', 'image_url', 'category', 'brand', 'sku', 'status'
            ]));

            return redirect()->route('admin.products.index')
                ->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to create product', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to create product')
                ->withInput();
        }
    }

    /**
     * Show edit product form (Admin)
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update product (Admin)
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $product->update($request->only([
                'name', 'description', 'image_url', 'category', 'brand', 'sku', 'status'
            ]));

            return redirect()->route('admin.products.index')
                ->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update product', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update product')
                ->withInput();
        }
    }

    /**
     * Delete product (Admin)
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return redirect()->route('admin.products.index')
                ->with('success', 'Product deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete product', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete product');
        }
    }

    /**
     * Display product commissions (Admin)
     */
    public function commissions($productId)
    {
        $product = Product::with(['productCommissions.program'])->findOrFail($productId);
        $programs = Program::where('status', Program::STATUS_ACTIVE)->get();
        
        return view('admin.products.commissions', compact('product', 'programs'));
    }

    /**
     * Store product commission (Admin)
     */
    public function storeCommission(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'fixed_amount' => 'nullable|numeric|min:0|required_if:commission_type,fixed',
            'category' => 'nullable|string|max:100',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            ProductCommission::updateOrCreate(
                [
                    'product_id' => $productId,
                    'program_id' => $request->program_id,
                ],
                [
                    'commission_rate' => $request->commission_rate,
                    'commission_type' => $request->commission_type,
                    'fixed_amount' => $request->fixed_amount,
                    'category' => $request->category,
                    'min_purchase' => $request->min_purchase ?? 0,
                    'max_commission' => $request->max_commission,
                    'status' => $request->status,
                    'source' => ProductCommission::SOURCE_MANUAL,
                    'last_updated_at' => now(),
                ]
            );

            return redirect()->back()
                ->with('success', 'Commission rate added/updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to store product commission', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to save commission rate')
                ->withInput();
        }
    }

    /**
     * Update product commission (Admin)
     */
    public function updateCommission(Request $request, $productId, $commissionId)
    {
        $commission = ProductCommission::where('product_id', $productId)
            ->findOrFail($commissionId);

        $validator = Validator::make($request->all(), [
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed',
            'fixed_amount' => 'nullable|numeric|min:0|required_if:commission_type,fixed',
            'category' => 'nullable|string|max:100',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $commission->update([
                'commission_rate' => $request->commission_rate,
                'commission_type' => $request->commission_type,
                'fixed_amount' => $request->fixed_amount,
                'category' => $request->category,
                'min_purchase' => $request->min_purchase ?? 0,
                'max_commission' => $request->max_commission,
                'status' => $request->status,
                'last_updated_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'Commission rate updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update product commission', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update commission rate')
                ->withInput();
        }
    }

    /**
     * Delete product commission (Admin)
     */
    public function deleteCommission($productId, $commissionId)
    {
        try {
            $commission = ProductCommission::where('product_id', $productId)
                ->findOrFail($commissionId);
            
            $commission->delete();

            return redirect()->back()
                ->with('success', 'Commission rate deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete product commission', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete commission rate');
        }
    }

    /**
     * Import product commissions from CSV (Admin)
     */
    public function importCommissions(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $file = $request->file('csv_file');
            $data = array_map('str_getcsv', file($file->getRealPath()));
            
            // Skip header row
            $header = array_shift($data);
            
            $imported = 0;
            $errors = [];

            foreach ($data as $row) {
                if (count($row) < 3) continue; // Skip invalid rows

                try {
                    ProductCommission::updateOrCreate(
                        [
                            'product_id' => $productId,
                            'program_id' => $row[0],
                        ],
                        [
                            'commission_rate' => $row[1],
                            'commission_type' => $row[2] ?? 'percentage',
                            'status' => $row[3] ?? 'active',
                            'source' => ProductCommission::SOURCE_IMPORT,
                            'last_updated_at' => now(),
                        ]
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($imported + count($errors) + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} commission rates.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->back()
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to import commissions', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to import commissions: ' . $e->getMessage());
        }
    }
}

