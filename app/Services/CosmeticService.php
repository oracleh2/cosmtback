<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CosmeticService
{
    // Проблемные ингредиенты с описаниями
    private $problematicIngredients = [
        'alcohol denat' => 'Может сушить кожу и нарушать барьерную функцию',
        'isopropyl alcohol' => 'Может быть слишком сушащим для кожи',
        'sodium lauryl sulfate' => 'Может вызывать раздражение и сухость',
        'sodium laureth sulfate' => 'Может вызывать раздражение у чувствительной кожи',
        'fragrance' => 'Частая причина аллергических реакций',
        'parfum' => 'Может вызывать раздражение и аллергию',
        'parabens' => 'Могут вызывать раздражение у некоторых людей',
        'formaldehyde' => 'Потенциально опасный консервант',
        'phthalates' => 'Могут нарушать гормональный баланс',
        'mineral oil' => 'Может закупоривать поры',
        'petroleum' => 'Может закупоривать поры',
        'парафин' => 'Может закупоривать поры и вызывать акне у предрасположенных людей',
    ];

    // Полезные ингредиенты для разных проблем с кожей
    private $beneficialIngredients = [
        'acne' => [
            'salicylic acid' => 'Отшелушивает и очищает поры',
            'benzoyl peroxide' => 'Уничтожает бактерии, вызывающие акне',
            'niacinamide' => 'Уменьшает воспаление и покраснение',
            'tea tree oil' => 'Натуральный антибактериальный ингредиент',
            'zinc' => 'Уменьшает выработку кожного сала',
            'retinol' => 'Ускоряет обновление клеток'
        ],
        'dryness' => [
            'hyaluronic acid' => 'Увлажняет и удерживает влагу',
            'glycerin' => 'Притягивает влагу к коже',
            'ceramides' => 'Укрепляет барьерную функцию кожи',
            'squalane' => 'Увлажняет и смягчает кожу',
            'shea butter' => 'Богатый питательный ингредиент',
            'jojoba oil' => 'Балансирует выработку кожного сала',
            'гиалуроновая кислота' => 'Увлажняет и удерживает влагу',
            'глицерин' => 'Притягивает влагу к коже',
            'масло ши' => 'Богатый питательный ингредиент'
        ],
        'aging' => [
            'retinol' => 'Стимулирует выработку коллагена',
            'vitamin c' => 'Антиоксидант, осветляет и выравнивает тон',
            'peptides' => 'Стимулируют обновление клеток',
            'coenzyme q10' => 'Защищает от свободных радикалов',
            'alpha hydroxy acids' => 'Отшелушивает и обновляет кожу',
            'niacinamide' => 'Улучшает текстуру и тон кожи'
        ],
        'hyperpigmentation' => [
            'vitamin c' => 'Осветляет и выравнивает тон кожи',
            'niacinamide' => 'Уменьшает пигментацию',
            'alpha arbutin' => 'Уменьшает меланин в коже',
            'kojic acid' => 'Осветляет пигментные пятна',
            'licorice extract' => 'Натуральный осветляющий ингредиент',
            'azelaic acid' => 'Уменьшает пигментацию и покраснения'
        ],
        'sensitivity' => [
            'aloe vera' => 'Успокаивает и снимает раздражение',
            'chamomile' => 'Снимает воспаление и успокаивает',
            'centella asiatica' => 'Заживляет и успокаивает',
            'oat extract' => 'Снимает раздражение',
            'allantoin' => 'Успокаивает и смягчает',
            'bisabolol' => 'Уменьшает покраснение и воспаление'
        ]
    ];

    /**
     * Analyze cosmetic ingredients.
     *
     * @param string $ingredientsText
     * @return array
     */
    public function analyzeIngredients($ingredientsText)
    {
        try {
            // Разбираем ингредиенты в массив
            $parsedIngredients = $this->parseIngredients($ingredientsText);

            // В реальном приложении вы бы:
            // 1. Отправили ингредиенты в API для анализа
            // 2. Обработали ответ
            // 3. Создали структурированный анализ

            // Пока что создаем локальный анализ
            $analysis = $this->localIngredientsAnalysis($parsedIngredients);

            return $analysis;
        } catch (\Exception $e) {
            Log::error('Failed to analyze cosmetic ingredients: ' . $e->getMessage());
            return [
                'error' => 'Failed to analyze ingredients',
                'raw_ingredients' => $ingredientsText
            ];
        }
    }

    /**
     * Parse a string of ingredients into an array.
     *
     * @param string $ingredientsText
     * @return array
     */
    private function parseIngredients($ingredientsText)
    {
        // Разделить ингредиенты по общим разделителям
        $ingredients = preg_split('/[,;\/]+/', $ingredientsText);

        // Очистить каждый ингредиент
        $cleanIngredients = [];
        foreach ($ingredients as $ingredient) {
            $ingredient = trim($ingredient);
            if (!empty($ingredient)) {
                $cleanIngredients[] = mb_strtolower($ingredient, 'UTF-8');
            }
        }

        return $cleanIngredients;
    }

    /**
     * Выполняет локальный анализ ингредиентов без внешнего API.
     *
     * @param array $ingredients
     * @return array
     */
    private function localIngredientsAnalysis($ingredients)
    {
        $problematic = [];
        $beneficial = [];
        $unknown = [];

        // Проверяем каждый ингредиент по нашим базам данных
        foreach ($ingredients as $ingredient) {
            $matched = false;

            // Проверка на проблемные ингредиенты
            foreach (array_keys($this->problematicIngredients) as $problematicIngredient) {
                if (mb_stripos($ingredient, $problematicIngredient) !== false) {
                    $problematic[] = [
                        'name' => $ingredient,
                        'concern' => $this->problematicIngredients[$problematicIngredient]
                    ];
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                // Проверка на полезные ингредиенты
                $beneficialFound = false;

                foreach ($this->beneficialIngredients as $concern => $beneficialList) {
                    foreach (array_keys($beneficialList) as $beneficialIngredient) {
                        if (mb_stripos($ingredient, $beneficialIngredient) !== false) {
                            $beneficial[] = [
                                'name' => $ingredient,
                                'benefit' => $beneficialList[$beneficialIngredient],
                                'for_concern' => $concern
                            ];
                            $beneficialFound = true;
                            $matched = true;
                            break;
                        }
                    }

                    if ($beneficialFound) {
                        break;
                    }
                }
            }

            if (!$matched) {
                $unknown[] = $ingredient;
            }
        }

        // Создаем категории ингредиентов в зависимости от функции
        $categories = $this->categorizeBySkinFunction($ingredients);

        // Формируем итоговый анализ
        return [
            'total_ingredients' => count($ingredients),
            'problematic_count' => count($problematic),
            'beneficial_count' => count($beneficial),
            'unknown_count' => count($unknown),
            'problematic_ingredients' => $problematic,
            'beneficial_ingredients' => $beneficial,
            'ingredient_categories' => $categories,
            'all_ingredients' => $ingredients,
        ];
    }

    /**
     * Categorize ingredients by their skincare function.
     *
     * @param array $ingredients
     * @return array
     */
    private function categorizeBySkinFunction($ingredients)
    {
        $categories = [
            'moisturizing' => [],
            'antioxidant' => [],
            'exfoliating' => [],
            'anti-aging' => [],
            'soothing' => [],
            'brightening' => [],
            'preservatives' => [],
            'surfactants' => [],
            'emollients' => [],
            'occlusives' => [],
            'humectants' => [],
        ];

        // Ключевые слова, указывающие на функцию ингредиента
        $categoryKeywords = [
            'moisturizing' => ['glycerin', 'hyaluronic', 'ceramide', 'urea', 'squalane', 'глицерин', 'гиалуроновая'],
            'antioxidant' => ['vitamin c', 'vitamin e', 'tocopherol', 'resveratrol', 'coenzyme q10', 'glutathione', 'niacinamide', 'витамин'],
            'exfoliating' => ['salicylic acid', 'glycolic acid', 'lactic acid', 'mandelic acid', 'aha', 'bha', 'enzyme', 'кислота'],
            'anti-aging' => ['retinol', 'peptide', 'copper peptide', 'collagen', 'bakuchiol', 'ретинол', 'пептид', 'коллаген'],
            'soothing' => ['aloe', 'centella', 'allantoin', 'chamomile', 'bisabolol', 'oat', 'panthenol', 'алоэ', 'пантенол'],
            'brightening' => ['arbutin', 'kojic acid', 'vitamin c', 'tranexamic acid', 'licorice', 'azelaic', 'витамин c'],
            'preservatives' => ['phenoxyethanol', 'parabens', 'benzyl alcohol', 'benzoic acid', 'sorbic acid', 'ethylhexylglycerin', 'парабен'],
            'surfactants' => ['sodium lauryl', 'sodium laureth', 'cocamidopropyl', 'coco-glucoside', 'decyl glucoside', 'лаурил'],
            'emollients' => ['caprylic', 'dimethicone', 'cyclomethicone', 'jojoba', 'shea butter', 'argan oil', 'масло ши', 'масло жожоба'],
            'occlusives' => ['petrolatum', 'beeswax', 'mineral oil', 'lanolin', 'silicone', 'dimethicone', 'парафин', 'воск', 'ланолин'],
            'humectants' => ['glycerin', 'hyaluronic', 'sodium pca', 'sorbitol', 'propylene glycol', 'butylene glycol', 'panthenol', 'глицерин', 'гиалуроновая'],
        ];

        // Категоризация ингредиентов на основе ключевых слов
        foreach ($ingredients as $ingredient) {
            foreach ($categoryKeywords as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_stripos($ingredient, $keyword) !== false) {
                        $categories[$category][] = $ingredient;
                        break;
                    }
                }
            }
        }

        // Удаляем пустые категории
        foreach ($categories as $category => $items) {
            if (empty($items)) {
                unset($categories[$category]);
            } else {
                // Удаляем дубликаты
                $categories[$category] = array_unique($categories[$category]);
            }
        }

        return $categories;
    }

    /**
     * Интеграция с внешним API для анализа ингредиентов.
     * Это заглушка для будущей реализации.
     *
     * @param array $ingredients
     * @return array
     */
    private function analyzeWithExternalAPI($ingredients)
    {
        // Здесь была бы ваша интеграция с внешним API, например:

        /*
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.cosmetic_api.key'),
            ])->post('https://api.example.com/ingredients/analyze', [
                'ingredients' => $ingredients
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('External API analysis failed: ' . $e->getMessage());
            return $this->localIngredientsAnalysis($ingredients);
        }
        */

        // Пока возвращаем локальный анализ
        return $this->localIngredientsAnalysis($ingredients);
    }

    /**
     * Analyze ingredients from OCR text.
     * This is for future integration with OCR services.
     *
     * @param string $imagePath
     * @return array
     */
    public function analyzeIngredientsFromImage($imagePath)
    {
        try {
            // Здесь была бы интеграция с OCR-сервисом, например Google Cloud Vision API:

            /*
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.google_vision.key'),
            ])->attach(
                'image', file_get_contents($imagePath), 'image.jpg'
            )->post('https://vision.googleapis.com/v1/images:annotate', [
                'requests' => [
                    [
                        'image' => [
                            'content' => base64_encode(file_get_contents($imagePath))
                        ],
                        'features' => [
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 1
                            ]
                        ]
                    ]
                ]
            ]);

            $text = $response->json()['responses'][0]['textAnnotations'][0]['description'] ?? '';
            */

            // Временная имитация
            $text = "Вода, Глицерин, Гиалуроновая кислота, Масло ши, Парафин";

            return $this->analyzeIngredients($text);
        } catch (\Exception $e) {
            Log::error('Failed to analyze ingredients from image: ' . $e->getMessage());
            return [
                'error' => 'Failed to analyze ingredients from image',
                'raw_ingredients' => []
            ];
        }
    }
}
