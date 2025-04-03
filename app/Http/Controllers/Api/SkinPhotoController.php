<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkinPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

//use Intervention\Image\Facades\Image;
//use Intervention\Image\Laravel\Facades\Image;

class SkinPhotoController extends Controller
{
    /**
     * Display a listing of the user's photos.
     */
    public function index(Request $request)
    {
        $photos = $request->user()
            ->skinPhotos()
            ->with('skinAnalysis')
            ->orderBy('taken_at', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => $photos->items(),
            'meta' => [
                'total' => $photos->total(),
                'per_page' => $photos->perPage(),
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage()
            ]
        ]);
    }

    /**
     * Store a newly created photo in storage.
     */
    public function store(Request $request)
    {
        logger()->info('Store photo', $request->all());
        $request->validate([
            'image' => 'required|image|max:51200', // max 5MB
            'skin_type' => 'nullable|string|max:50',
            'skin_concerns' => 'nullable|array',
            'skin_concerns.*' => 'string|max:50',
        ]);

        $userId = $request->user()->id;
        info($userId);
        // Создаем директорию если она не существует
        $disk = 'public';
        $directory = 'skin_photos/' . $userId;
        if (!Storage::disk($disk)->exists($directory)) {
            Storage::disk($disk)->makeDirectory($directory);
        }

        // Сохраняем оригинальное изображение
        $image = $request->file('image');
        $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs($directory, $filename, $disk);

        // Создаем миниатюру
        $thumbnailPath = null;
        try {
            $i = Image::read($image);
//            $img = Image::make(storage_path('app/' . $path));
            $i->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $thumbnailFilename = 'thumbnail_' . $filename;
            $thumbnailPath = $directory . '/' . $thumbnailFilename;

//            Storage::disk($disk)->put(
//                storage_path('app/' . $path),
////                (string) $img->encode()
//                $i->encodeByExtension($image->getClientOriginalExtension(), quality: 100)
//            );

            // Сохраняем миниатюру
            Storage::disk($disk)->put(
                $thumbnailPath,
//                (string) $img->encode()
                $i->encodeByExtension($image->getClientOriginalExtension(), quality: 70)
            );
        } catch (\Exception $e) {
            // Если не удалось создать миниатюру, используем оригинальное изображение
            \Log::error('Failed to create thumbnail: ' . $e->getMessage());
        }

        // Создаем метаданные
        $metadata = [
            'skin_type' => $request->skin_type,
            'skin_concerns' => $request->skin_concerns ?? []
        ];

        // Создаем запись в БД
        $photo = SkinPhoto::create([
            'user_id' => $userId,
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'taken_at' => now(),
            'metadata' => $metadata,
        ]);

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'data' => [
                'id' => $photo->id,
                'user_id' => $photo->user_id,
                'image_url' => $photo->image_url,
                'thumbnail_url' => $photo->thumbnail_url,
                'upload_date' => $photo->upload_date,
                'metadata' => $photo->metadata,
            ]
        ], 201);
    }

    /**
     * Display the specified photo.
     */
    public function show(Request $request, $id)
    {
        $photo = $request->user()
            ->skinPhotos()
            ->with('skinAnalysis.recommendation.recommendedProducts')
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $photo->id,
                'user_id' => $photo->user_id,
                'image_url' => $photo->image_url,
                'thumbnail_url' => $photo->thumbnail_url,
                'upload_date' => $photo->upload_date,
                'metadata' => $photo->metadata,
            ]
        ]);
    }

    /**
     * Remove the specified photo from storage.
     */
    public function destroy(Request $request, $id)
    {
        $photo = $request->user()->skinPhotos()->findOrFail($id);

        // Удаляем файлы изображений
        Storage::delete($photo->file_path);
        if ($photo->thumbnail_path) {
            Storage::delete($photo->thumbnail_path);
        }

        // Удаляем запись из БД (каскадное удаление анализов и рекомендаций)
        $photo->delete();

        return response()->json([
            'message' => 'Photo deleted successfully'
        ]);
    }

    /**
     * Get the latest photo for analysis.
     */
    public function latest(Request $request)
    {
        $photo = $request->user()
            ->skinPhotos()
            ->with('skinAnalysis.recommendation.recommendedProducts')
            ->latest('taken_at')
            ->first();

        if (!$photo) {
            return response()->json([
                'message' => 'No photos found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $photo->id,
                'user_id' => $photo->user_id,
                'image_url' => $photo->image_url,
                'thumbnail_url' => $photo->thumbnail_url,
                'upload_date' => $photo->upload_date,
                'metadata' => $photo->metadata,
            ]
        ]);
    }
}
