<?php

namespace App\Http\Resources;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->owner_id,
            'parent_id' => $this->parent_id,
            'path' => $this->when(isset($this->path), $this->path),
            'depth' => $this->depth,
            'settings' => $this->settings ?? Workspace::DEFAULT_SETTINGS,
            'is_archived' => (bool) $this->is_archived,
            'children_count' => $this->whenCounted('children'),
            'children' => WorkspaceResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
