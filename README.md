Caffeinated Bonsai
====================
[![Laravel 5.0](https://img.shields.io/badge/Laravel-5.0-orange.svg?style=flat-square)](http://laravel.com)
[![Laravel 5.1](https://img.shields.io/badge/Laravel-5.1-orange.svg?style=flat-square)](http://laravel.com)
[![Source](http://img.shields.io/badge/source-caffeinated/bonsai-blue.svg?style=flat-square)](https://github.com/caffeinated/bonsai)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

**Bonsai** (盆栽, lit. *plantings in tray*, from *bon*, a tray or low-sided pot and *sai*, a planting or plantings, is a Japanese art form using miniature trees grown in containers.

Caffeinated Bonsai is an experimental project to handle and process assets for any Laravel 5 application - much like [CodeSleeve's Asset Pipeline](https://github.com/CodeSleeve/asset-pipeline) package provided for Laravel 4 (which is now deprecated due to Laravel Elixer in Laravel 5).

The package follows the FIG standards PSR-1, PSR-2, and PSR-4 to ensure a high level of interoperability between shared PHP code. At the moment the package is not unit tested, but is planned to be covered later down the road.

Quick Installation
------------------
Begin by installing the package through Composer. Depending on what version of Laravel you are using (5.0 or 5.1), you'll want to pull in the `~1.0` or `~2.0` release, respectively:

#### Laravel 5.1.x

##### Composer
```
composer require caffeinated/bonsai=~3.0
```

##### Service Provider
```php
Caffeinated\Bonsai\BonsaiServiceProvider::class,
```

##### Facade
```php
'Bonsai' => Caffeinated\Bonsai\Facades\Bonsai::class,
```

And that's it! With your coffee in reach, start planting some assets!

Documentation
-------------

#### Bonsai
The `Bonsai` facade returns the instance of `Caffeinated\Bonsai\Factory`.
Instead of the facade you can also use dependency injection by type hinting the `Caffeinated\Bonsai\Contracts\Factory` interface.
Alternatively you can use the IoC container's `make` method by either using the interface or alias `caffeinated.bonsai`.
 
###### Method overview

| method | paramters | description |
|:-------|:----------|:------------|
| `Bonsai::make($handle, $path, array $dependencies = [])` | (string) $handle = name/id, (string) $path = path to asset | Create and return a new asset instance |


###### Simple examples
```php

```

Now, to add assets at anytime (and anywhere in your code), simply call `Bonsai:add()`:

```php
Bonsai::add('assets/css/example.css');
```

### Defining Dependencies
Assets may depend on other assets being loaded before them. You can easily tell Bonsai about any dependencies your asset files may have against each other by using the `dependsOn()` method.

```php
Bonsai::add('assets/css/example.css')->dependsOn('bootstrap');
Bonsai::add('assets/css/bootstrap.css', 'bootstrap');
```

The above will generate the following CSS:

```html
<link rel="stylesheet" href="assets/css/bootstrap.css">
<link rel="stylesheet" href="assets/css/example.css">
```

### Rendering Assets
To echo out your assets within your layout, simply use the `css()` and `js()` methods:

#### Blade

```html
{!! $bonsai->css() !!}

{!! $bonsai->js() !!}
```

#### Twig

```html
{{ bonsai.css()|raw }}

{{ bonsai.js()|raw }}
```

TODO
----
- ~~Check for dependencies (`dependsOn()` method) and load dependencies first, in order when rendering within a view.~~
- Combine and minify assets into one cached file when in the production environment.
- ~~Add the ability to parse a bonsai.json file for assets that can be registered for use.~~
