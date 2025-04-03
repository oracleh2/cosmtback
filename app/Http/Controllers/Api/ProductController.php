<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CosmeticService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    protected $cosmeticService;

    /**
     * Create a new controller instance.
     */
    public function __construct(CosmeticService $cosmeticService)
    {
        $this->cosmeticService = $cosmeticService;
    }

    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Применяем фильтры
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('brand', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('skin_type')) {
            $query->where('skin_type_target', $request->skin_type);
        }

        // Получаем результаты с пагинацией
        $products = $query->paginate(10);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage()
            ]
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:5120',
            'category' => 'nullable|string|max:50',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'string',
            'description' => 'nullable|string',
            'skin_type_target' => 'nullable|string|max:50',
            'skin_concerns_target' => 'nullable|array',
            'skin_concerns_target.*' => 'string|max:50',
        ]);

        // Обработка изображения
        $imagePath = null;
        if ($request->hasFile('image')) {
            $directory = 'public/products';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            $image = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs($directory, $filename);

            // Оптимизация изображения
            try {
                $img = Image::make(storage_path('app/' . $imagePath));
                $img->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                Storage::put($imagePath, (string) $img->encode());
            } catch (\Exception $e) {
                \Log::error('Failed to optimize product image: ' . $e->getMessage());
            }
        }

        // Создаем продукт
        $product = Product::create([
            'name' => $request->name,
            'brand' => $request->brand,
            'image_path' => $imagePath,
            'category' => $request->category,
            'ingredients' => $request->ingredients,
            'description' => $request->description,
            'skin_type_target' => $request->skin_type_target,
            'skin_concerns_target' => $request->skin_concerns_target,
        ]);

        return response()->json([
            'message' => 'Product added successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'data' => $product
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'brand' => 'sometimes|nullable|string|max:100',
            'image' => 'sometimes|nullable|image|max:5120',
            'category' => 'sometimes|nullable|string|max:50',
            'ingredients' => 'sometimes|nullable|array',
            'ingredients.*' => 'string',
            'description' => 'sometimes|nullable|string',
            'skin_type_target' => 'sometimes|nullable|string|max:50',
            'skin_concerns_target' => 'sometimes|nullable|array',
            'skin_concerns_target.*' => 'string|max:50',
        ]);

        $product = Product::findOrFail($id);

        // Обработка изображения
        if ($request->hasFile('image')) {
            // Удаляем старое изображение, если оно есть
            if ($product->image_path) {
                Storage::delete($product->image_path);
            }

            $directory = 'public/products';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            $image = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs($directory, $filename);

            // Оптимизация изображения
            try {
                $img = Image::make(storage_path('app/' . $imagePath));
                $img->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                Storage::put($imagePath, (string) $img->encode());
            } catch (\Exception $e) {
                \Log::error('Failed to optimize product image: ' . $e->getMessage());
            }

            $product->image_path = $imagePath;
        }

        // Обновляем остальные поля
        if ($request->has('name')) {
            $product->name = $request->name;
        }

        if ($request->has('brand')) {
            $product->brand = $request->brand;
        }

        if ($request->has('category')) {
            $product->category = $request->category;
        }

        if ($request->has('ingredients')) {
            $product->ingredients = $request->ingredients;
        }

        if ($request->has('description')) {
            $product->description = $request->description;
        }

        if ($request->has('skin_type_target')) {
            $product->skin_type_target = $request->skin_type_target;
        }

        if ($request->has('skin_concerns_target')) {
            $product->skin_concerns_target = $request->skin_concerns_target;
        }

        $product->save();

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Удаляем изображение, если оно есть
        if ($product->image_path) {
            Storage::delete($product->image_path);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Analyze ingredients from a product image.
     */
    public function analyzeIngredients(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // max 10MB
            'ingredients' => 'nullable|string',
        ]);

        $ingredients = null;

        // Если предоставлены текстовые ингредиенты
        if ($request->has('ingredients')) {
            $ingredients = $request->ingredients;
        }
        // Если предоставлено изображение
        else if ($request->hasFile('image')) {
            // Здесь можно добавить интеграцию с OCR-сервисом для распознавания текста
            // Например, Google Cloud Vision API, Amazon Textract и т.д.

            // Пока что просто возвращаем заготовленный ответ
            $ingredients = "Вода, Глицерин, Гиалуроновая кислота, Масло ши, Парафин";
        }

        // Анализируем ингредиенты
        $analysis = $this->cosmeticService->analyzeIngredients($ingredients ?? '');

        // Форматируем ответ
        $formattedAnalysis = [
            'ingredients' => $analysis['all_ingredients'] ?? [],
            'concerns' => [],
            'benefits' => [],
            'safety_score' => 85.0,
            'suitable_for_skin_types' => ["Сухая", "Нормальная"]
        ];

        // Добавляем проблемные ингредиенты
        if (isset($analysis['problematic_ingredients']) && is_array($analysis['problematic_ingredients'])) {
            foreach ($analysis['problematic_ingredients'] as $problematic) {
                $formattedAnalysis['concerns'][] = [
                    'name' => $problematic['name'],
                    'description' => $problematic['concern']
                ];
            }
        }

        // Добавляем полезные свойства
        if (isset($analysis['beneficial_ingredients']) && is_array($analysis['beneficial_ingredients'])) {
            $benefitGroups = [];

            foreach ($analysis['beneficial_ingredients'] as $beneficial) {
                $concern = $beneficial['for_concern'] ?? 'general';
                $benefit = $beneficial['benefit'] ?? '';
                $name = $beneficial['name'] ?? '';

                if (!isset($benefitGroups[$concern])) {
                    $benefitGroups[$concern] = [];
                }

                $benefitGroups[$concern][] = $name;
            }

            foreach ($benefitGroups as $concern => $ingredients) {
                $formattedAnalysis['benefits'][] = ucfirst($concern) . " (" . implode(", ", $ingredients) . ")";
            }
        }

        return response()->json([
            'data' => $formattedAnalysis
        ]);
    }
}
