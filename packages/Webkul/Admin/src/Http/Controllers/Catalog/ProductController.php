<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\InventoryRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Admin\Http\Resources\AttributeResource;
use Webkul\Admin\Http\Resources\ProductResource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Rules\Slug;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductDownloadableLinkRepository;
use Webkul\Product\Repositories\ProductDownloadableSampleRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /*
    * Using const variable for status
    */
    const ACTIVE_STATUS = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        protected ProductDownloadableSampleRepository $productDownloadableSampleRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductRepository $productRepository,
        protected CustomerRepository $customerRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ProductDataGrid::class)->process();
        }

        $families = $this->attributeFamilyRepository->all();

        return view('admin::catalog.products.index', compact('families'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $families = $this->attributeFamilyRepository->all();

        $configurableFamily = null;

        if ($familyId = request()->get('family')) {
            $configurableFamily = $this->attributeFamilyRepository->find($familyId);
        }

        return view('admin::catalog.products.create', compact('families', 'configurableFamily'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->validate(request(), [
            'type'                => 'required',
            'attribute_family_id' => 'required',
            'sku'                 => ['required', 'unique:products,sku', new Slug],
            'super_attributes'    => 'array|min:1',
            'super_attributes.*'  => 'array|min:1',
        ]);

        if (
            ProductType::hasVariants(request()->input('type'))
            && ! request()->has('super_attributes')
        ) {
            $configurableFamily = $this->attributeFamilyRepository
                ->find(request()->input('attribute_family_id'));

            return new JsonResponse([
                'data' => [
                    'attributes' => AttributeResource::collection($configurableFamily->configurable_attributes),
                ],
            ]);
        }

        Event::dispatch('catalog.product.create.before');

        $product = $this->productRepository->create(request()->only([
            'type',
            'attribute_family_id',
            'sku',
            'super_attributes',
            'family',
        ]));

        Event::dispatch('catalog.product.create.after', $product);

        session()->flash('success', trans('admin::app.catalog.products.create-success'));

        return new JsonResponse([
            'data' => [
                'redirect_url' => route('admin.catalog.products.edit', $product->id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        return view('admin::catalog.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ProductForm $request, int $id)
    {
        Event::dispatch('catalog.product.update.before', $id);

        $product = $this->productRepository->update(request()->all(), $id);

        Event::dispatch('catalog.product.update.after', $product);

        session()->flash('success', trans('admin::app.catalog.products.update-success'));

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Update inventories.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateInventories(InventoryRequest $inventoryRequest, int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        Event::dispatch('catalog.product.update.before', $id);

        $this->productInventoryRepository->saveInventories(request()->all(), $product);

        Event::dispatch('catalog.product.update.after', $product);

        return response()->json([
            'message'      => __('admin::app.catalog.products.saved-inventory-message'),
            'updatedTotal' => $this->productInventoryRepository->where('product_id', $product->id)->sum('qty'),
        ]);
    }

    /**
     * Uploads downloadable file.
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadLink(int $id)
    {
        return response()->json(
            $this->productDownloadableLinkRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Copy a given Product.
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(int $id)
    {
        try {
            Event::dispatch('catalog.product.create.before');

            $product = $this->productRepository->copy($id);

            Event::dispatch('catalog.product.create.after', $product);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->to(route('admin.catalog.products.index'));
        }

        session()->flash('success', trans('admin::app.catalog.products.product-copied'));

        return redirect()->route('admin.catalog.products.edit', $product->id);
    }

    public function showCopyPowersForm()
    {
        // 1. Get sphere_power attribute with eager loading
        $spherePowerAttribute = $this->attributeRepository->findOneByField('code', 'sphere_power');
        
        if (!$spherePowerAttribute) {
            return view('admin::catalog.products.copy-powers', ['products' => collect(), 'availablePowers' => []]);
        }
        
        // 2. Get available powers (optimized)
        $availablePowers = $spherePowerAttribute->options->pluck('admin_name')->map('floatval')->toArray();
        
        // 3. Get only the products in "Sphere Power" attribute family (using whereHas instead of filtering the collection)
        $products = $this->productRepository->scopeQuery(function($query) {
            return $query->whereHas('attribute_family', function($query) {
                $query->where('name', 'Sphere Power');
            });
        })->with(['product_flats', 'images'])->get();
        
        // 4. Batch process existing power copies instead of checking one by one
        $spherePowerAttributeId = $spherePowerAttribute->id;
        
        // 4.1 Get all power option IDs in a map for easy lookup
        $powerOptionMap = [];
        foreach ($availablePowers as $power) {
            if ((float)$power == 0.0) {
                $optionId = DB::table('attribute_options')
                    ->where('attribute_id', $spherePowerAttributeId)
                    ->where('admin_name', 'Normal')
                    ->value('id');
            } else {
                $possibleNames = [
                    (string)(float)$power,
                    number_format((float)$power, 1),
                    number_format((float)$power, 2)
                ];
                
                foreach ($possibleNames as $name) {
                    $optionId = DB::table('attribute_options')
                        ->where('attribute_id', $spherePowerAttributeId)
                        ->where('admin_name', $name)
                        ->value('id');
                    
                    if ($optionId) break;
                }
            }
            
            if ($optionId) {
                $powerOptionMap[$power] = $optionId;
            }
        }
        
        // 4.2 Get all existing product power combinations at once
        $existingPowerProducts = DB::table('product_attribute_values')
            ->where('attribute_id', $spherePowerAttributeId)
            ->whereIn('integer_value', array_values($powerOptionMap))
            ->whereIn('product_id', $products->pluck('id'))
            ->select('product_id', 'integer_value')
            ->get();
        
        // 4.3 Create a lookup map of existing product-power combinations
        $existingCombinations = [];
        foreach ($existingPowerProducts as $item) {
            $existingCombinations[$item->product_id][] = $item->integer_value;
        }
        
        // 5. Determine copyable powers for each product using our lookup maps
        $productCount = 0;
        foreach ($products as $index => $product) {
            $copyablePowers = [];
            
            foreach ($powerOptionMap as $power => $optionId) {
                // Skip if this product already has this power
                if (isset($existingCombinations[$product->id]) && in_array($optionId, $existingCombinations[$product->id])) {
                    continue;
                }
                
                // Check if any other product has this power (meaning a copy already exists)
                $existingCopy = false;
                foreach ($existingCombinations as $pid => $options) {
                    if ($pid != $product->id && in_array($optionId, $options)) {
                        $existingCopy = true;
                        break;
                    }
                }
                
                if (!$existingCopy) {
                    $copyablePowers[] = $power;
                }
            }
            
            $product->setAttribute('copyablePowers', $copyablePowers);
            
            // Remove products without copyable powers
            if (empty($copyablePowers)) {
                $products->forget($index);
            } else {
                $productCount++;
            }
        }
        
        // 6. Handle search filtering
        if ($search = request()->input('search')) {
            $products = $products->filter(function ($product) use ($search) {
                $flat = $product->product_flats->first();
                return $flat && stripos($flat->name, $search) !== false;
            });
        }
        
        // 7. Sort products by name
        $products = $products->sortBy(function ($product) {
            $flat = $product->product_flats->first();
            return $flat ? $flat->name : '';
        });
        
        return view('admin::catalog.products.copy-powers', compact('products', 'availablePowers'));
    }
    
    public function copyWithPowers(Request $request)
    {
        DB::beginTransaction();
        try {
            Event::dispatch('catalog.product.create.before');
    
            // Retrieve base product ID.
            $id = $request->input('product_id');
    
            $baseProduct = $this->productRepository->find($id);
            if (!$baseProduct) {
                Log::error('Base product not found', ['product_id' => $id]);
                throw new \Exception('Base product not found.');
            }
    
            // Get selected power values.
            $selectedPowers = $request->input('powers');

            if (!is_array($selectedPowers) || empty($selectedPowers)) {
                Log::error('No power values provided', ['selectedPowers' => $selectedPowers]);
                throw new \Exception('No power values provided.');
            }
    
            // Get base product names without power values.
            $baseNameEn = '';
            $baseNameAr = '';
            $baseFlats = $baseProduct->product_flats->toArray();
            foreach ($baseFlats as $baseFlat) {
                if ($baseFlat['locale'] === 'en') {
                    $baseNameEn = preg_replace('/ -?\d+\.\d+$/', '', $baseFlat['name']);
                    $baseNameEn = preg_replace('/ -?\d+$/', '', $baseNameEn);
                    $baseNameEn = trim($baseNameEn);
                }
                if ($baseFlat['locale'] === 'ar') {
                    $baseNameAr = preg_replace('/ -?\d+\.\d+$/', '', $baseFlat['name']);
                    $baseNameAr = preg_replace('/ -?\d+$/', '', $baseNameAr);
                    $baseNameAr = trim($baseNameAr);
                }
            }
            if (empty($baseNameEn)) {
                $baseNameEn = preg_replace('/ -?\d+\.\d+$/', '', $baseProduct->name);
                $baseNameEn = preg_replace('/ -?\d+$/', '', $baseNameEn);
                $baseNameEn = trim($baseNameEn);
            }
            if (empty($baseNameAr)) {
                $baseNameAr = $baseNameEn;
            }
    
            // Get base product descriptions/meta texts.
            $baseShortDescEn = $baseProduct->getAttributeValue('short_description', 'en') ?? '';
            $baseDescEn = $baseProduct->getAttributeValue('description', 'en') ?? '';
            $baseMetaTitleEn = $baseProduct->getAttributeValue('meta_title', 'en') ?? '';
            $baseMetaKeywordsEn = $baseProduct->getAttributeValue('meta_keywords', 'en') ?? '';
            $baseMetaDescEn = $baseProduct->getAttributeValue('meta_description', 'en') ?? '';
    
            $baseShortDescAr = $baseProduct->getAttributeValue('short_description', 'ar') ?? '';
            $baseDescAr = $baseProduct->getAttributeValue('description', 'ar') ?? '';
            $baseMetaTitleAr = $baseProduct->getAttributeValue('meta_title', 'ar') ?? '';
            $baseMetaKeywordsAr = $baseProduct->getAttributeValue('meta_keywords', 'ar') ?? '';
            $baseMetaDescAr = $baseProduct->getAttributeValue('meta_description', 'ar') ?? '';
    
            // Clean descriptions/meta texts.
            $baseShortDescEn = preg_replace('/ -?\d+\.\d+/', '', $baseShortDescEn);
            $baseDescEn = preg_replace('/ -?\d+\.\d+/', '', $baseDescEn);
            $baseMetaTitleEn = preg_replace('/ -?\d+\.\d+/', '', $baseMetaTitleEn);
            $baseMetaKeywordsEn = preg_replace('/ -?\d+\.\d+/', '', $baseMetaKeywordsEn);
            $baseMetaDescEn = preg_replace('/ -?\d+\.\d+/', '', $baseMetaDescEn);
    
            $baseShortDescAr = preg_replace('/ -?\d+\.\d+/', '', $baseShortDescAr);
            $baseDescAr = preg_replace('/ -?\d+\.\d+/', '', $baseDescAr);
            $baseMetaTitleAr = preg_replace('/ -?\d+\.\d+/', '', $baseMetaTitleAr);
            $baseMetaKeywordsAr = preg_replace('/ -?\d+\.\d+/', '', $baseMetaKeywordsAr);
            $baseMetaDescAr = preg_replace('/ -?\d+\.\d+/', '', $baseMetaDescAr);
    
            // Fallback: if short description or description is empty, use base name.
            if (empty(trim($baseShortDescEn))) {
                $baseShortDescEn = $baseNameEn;
            }
            if (empty(trim($baseDescEn))) {
                $baseDescEn = $baseNameEn;
            }
            if (empty(trim($baseShortDescAr))) {
                $baseShortDescAr = $baseNameAr;
            }
            if (empty(trim($baseDescAr))) {
                $baseDescAr = $baseNameAr;
            }
    
            // Fallback for meta fields: if empty, use base name.
            if (empty(trim($baseMetaTitleEn))) {
                $baseMetaTitleEn = $baseNameEn;
            }
            if (empty(trim($baseMetaKeywordsEn))) {
                $baseMetaKeywordsEn = $baseNameEn;
            }
            if (empty(trim($baseMetaDescEn))) {
                $baseMetaDescEn = $baseNameEn;
            }
            if (empty(trim($baseMetaTitleAr))) {
                $baseMetaTitleAr = $baseNameAr;
            }
            if (empty(trim($baseMetaKeywordsAr))) {
                $baseMetaKeywordsAr = $baseNameAr;
            }
            if (empty(trim($baseMetaDescAr))) {
                $baseMetaDescAr = $baseNameAr;
            }
    
            $createdProducts = [];
            foreach ($selectedPowers as $power) {
    
                // Check if a copy with this power exists.
                $exists = $this->findExistingPowerCopy($baseProduct->id, $power);
                if ($exists) {
                    continue;
                }
    
                // Create a duplicate.
                $newProduct = $this->productRepository->copy($id);
    
                // Update SKU.
                if ($power < 0) {
                    $skuSuffix = 'N' . str_replace('.', '', abs($power));
                } else {
                    $skuSuffix = str_replace('.', '', $power);
                }
                $newProduct->sku = $baseProduct->sku . '-' . $skuSuffix;
                $newProduct->save();
    
                // Format the power value.
                $formattedPower = number_format((float)$power, 2);
    
                // New names.
                $newNameEn = $baseNameEn . " " . $formattedPower;
                $newNameAr = $baseNameAr . " " . $formattedPower;
    
                // URL keys.
                $newUrlKeyEn = Str::slug($newNameEn);
                $newUrlKeyAr = Str::slug($newNameAr);
    
                // Updated descriptions.
                $newShortDescEn = $this->replaceContentWithPower($baseShortDescEn, $formattedPower);
                $newDescEn = $this->replaceContentWithPower($baseDescEn, $formattedPower);
                $newMetaTitleEn = $this->replaceContentWithPower($baseMetaTitleEn, $formattedPower);
                $newMetaKeywordsEn = $this->replaceContentWithPower($baseMetaKeywordsEn, $formattedPower);
                $newMetaDescEn = $this->replaceContentWithPower($baseMetaDescEn, $formattedPower);
    
                $newShortDescAr = $this->replaceContentWithPower($baseShortDescAr, $formattedPower);
                $newDescAr = $this->replaceContentWithPower($baseDescAr, $formattedPower);
                $newMetaTitleAr = $this->replaceContentWithPower($baseMetaTitleAr, $formattedPower);
                $newMetaKeywordsAr = $this->replaceContentWithPower($baseMetaKeywordsAr, $formattedPower);
                $newMetaDescAr = $this->replaceContentWithPower($baseMetaDescAr, $formattedPower);
    
                // Use new product's ID as product number.
                $productNumber = (string)$newProduct->id;
    
                // Update product_flat table for both locales.
                $enUpdated = DB::table('product_flat')
                    ->where('product_id', $newProduct->id)
                    ->where('locale', 'en')
                    ->update([
                        'name' => $newNameEn,
                        'product_number' => $productNumber,
                        'url_key' => $newUrlKeyEn,
                        'short_description' => $newShortDescEn,
                        'description' => $newDescEn,
                        'meta_title' => $newMetaTitleEn,
                        'meta_keywords' => $newMetaKeywordsEn,
                        'meta_description' => $newMetaDescEn
                    ]);
                $arUpdated = DB::table('product_flat')
                    ->where('product_id', $newProduct->id)
                    ->where('locale', 'ar')
                    ->update([
                        'name' => $newNameAr,
                        'product_number' => $productNumber,
                        'url_key' => $newUrlKeyAr,
                        'short_description' => $newShortDescAr,
                        'description' => $newDescAr,
                        'meta_title' => $newMetaTitleAr,
                        'meta_keywords' => $newMetaKeywordsAr,
                        'meta_description' => $newMetaDescAr
                    ]);
    
                // Update sphere_power attribute in EAV.
                // Since sphere_power is a select attribute, we need to look up the option id.
                // Update sphere_power attribute in EAV.
                $spherePowerAttributeId = DB::table('attributes')
                ->where('code', 'sphere_power')
                ->value('id');
                if ($spherePowerAttributeId) {
                // Lookup the option id from lensattribute_options table.
                // Force using the correct table and format (we use $formattedPower which is like "-2.00")
                $optionId = DB::table('attribute_options')
                    ->where('attribute_id', $spherePowerAttributeId)
                    ->where('admin_name', $formattedPower)
                    ->value('id');
                    if ($optionId) {
                        // Force using integer_value since your query shows sphere_power is stored there.
                        $valueColumn = 'integer_value';
                        $existingAttr = DB::table('product_attribute_values')
                            ->where('product_id', $newProduct->id)
                            ->where('attribute_id', $spherePowerAttributeId)
                            ->first();
                        if ($existingAttr) {
                            DB::table('product_attribute_values')
                                ->where('id', $existingAttr->id)
                                ->update([
                                    $valueColumn => $optionId
                                ]);
                        } else {
                            DB::table('product_attribute_values')->insert([
                                'product_id' => $newProduct->id,
                                'attribute_id' => $spherePowerAttributeId,
                                $valueColumn => $optionId,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                        } else {
                            Log::warning("No option found for sphere_power value: {$formattedPower}");
                        }
                    } else {
                Log::warning('sphere_power attribute not found for assignment');
                }
                
                // Update EAV attributes so that flat table refresh uses these values.
                // (We update everything except product_number.)
                $this->updateProductAttribute($newProduct->id, 'product_number', (string)$newProduct->id);
                $this->updateProductAttribute($newProduct->id, 'name', $newNameEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'name', $newNameAr, 'ar');
                $this->updateProductAttribute($newProduct->id, 'url_key', $newUrlKeyEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'url_key', $newUrlKeyAr, 'ar');
                $this->updateProductAttribute($newProduct->id, 'short_description', $newShortDescEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'short_description', $newShortDescAr, 'ar');
                $this->updateProductAttribute($newProduct->id, 'description', $newDescEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'description', $newDescAr, 'ar');
                $this->updateProductAttribute($newProduct->id, 'meta_title', $newMetaTitleEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'meta_title', $newMetaTitleAr, 'ar');
                $this->updateProductAttribute($newProduct->id, 'meta_keywords', $newMetaKeywordsEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'meta_keywords', $newMetaKeywordsAr, 'ar');
                $this->updateProductAttribute($newProduct->id, 'meta_description', $newMetaDescEn, 'en');
                $this->updateProductAttribute($newProduct->id, 'meta_description', $newMetaDescAr, 'ar');
    
                // Create URL rewrites.
                $this->createUrlRewrite($newProduct->id, 'product', $newUrlKeyEn, $newNameEn, 'en');
                $this->createUrlRewrite($newProduct->id, 'product', $newUrlKeyAr, $newNameAr, 'ar');
    
                // Force a flat table refresh.
                if (method_exists($newProduct->getTypeInstance(), 'updateProductFlat')) {
                    $newProduct->getTypeInstance()->updateProductFlat($newProduct);
                }
                // Final update of product_number in flat table.
                DB::table('product_flat')
                    ->where('product_id', $newProduct->id)
                    ->update(['product_number' => $productNumber]);
    
                // Dispatch events and clear cache.
                Event::dispatch('catalog.product.update.after', $newProduct);
                Event::dispatch('catalog.product.create.after', $newProduct);
                Cache::forget('product_' . $newProduct->id);
    
                $createdProducts[] = $newProduct->id;
            }
            
            DB::commit();
            session()->flash('success', trans('admin::app.catalog.products.copy-success', ['count' => count($createdProducts)]));
            return redirect()->route('admin.catalog.products.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in copyWithPowers', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', trans('admin::app.catalog.products.copy-error', ['message' => $e->getMessage()]));
            return redirect()->route('admin.catalog.products.index');
        }
    }
   
    /**
     * Create a URL rewrite record.
     */
    private function createUrlRewrite($productId, $entityType, $requestPath, $targetPath, $locale)
    {
        try {
            $existing = DB::table('url_rewrites')
                ->where('request_path', $requestPath)
                ->where('locale', $locale)
                ->first();
            if ($existing) {
                $requestPath = $requestPath . '-' . $productId;
            }
            DB::table('url_rewrites')->insert([
                'entity_type' => $entityType,
                'request_path' => $requestPath,
                'target_path' => $targetPath,
                'redirect_type' => 301,
                'locale' => $locale,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating URL rewrite', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'request_path' => $requestPath
            ]);
        }
    }

    /**
     * Update a product attribute.
     */
    private function updateProductAttribute($productId, $attributeCode, $value, $locale = null)
    {
        try {
            $attribute = DB::table('attributes')
                ->where('code', $attributeCode)
                ->first();
                
            if (!$attribute) {
                Log::warning("Attribute not found: {$attributeCode}");
                return;
            }
            
            $attributeId = $attribute->id;
            $attributeType = $attribute->type;
            
            // Determine value column based on attribute type
            $valueColumn = 'text_value';
            
            // Special handling for sphere_power attribute (select type)
            if ($attributeCode == 'sphere_power') {
                $valueColumn = 'integer_value';
                
                // If value is not numeric, look up the option ID
                if (!is_numeric($value)) {
                    $optionId = DB::table('attribute_options')
                        ->where('attribute_id', $attributeId)
                        ->where(function($query) use ($value) {
                            $query->where('admin_name', $value)
                                ->orWhere('admin_name', 'like', $value);
                        })
                        ->value('id');
                        
                    if ($optionId) {
                        $value = $optionId;
                    } else {
                        return;
                    }
                }
            } elseif ($attributeType == 'integer') {
                $valueColumn = 'integer_value';
            } elseif ($attributeType == 'decimal' || $attributeType == 'price') {
                $valueColumn = 'float_value';
            } elseif ($attributeType == 'boolean') {
                $valueColumn = 'boolean_value';
            } elseif ($attributeType == 'datetime') {
                $valueColumn = 'datetime_value';
            } elseif ($attributeType == 'date') {
                $valueColumn = 'date_value';
            }
            
            $result = DB::table('product_attribute_values')
                ->updateOrInsert(
                    [
                        'product_id' => $productId,
                        'attribute_id' => $attributeId,
                        'locale' => $locale,
                        'channel' => null
                    ],
                    [
                        $valueColumn => $value,
                    ]
                );
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Error updating attribute {$attributeCode}", [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            return false;
        }
    }

    /**
     * Find existing product with this power value.
     */
    private function findExistingPowerCopy($baseProductId, $power)
    {
        // Get the sphere_power attribute ID
        $attributeId = DB::table('attributes')
            ->where('code', 'sphere_power')
            ->value('id');
        if (!$attributeId) {
            return false;
        }
        
        // Get the option ID for this power value
        $optionId = null;
        if ((float)$power == 0.0) {
            // Special case: if power is zero, use "Normal"
            $optionId = DB::table('attribute_options')
                ->where('attribute_id', $attributeId)
                ->where('admin_name', 'Normal')
                ->value('id');
        } else {
            // Try different formats
            $possibleNames = [
                (string)(float)$power,
                number_format((float)$power, 1),
                number_format((float)$power, 2)
            ];
            
            foreach ($possibleNames as $name) {
                $optionId = DB::table('attribute_options')
                    ->where('attribute_id', $attributeId)
                    ->where('admin_name', $name)
                    ->value('id');
                
                if ($optionId) break;
            }
        }
        
        if (!$optionId) {
            return false;
        }
        
        // Look for any product that has this power value and is not the base product
        $existingProduct = DB::table('product_attribute_values')
            ->where('attribute_id', $attributeId)
            ->where('integer_value', $optionId)
            ->where('product_id', '!=', $baseProductId)
            ->first();
        
        if ($existingProduct) {
            return true;
        }
        
        return false;
    }

    /**
     * Replace or append power values in content.
     */
    private function replaceContentWithPower($content, $formattedPower)
    {
        if (empty($content)) {
            return $content;
        }
        $hasExistingPower = preg_match('/ -?\d+\.\d+$/', $content) || preg_match('/ -?\d+$/', $content);
        $hasExistingPowerInHtml = preg_match('/<p>(.*?) -?\d+\.\d+<\/p>/', $content) || preg_match('/<p>(.*?) -?\d+<\/p>/', $content);
        if ($hasExistingPower) {
            $content = preg_replace('/ -?\d+\.\d+$/', " {$formattedPower}", $content);
            $content = preg_replace('/ -?\d+$/', " {$formattedPower}", $content);
        } else if ($hasExistingPowerInHtml) {
            $content = preg_replace('/<p>(.*?) -?\d+\.\d+<\/p>/', "<p>$1 {$formattedPower}</p>", $content);
            $content = preg_replace('/<p>(.*?) -?\d+<\/p>/', "<p>$1 {$formattedPower}</p>", $content);
        } else {
            if (preg_match('/<\/[a-zA-Z][^>]*>$/', $content)) {
                $content = preg_replace('/(<\/[a-zA-Z][^>]*>)$/', " {$formattedPower}$1", $content);
            } else {
                $content .= " {$formattedPower}";
            }
        }
        return $content;
    }

    /**
     * Uploads downloadable sample file.
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadSample(int $id)
    {
        return response()->json(
            $this->productDownloadableSampleRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Event::dispatch('catalog.product.delete.before', $id);

            $this->productRepository->delete($id);

            Event::dispatch('catalog.product.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.delete-failed'),
        ], 500);
    }

    /**
     * Mass delete the products.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $productIds = $massDestroyRequest->input('indices');

        try {
            foreach ($productIds as $productId) {
                $product = $this->productRepository->find($productId);

                if (isset($product)) {
                    Event::dispatch('catalog.product.delete.before', $productId);

                    $this->productRepository->delete($productId);

                    Event::dispatch('catalog.product.delete.after', $productId);
                }
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mass update the products.
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $productIds = $massUpdateRequest->input('indices');

        foreach ($productIds as $productId) {
            Event::dispatch('catalog.product.update.before', $productId);

            $product = $this->productRepository->update([
                'status'  => $massUpdateRequest->input('value'),
            ], $productId, ['status']);

            Event::dispatch('catalog.product.update.after', $product);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success'),
        ], 200);
    }

    /**
     * To be manually invoked when data is seeded into products.
     *
     * @return \Illuminate\Http\Response
     */
    public function sync()
    {
        Event::dispatch('products.datagrid.sync', true);

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Result of search product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $searchEngine = 'database';

        if (
            core()->getConfigData('catalog.products.search.engine') == 'elastic'
            && core()->getConfigData('catalog.products.search.admin_mode') == 'elastic'
        ) {
            $searchEngine = 'elastic';

            $indexNames = core()->getAllChannels()->map(function ($channel) {
                return 'products_'.$channel->code.'_'.app()->getLocale().'_index';
            })->toArray();
        }

        $channelId = $this->customerRepository->find(request('customer_id'))->channel_id ?? null;

        $params = [
            'index'      => $indexNames ?? null,
            'name'       => request('query'),
            'sort'       => 'created_at',
            'order'      => 'desc',
            'channel_id' => $channelId,
        ];

        if (request()->has('type')) {
            $params['type'] = request('type');
        }

        $products = $this->productRepository
            ->setSearchEngine($searchEngine)
            ->getAll($params);

        return ProductResource::collection($products);
    }

    /**
     * Download image or file.
     *
     * @param  int  $productId
     * @param  int  $attributeId
     * @return \Illuminate\Http\Response
     */
    public function download($productId, $attributeId)
    {
        $productAttribute = $this->productAttributeValueRepository->findOneWhere([
            'product_id'   => $productId,
            'attribute_id' => $attributeId,
        ]);

        return Storage::download($productAttribute['text_value']);
    }
}
