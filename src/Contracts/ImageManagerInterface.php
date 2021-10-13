<?php

namespace SimpleImageManager\Contracts;

use Illuminate\Http\UploadedFile;

interface ImageManagerInterface
{
    /**
     * Upload file and if exists old file than delete it.
     *
     * @param UploadedFile $image
     * @param string|null $fileName
     * @param string|null $oldFile
     *
     * @return string
     */
    public function upload(UploadedFile $image, ?string $fileName = null, ?string $oldFile = null): string;

    /**
     * Delete file from storage.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function delete(string $fileName): bool;

    /**
     * Delete single file from storage.
     *
     * @param string $fileName
     * @param string|null $format
     *
     * @return bool
     */
    public function deleteSingle(string $fileName, ?string $format = null): bool;

    /**
     * @param string $fileName
     * @param string|null $format
     *
     * @return string|null
     */
    public function path(string $fileName, ?string $format): ?string;

    /**
     * @param string $fileName
     * @param string|null $format
     *
     * @return string|null
     */
    public function url(string $fileName, ?string $format): ?string;

    /**
     * Array map of srcset
     *
     * @return array
     */
    public function srcsetMap(): array;
}
