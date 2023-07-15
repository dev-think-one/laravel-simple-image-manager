# Laravel: Simple image manager

![Packagist License](https://img.shields.io/packagist/l/think.studio/laravel-simple-image-manager?color=%234dc71f)
[![Packagist Version](https://img.shields.io/packagist/v/think.studio/laravel-simple-image-manager)](https://packagist.org/packages/think.studio/laravel-simple-image-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/think.studio/laravel-simple-image-manager)](https://packagist.org/packages/think.studio/laravel-simple-image-manager)
[![Build Status](https://scrutinizer-ci.com/g/dev-think-one/laravel-simple-image-manager/badges/build.png?b=main)](https://scrutinizer-ci.com/g/dev-think-one/laravel-simple-image-manager/build-status/main)
[![Code Coverage](https://scrutinizer-ci.com/g/dev-think-one/laravel-simple-image-manager/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/dev-think-one/laravel-simple-image-manager/?branch=main)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dev-think-one/laravel-simple-image-manager/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/dev-think-one/laravel-simple-image-manager/?branch=main)

Simple package to save multiple images formats when upload an image. For example:

- myimage.png (original)
- myimage-small.png
- myimage-thumb.png
- myimage-100x100.png

Package works as wrapper of [spatie/image](https://spatie.be/docs/image) package. So when you create formats you can use
all methods of this package. All configuration options description you can find
in [config file](config/simple-image-manager.php)

## Installation

Install the package via composer:

```bash
composer require think.studio/laravel-simple-image-manager
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="SimpleImageManager\ServiceProvider" --tag="config"
```

## Usage

Upload file example:

```php
if ($request->hasFile('avatar')) {
    $user->avatar = SimpleImageManager::driver('avatars')
                                      ->upload(
                                          $request->file('avatar'), 
                                          null /* or some name */,
                                          $user->avatar /* old file name to replace it */
                                       );
}
$user->save();
```

Other methods:

```php
// Get file url
$url = SimpleImageManager::driver('avatars')->url((string) $user->avatar, $format);
// Get file path
$path = SimpleImageManager::driver('avatars')->path((string) $user->avatar, $format);
// Delete specific format
$isDeleted = SimpleImageManager::driver('avatars')->deleteSingle((string) $user->avatar, $format);
// Delete all files
if($isDeletedAll = SimpleImageManager::driver('avatars')->delete((string) $user->avatar)) {
    $user->avatar = null;
}
```

### Advanced usage

#### Create specific trait

1. Create trait

```php
// app/Models/Traits/HasAvatar.php
namespace App\Models\Traits;

use Illuminate\Http\UploadedFile;
use SimpleImageManager\Facades\SimpleImageManager;

trait HasAvatar {
    
    /**
     * Driver name.
     *
     * @return string|null
     */
    public function avatarManagerDriver(): ?string {
        if ( property_exists( $this, 'avatarManagerDriver' ) ) {
            return $this->avatarManagerDriver;
        }

        return null;
    }

    /**
     * Field key name.
     *
     * @return string
     */
    public function avatarKey(): string {
        if ( property_exists( $this, 'avatarKey' ) ) {
            return $this->avatarKey;
        }

        return 'avatar';
    }

    /**
     * Image manager.
     *
     * @return \SimpleImageManager\Contracts\ImageManagerInterface
     */
    public function avatarManager(): \SimpleImageManager\Contracts\ImageManagerInterface {
        return SimpleImageManager::driver( $this->avatarManagerDriver() );
    }

    /**
     * Upload file to storage.
     *
     * @param UploadedFile $image
     * @param string|null $filename
     *
     * @return string|null Storage file name.
     */
    public function avatarUpload( UploadedFile $image, ?string $filename = null ): ?string {
        return $this->avatarManager()
                    ->upload( $image, $filename, $this->{$this->avatarKey()} );
    }

    /**
     * Delete file from storage.
     *
     * @return bool Storage file name.
     */
    public function avatarDelete( bool $updateField = true, bool $persist = false ): bool {
        $result = $this->avatarManager()->delete( $this->{$this->avatarKey()} );
        if ( $result && $updateField ) {
            $this->{$this->avatarKey()} = null;
            if ( $persist ) {
                $this->save();
            }
        }

        return $result;
    }

    /**
     * Full path to file.
     *
     * @param string|null $format
     *
     * @return string|null
     */
    public function avatarPath( ?string $format = null ): ?string {
        return $this->avatarManager()->path( (string) $this->{$this->avatarKey()}, $format );
    }

    /**
     * File url.
     *
     * @param string|null $format
     *
     * @return string|null
     */
    public function avatarUrl( ?string $format = null ): ?string {
        return $this->avatarManager()->url( (string) $this->{$this->avatarKey()}, $format );
    }
}
```

2. Use trail

```php
class User //...
{
    //...
    use HasAvatar;
    // ...

    protected string $avatarManagerDriver = 'avatars';
}
```

3. Manipulate using trait

```php
if ($request->hasFile('avatar')) {
            $user->avatar = $user->avatarUpload(
                    $request->file('avatar'), 
                    null /* or some name */
                );
}
$user->save();


$url = $user->avatarUrl();
```

#### Use internal trait

```php
use Illuminate\Database\Eloquent\Model;
use \SimpleImageManager\Eloquent\HasThinkImage;
use \SimpleImageManager\Eloquent\ThinkImage;

class Author extends Model
{
    use HasThinkImage;

    /** @inerhitDoc  */
    public function thinkImagesMap(): array {
        return [
            'avatar' => (new ThinkImage('avatars', $this->avatar))
                ->setDefaultUrl(url('/img/default.svg')),
            'image' => 'feature-images',
        ];
    }

    public function avatarImage(): ThinkImage {
        return $this->thinkImage('avatar');
    }

    public function featureImage(): ThinkImage {
        return $this->thinkImage('image');
    }
}
```

Then you can use it:

```php
/** @var Author Author */
$author->featureImage()->img();
$author->avatarImage()->url();
$author->thinkImage('avatar')->path();
```

#### Usage with laravel-nova

```php
Avatar::make('Avatar', 'avatar')
    ->store(
      fn ($request, Model $model, $attribute, $requestAttribute, $storageDisk, $storageDir) => fn () => $model->fill([
          $attribute => $model->avatarUpload($request->file($requestAttribute), $model->uuid),
      ])->save()
    )->maxWidth(100)
    ->preview(fn ($value, $storageDisk, $model) => $model->avatarUrl('small'))
    ->thumbnail(fn ($value, $storageDisk, $model) => $model->avatarUrl('small'))
    ->delete(fn ($request, $model, $storageDisk, $storagePath) => $model->avatarDelete()),
```

## Credits

- [![Think Studio](https://yaroslawww.github.io/images/sponsors/packages/logo-think-studio.png)](https://think.studio/)
