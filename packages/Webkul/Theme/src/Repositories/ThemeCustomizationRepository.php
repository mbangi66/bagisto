<?php

namespace Webkul\Theme\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Theme\Contracts\ThemeCustomization;

class ThemeCustomizationRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ThemeCustomization::class;
    }

    /**
     * Update the specified theme.
     *
     * @param  array  $data
     * @param  int  $id
     * @return ThemeCustomization
     */
    public function update($data, $id): ThemeCustomization
    {
        $locale = core()->getRequestedLocaleCode();

        if ($data['type'] == 'static_content') {
            $data[$locale]['options']['html'] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data[$locale]['options']['html']);
            $data[$locale]['options']['css'] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data[$locale]['options']['css']);
        }

        if (in_array($data['type'], ['image_carousel', 'services_content'])) {
            unset($data[$locale]['options']);
        }

        $theme = parent::update($data, $id);

        if (in_array($data['type'], ['image_carousel', 'services_content'])) {
            $this->uploadImage(request()->all(), $theme);
        }

        return $theme;
    }

    /**
     * Upload images.
     *
     * @param array $data
     * @param ThemeCustomization $theme
     * @return void|string
     */
    public function uploadImage(array $data, ThemeCustomization $theme)
    {
        $locale = core()->getRequestedLocaleCode();

        if (isset($data[$locale]['deleted_sliders'])) {
            foreach ($data[$locale]['deleted_sliders'] as $slider) {
                Storage::delete(str_replace('storage/', '', $slider['image']));
            }
        }

        if (! isset($data[$locale]['options'])) {
            Log::info('No options found for image upload.', ['locale' => $locale]);
            return;
        }

        $options = [];

        foreach ($data[$locale]['options'] as $image) {
            if (isset($image['service_icon'])) {
                $options['services'][] = [
                    'service_icon' => $image['service_icon'],
                    'description'  => $image['description'],
                    'title'        => $image['title'],
                ];
            } elseif ($image['image'] instanceof UploadedFile) {
                try {
                    Log::info('Uploading slider image directly.', [
                        'original_name' => $image['image']->getClientOriginalName(),
                    ]);

                    // Save the file directly without conversion/caching
                    $path = Storage::putFile('theme/' . $theme->id, $image['image']);

                    Log::info('Image uploaded successfully.', [
                        'path' => $path,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Image upload error during slider upload.', [
                        'error' => $e->getMessage(),
                    ]);

                    session()->flash('error', $e->getMessage());
                    return redirect()->back();
                }

                // For static content, return the URL immediately
                if (($data['type'] ?? '') == 'static_content') {
                    return Storage::url($path);
                }

                // Store the direct URL (without a cache prefix)
                $options['images'][] = [
                    'image' => Storage::url($path),
                    'link'  => $image['link'],
                    'title' => $image['title'],
                ];
            } else {
                Log::warning('Image upload not triggered for slider; image data is not an UploadedFile.', [
                    'image_data' => $image,
                ]);
                $options['images'][] = $image;
            }
        }

        $translatedModel = $theme->translate($locale);
        $translatedModel->options = $options ?? [];
        $translatedModel->theme_customization_id = $theme->id;
        $translatedModel->save();

        Log::info('Theme customization options updated.', [
            'theme_id' => $theme->id,
            'locale'   => $locale,
            'options'  => $options,
        ]);
    }
}
