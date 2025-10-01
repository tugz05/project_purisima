<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'is_published',
        'is_featured',
        'published_at',
        'expires_at',
        'image_path',
        'attachments',
        'author_name',
        'author_position',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'attachments' => 'array',
    ];

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_featured', 'desc')
                    ->orderBy('priority', 'desc')
                    ->orderBy('published_at', 'desc')
                    ->orderBy('sort_order', 'asc');
    }

    // Accessors
    public function getExcerptAttribute()
    {
        return \Str::limit(strip_tags($this->content), 150);
    }

    public function getIsActiveAttribute()
    {
        if (!$this->is_published) {
            return false;
        }

        if ($this->published_at && $this->published_at > now()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'blue',
        };
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'urgent' => 'red',
            'event' => 'green',
            'notice' => 'blue',
            'general' => 'gray',
            default => 'gray',
        };
    }
}
