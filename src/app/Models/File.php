<?php

namespace LaravelEnso\Files\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use LaravelEnso\Files\App\Facades\FileBrowser;
use LaravelEnso\Files\App\Traits\FilePolicies;
use LaravelEnso\TrackWho\App\Traits\CreatedBy;

class File extends Model
{
    use CreatedBy, FilePolicies;

    protected $fillable = ['original_name', 'saved_name', 'size', 'mime_type', 'created_by'];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function temporaryLink()
    {
        return url()->temporarySignedRoute(
            'core.files.share',
            now()->addSeconds(config('enso.files.linkExpiration')),
            ['file' => $this->id]
        );
    }

    public function type()
    {
        return FileBrowser::folder($this->attachable_type);
    }

    public function path()
    {
        return Storage::path(
            $this->attachable->folder()
            .DIRECTORY_SEPARATOR
            .$this->saved_name
        );
    }

    public function scopeVisible($query)
    {
        $query->hasMorph(
            'attachable', FileBrowser::models()->toArray()
        );
    }

    public function scopeForUser($query, $user)
    {
        $query->when(! $user->isAdmin() && ! $user->isSupervisor(), fn ($query) => $query
            ->whereCreatedBy($user->id));
    }

    public function scopeOrdered($query)
    {
        $query->orderBy('created_at', 'desc');
    }

    public function scopeBetween($query, $interval)
    {
        $query->when($interval->min, fn ($query) => $query->where(
            'created_at', '>=', Carbon::parse($interval->min)
        ))->when($interval->max, fn ($query) => $query->where(
            'created_at', '<=', Carbon::parse($interval->max)
        ));
    }

    public function scopeFilter($query, $search)
    {
        return $query->when($search, fn ($query) => $query
            ->where('original_name', 'LIKE', '%'.$search.'%'));
    }
}
