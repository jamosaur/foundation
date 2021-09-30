# Foundation

--- 

### What is this?

This is the base that I like to use for constructing API's with Laravel.

It is essentially a wrapper for spatie's laravel-fractal.

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
        
        // By default this will try to find a transformer in `App\Transformers` called 
        // `UserTransformer`. It guesses the name of the transformer based on the controller
        // name.
        return $this->transformCollection('users', $users)
            ->respond();
    }
    
    public function definedTransformer(): JsonResponse
    {
        $users = User::all();
        
        // You can override the transformer to use like this.
        return $this->setTransformer(new CustomTransformer())
            ->transformCollection('users', $users)
            ->respond();
    }
}
```

---

## Important Notes

- Transformers **MUST** implement `Jamosaur\Foundation\Contracts\TransformerContract` and extend `League\Fractal\TransformerAbstract` 
