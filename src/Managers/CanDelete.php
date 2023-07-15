<?php

namespace SimpleImageManager\Managers;

trait CanDelete
{
    /**
     * List of deleted configuration formats.
     * Useful if you deleted some configuration but still files exists for old created files
     * and you need do delete these formats on delete file.
     *
     * @var array
     */
    public array $deletedFormats = [];

    /**
     * Clear empty directory after deletion files.
     * Warning: this function is memory and time-consuming when there can be too many files.
     *
     * @var bool
     */
    public bool $truncateDir = false;

    /**
     * @param array $formats
     *
     * @return $this
     */
    public function setDeletedFormats(array $formats = []): static
    {
        $this->deletedFormats = $formats;

        return $this;
    }

    /**
     * @param bool $truncateDir
     * @return $this
     */
    public function truncateDir(bool $truncateDir = true): static
    {
        $this->truncateDir = $truncateDir;

        return $this;
    }

    /**
     * Get files list with all formats to delete.
     *
     * @param string $fileName
     * @return array
     */
    protected function filesToDelete(string $fileName): array
    {
        if (!$fileName) {
            return [];
        }

        $filesToDelete = [
            $fileName,
        ];

        [$name, $extension] = $this->explodeFilename($fileName);

        foreach (array_keys($this->formats) as $format) {
            $filesToDelete[] = "{$name}-{$format}.{$extension}";
        }

        foreach ($this->deletedFormats as $format) {
            $filesToDelete[] = "{$name}-{$format}.{$extension}";
        }

        return array_unique($filesToDelete);
    }

    /**
     * Clear empty directory if this is required.
     *
     * @param string $fileName
     * @return bool
     */
    protected function truncateDirectory(string $fileName): bool
    {
        if (!$this->truncateDir) {
            return false;
        }

        $directoryName = dirname($fileName);

        if (
            !$directoryName ||
            !empty($this->storage()->allFiles($directoryName))
        ) {
            return false;
        }

        return $this->storage()->deleteDirectory($directoryName);
    }
}
