<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sub_heading' => $this->sub_heading,
            'content' => $this->content,
            'image' => $this->image,
            'category' => ucfirst($this->category),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_image_alt' => $this->meta_image_alt,
            'slug' => $this->slug,
        ];
    }
}
