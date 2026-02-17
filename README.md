# Foundation

--- 

### What is this?

This is the base that I like to use for constructing API's with Laravel.

It is essentially a wrapper for Spatie's laravel-fractal.

---

## Installation

---

1. `composer require jamosaur/foundation`
2. Update your API controllers to extend `Jamosaur\Foundation\ApiController`. This extends the default Laravel controller but also adds more methods that we will use.
3. Update your API middleware in `app\Http\Kernel.php` to use `Jamosaur\Foundation\Middleware\ApiRequestMiddleware`

---

## Example Usage

### Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Jamosaur\Foundation\ApiController;

class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        $users = User::all();
        
        // By default, this will attempt to transform the data with
        // `\App\Transformers\UserTransformer`. The package takes replaces
        // the `Controller` in the controller name with Transformer
        // and attempts to load it from the `\App\Transformers\ namespace.
        return $this->transformCollection('users', $users)
            ->respond();
    }
    
    public function differentNamespace(): JsonResponse
    {
        $users = User::all();
        
        // You can define a different namespace for transformers.
        return $this->setTransformerNamespace('\\App\\Domain\\Transformers')
            ->transformCollection('users', $users)
            ->respond();
    }
    
    public function chainingTransformers(): JsonResponse
    {
        $user = User::all();
        $posts = Post::all();
        $photo = Photo::query()->first();
        
        // You can also chain methods to transform multiple
        // sets of data.
        return $this->setTransformer(new UserTransformer())
            ->transformCollection('users', $users)
            ->setTransformer(new PostTransformer())
            ->transformCollection('posts', $posts)
            ->setTransformer(new PhotoTransformer())
            ->transformItem('photo', $photo)
            ->respond();
    }
    
    public function definedTransformer(): JsonResponse
    {
        $users = User::query()->with('profile')->get();
        
        // You can override which transformer is used, and also pass in
        // any includes.
        return $this->setTransformer(new CustomTransformer(), ['profile'])
            ->transformCollection('users', $users)
            ->respond();
    }
}
```

---

## Important Notes

- Transformers **MUST** implement `Jamosaur\Foundation\Contracts\TransformerContract` and extend `League\Fractal\TransformerAbstract` 
