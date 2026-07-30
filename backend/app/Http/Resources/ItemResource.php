<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'notes' => $this->notes,
            'serial_number' => $this->serial_number,
            'quantity' => $this->quantity,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'project_id' => $this->project_id,
            'purchase_price_cents' => $this->purchase_price_cents,
            'current_value_cents' => $this->current_value_cents,
            'purchased_at' => $this->purchased_at?->toDateString(),
            'warranty_until' => $this->warranty_until?->toDateString(),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
