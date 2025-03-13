<?php

namespace Webkul\Core\ImageCache;

use Config;
use Closure;
use Illuminate\Http\Response as IlluminateResponse;
use Intervention\Image\ImageCacheController;
use Intervention\Image\ImageManager;

class Controller extends ImageCacheController
{
    /**
     * Cache template
     *
     * @var string
     */
    protected $template;

    /**
     * Logo
     *
     * @var string
     */
    const BAGISTO_LOGO = 'https://lens.majesticdemo.com/themes/shop/default/build/assets/logo-942157c2.svg';

    /**
     * Get HTTP response of either original image file or
     * template applied file.
     *
     * @param  string  $template
     * @param  string  $filename
     * @return Illuminate\Http\Response
     */
    public function getResponse($template, $filename)
    {
        dd('ImageCache Controller Reached');

        \Log::info("ImageCache: Received request. Template: {$template}, Filename: {$filename}");
        switch (strtolower($template)) {
            case 'original':
                return $this->getOriginal($filename);

            case 'download':
                return $this->getDownload($filename);

            default:
                return $this->getImage($template, $filename);
        }
    }

    /**
     * Get HTTP response of template applied image file
     *
     * @param  string  $template
     * @param  string  $filename
     * @return Illuminate\Http\Response
     */
    public function getImage($template, $filename)
    {
        $this->template = $template;
        $cacheTime = ($template == 'logo') ? 10080 : config('imagecache.lifetime');
        \Log::info("ImageCache: getImage called. Cache time: {$cacheTime} minutes.");

        if ($template == 'logo') {
            $path = self::BAGISTO_LOGO;
            \Log::info("ImageCache: Using logo image: {$path}");
        } else {
            $template = $this->getTemplate($template);
            $path = $this->getImagePath($filename);
            \Log::info("ImageCache: Computed image path: {$path}");
        }

        $manager = new ImageManager(Config::get('image'));

        try {
            $content = $manager->cache(function ($image) use ($template, $path) {
                \Log::info("ImageCache: Starting processing for path: {$path}");
                // Check if file exists
                if (!file_exists($path)) {
                    \Log::error("ImageCache: File not found at path: {$path}");
                    throw new \Exception("File does not exist: {$path}");
                }
                if ($template instanceof Closure) {
                    \Log::info("ImageCache: Using closure callback for image processing.");
                    $template($image->make($path));
                } elseif (is_object($template)) {
                    \Log::info("ImageCache: Using filter template for image processing.");
                    $image->make($path)->filter($template);
                } else {
                    \Log::info("ImageCache: No template provided. Simply making image.");
                    $image->make($path);
                }
                \Log::info("ImageCache: Finished processing for path: {$path}");
            }, $cacheTime);

            \Log::info("ImageCache: Successfully generated cached image content.");
        } catch (\Exception $e) {
            \Log::error("ImageCache: Error processing image: " . $e->getMessage());
            if ($template != 'logo') {
                abort(404);
            }
            $content = '';
        }

        return $this->buildResponse($content);
    }

    /**
     * Builds HTTP response from given image data
     *
     * @param  string  $content
     * @return Illuminate\Http\Response
     */
    protected function buildResponse($content)
    {
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
        $eTag = md5($content);
        $notModified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag;
        $content = $notModified ? null : $content;
        $statusCode = $notModified ? 304 : 200;
        $maxAge = ($this->template == 'logo' ? 10080 : config('imagecache.lifetime')) * 60;

        \Log::info("ImageCache: Building response with MIME: {$mime}, Status: {$statusCode}, Max-Age: {$maxAge}");

        return new IlluminateResponse($content, $statusCode, [
            'Content-Type'   => $mime,
            'Cache-Control'  => 'max-age=' . $maxAge . ', public',
            'Content-Length' => strlen($content),
            'Etag'           => $eTag,
        ]);
    }
}
