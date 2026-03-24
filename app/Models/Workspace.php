<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    public const DEFAULT_SETTINGS = [
        'icon' => 'folder_open',
        'color' => '#A0AEC0', // Neutral Gray/Silver
    ];

    protected $fillable = [

        'name',
        'owner_id',
        'parent_id',
        'depth',
        'is_archived',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Get the user that owns the workspace.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the parent workspace.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'parent_id');
    }

    /**
     * Get the child workspaces.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Workspace::class, 'parent_id');
    }
}
