<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'parent_id' => $this->parent_id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'url'       => $this->url,
            'status'    => $this->status,
            'banner_url' => $this->banner_path 
                                ? $this->banner_url 
                                : asset('images/default-category-banner.jpg'),
            'children'  => self::collection($this->children),
        ];
    }
}
