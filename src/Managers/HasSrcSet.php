<?php

namespace SimpleImageManager\Managers;

trait HasSrcSet
{
    /**
     * @inheritDoc
     */
    public function srcsetMap(): array
    {
        $map = [];

        if (is_array($this->original) &&
            !empty($this->original['srcset'])) {
            $map[''] = $this->original['srcset'];
        }

        foreach ($this->formats as $format => $configuration) {
            if (!empty($configuration['srcset'])) {
                $map[$format] = $configuration['srcset'];
            }
        }

        uasort($map, function ($a, $b) {
            return (int)$b - (int)$a;
        });

        return $map;
    }
}
