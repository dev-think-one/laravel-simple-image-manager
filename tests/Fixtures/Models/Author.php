<?php

namespace SimpleImageManager\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleImageManager\Eloquent\HasThinkImage;
use SimpleImageManager\Eloquent\ThinkImage;

class Author extends Model
{
    use HasThinkImage;

    /** @inerhitDoc  */
    public function thinkImagesMap(): array
    {
        return [
            'avatar' => (new ThinkImage('avatars', $this->avatar))
                ->setDefaultPath('my/path/there.svg')
                ->setDefaultUrl(url('/img/default.svg')),
            'image' => 'feature-images',
        ];
    }

    public function avatarImage(): ThinkImage
    {
        return $this->thinkImage('avatar');
    }

    public function featureImage(): ThinkImage
    {
        return $this->thinkImage('image');
    }
}
