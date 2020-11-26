<?php

namespace LaravelEnso\Files\Services;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use LaravelEnso\Core\Models\User;
use LaravelEnso\Files\Contracts\Attachable;
use Symfony\Component\HttpFoundation\File\File as BaseFile;

class Files
{
    private Attachable $attachable;
    private BaseFile $file;
    private string $disk;
    private array $extensions;
    private array $mimeTypes;
    private bool $optimize;
    private array $resize;
    private string $separator;

    public function __construct(Attachable $attachable)
    {
        $this->attachable = $attachable;
        $this->disk = config('filesystems.default');
        $this->extensions = [];
        $this->mimeTypes = [];
        $this->optimize = false;
        $this->resize = [];
        $this->separator = DIRECTORY_SEPARATOR;
    }

    public function attach(File $file, string $originalName, ?User $user)
    {
        $this->file($file)
            ->validateFile()
            ->processImage()
            ->persistAttachedFile($originalName, $user);
    }

    public function upload(UploadedFile $file)
    {
        $this->file($file)
            ->validateUploadedFile()
            ->processImage()
            ->persistUploadedFile();
    }

    public function optimize($optimize)
    {
        $this->optimize = $optimize;

        return $this;
    }

    public function resize(array $resize)
    {
        $this->resize = $resize;

        return $this;
    }

    public function disk(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    public function extensions(array $extensions)
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function mimeTypes(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;

        return $this;
    }

    private function file(BaseFile $file)
    {
        $this->file = $file;

        return $this;
    }

    private function validateFile()
    {
        (new FileValidator(
            $this->file,
            $this->extensions,
            $this->mimeTypes
        ))->handle();

        return $this;
    }

    private function validateUploadedFile()
    {
        (new UploadedFileValidator(
            $this->file,
            $this->extensions,
            $this->mimeTypes
        ))->handle();

        return $this;
    }

    private function processImage()
    {
        (new ImageProcessor(
            $this->file,
            $this->optimize,
            $this->resize
        ))->handle();

        return $this;
    }

    private function persistAttachedFile(string $originalName, ?User $user)
    {
        $this->attachable->file()->create([
            'original_name' => $originalName,
            'disk' => $this->disk,
            'path' => "{$this->attachable->folder()}{$this->separator}{$this->file->getBaseName()}",
            'size' => $this->file->getSize(),
            'mime_type' => $this->file->getMimeType(),
            'created_by' => optional($user)->id,
        ]);
    }

    private function persistUploadedFile()
    {
        DB::transaction(function () {
            $this->attachable->file()->create([
                'original_name' => $this->file->getClientOriginalName(),
                'disk' => $this->disk,
                'path' => "{$this->attachable->folder()}{$this->separator}{$this->file->hashName()}",
                'size' => $this->file->getSize(),
                'mime_type' => $this->file->getMimeType(),
            ]);

            $this->file->store(
                $this->attachable->folder(),
                ['disk' => $this->disk]
            );
        });
    }
}
