
# Flysystem adapter for the Publitio API

This package contains a [Flysystem](https://flysystem.thephpleague.com/) adapter for Publitio.

## Installation

TODO

## Usage

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Publitio\API;
use Publitio\FlysystemPublitio\PublitioAdapter;

class PublitioServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('publitio', function ($app, $config) {

            $client = new API($config['api_key'], $config['api_secret']);
            $adapter = new PublitioAdapter($client, $config);

            return new Filesystem($adapter);
        });
    }
}
```
add config > filesystems.php
```
    'disks' => [
        ...
        'publitio' => [
            'driver' => 'publitio',
            'api_key' => env('PUBLITIO_API_KEY', 'JbodQdBHSNvpix0yJAxN'),
            'api_secret' => env('PUBLITIO_API_SECRET', 'NPkny339LvH1txSnbQPjMPHMmAhAwLby'),
            'domain' => env('PUBLITIO_DOMAIN', 'https://media.publit.io'),
        ],
```

add config > app.php
```
        App\Providers\PublitioServiceProvider::class,
```
## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
