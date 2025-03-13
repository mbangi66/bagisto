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
        // Retrieve the sphere_power attribute from the attribute repository.
        $spherePowerAttribute = $this->attributeRepository->findOneByField('code', 'sphere_power');
    
        if ($spherePowerAttribute && $spherePowerAttribute->options->isNotEmpty()) {
            // Assume each option's admin_name holds the power value.
            $availablePowers = $spherePowerAttribute->options->pluck('admin_name')->toArray();
            // Convert values to float if needed.
            $availablePowers = array_map('floatval', $availablePowers);
        } else {
            $availablePowers = [];
        }
    
        // Retrieve all products (this returns a Collection).
        $allProducts = $this->productRepository->all()->load('product_flats');
    
        // Filter the collection. Add your filtering logic here.
        $products = $allProducts->filter(function ($product) use ($availablePowers) {
            // For example, you could check if the product's attribute family is "Sphere Power"
            // and add further checks if needed.
            if (! isset($product->attribute_family) || $product->attribute_family->name !== 'Sphere Power') {
                return false;
            }
    
            // You can add more logic here...
            return true;
        });

        $products = $products->sortBy(function ($product) {
            $flat = $product->product_flats->first();
            return $flat ? $flat->name : '';
        });

        return view('admin::catalog.products.copy-powers', compact('products', 'availablePowers'));
    }
    
    public function copyWithPowers(Request $request, int $id)
    {
        try {
            Event::dispatch('catalog.product.create.before');

            // Retrieve the base product using the provided ID.
            $baseProduct = $this->productRepository->find($id);
            if (!$baseProduct) {
                throw new \Exception('Base product not found.');
            }

            // Get the selected power values from the request (e.g., [6.00, 5.5, 5.0, ...])
            $selectedPowers = $request->input('powers');
            if (!is_array($selectedPowers) || empty($selectedPowers)) {
                throw new \Exception('No power values provided.');
            }

            // Array to keep track of new products created.
            $createdProducts = [];

            foreach ($selectedPowers as $power) {
                // Safety check: determine if a product copy with this power already exists.
                // (Implement findExistingPowerCopy in your repository if needed.)
                $exists = $this->productRepository->findExistingPowerCopy($baseProduct->id, $power);
                if ($exists) {
                    continue; // Skip this power value if a copy exists.
                }

                // Create a new product copy from the base product.
                $newProduct = $this->productRepository->copy($id);

                // Retrieve the base English translation.
                $baseEn = $baseProduct->translate('en');

                // Update English fields by using the base product’s text and appending the power.
                $newEn = $newProduct->translateOrNew('en');
                $newEn->name = $baseEn->name . " " . $power;
                $newEn->short_description = $baseEn->short_description . " " . $power;
                $newEn->description = $baseEn->description . " " . $power;
                $newEn->meta_title = $baseEn->meta_title . " " . $power;
                $newEn->meta_keywords = $baseEn->meta_keywords . " " . $power;
                $newEn->meta_description = $baseEn->meta_description . " " . $power;

                // For Arabic, check if the base product has Arabic translations.
                if ($baseProduct->hasTranslation('ar')) {
                    $baseAr = $baseProduct->translate('ar');
                    $newAr = $newProduct->translateOrNew('ar');
                    $newAr->name = $baseAr->name . " " . $power;
                    $newAr->short_description = $baseAr->short_description . " " . $power;
                    $newAr->description = $baseAr->description . " " . $power;
                    $newAr->meta_title = $baseAr->meta_title . " " . $power;
                    $newAr->meta_keywords = $baseAr->meta_keywords . " " . $power;
                    $newAr->meta_description = $baseAr->meta_description . " " . $power;
                } else {
                    // If no Arabic translation exists for the base product, copy English text.
                    $newProduct->translateOrNew('ar')->name = $newEn->name;
                    $newProduct->translateOrNew('ar')->short_description = $newEn->short_description;
                    $newProduct->translateOrNew('ar')->description = $newEn->description;
                    $newProduct->translateOrNew('ar')->meta_title = $newEn->meta_title;
                    $newProduct->translateOrNew('ar')->meta_keywords = $newEn->meta_keywords;
                    $newProduct->translateOrNew('ar')->meta_description = $newEn->meta_description;
                }

                // Update SKU based on the base product’s SKU and the current power value.
                // Remove any decimals from the power value before appending.
                $newProduct->sku = $baseProduct->sku . '-' . str_replace('.', '', $power);

                // Update the URL key using a helper (like str_slug) on the new English name.
                $newProduct->url_key = str_slug($newEn->name);

                // Update the sphere power attribute. (Adjust this to match how your model handles custom attributes.)
                if (method_exists($newProduct, 'updateAttribute')) {
                    $newProduct->updateAttribute('sphere_power', $power);
                } else {
                    $newProduct->sphere_power = $power;
                }

                // Save the new product.
                $this->productRepository->save($newProduct);
                Event::dispatch('catalog.product.create.after', $newProduct);

                $createdProducts[] = $newProduct;
            }

            if (count($createdProducts) > 0) {
                session()->flash('success', trans('admin::app.catalog.products.product-copied'));
            } else {
                session()->flash('info', 'No new power copies were created; they may already exist.');
            }

            return redirect()->route('admin.catalog.products.index');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return redirect()->route('admin.catalog.products.index');
        }
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
