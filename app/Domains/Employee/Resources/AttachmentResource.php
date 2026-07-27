<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray($request): array { return ['id' => $this->id, 'category' => $this->category, 'original_name' => $this->original_name, 'mime_type' => $this->mime_type, 'size' => $this->size, 'created_at' => $this->created_at?->toISOString()]; }
}
