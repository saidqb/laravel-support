# laravel-support
progress build ...

### Requirement

laravel version > 10

### Requirement package before installation

```
composer require saidqb/core-php
```

### installation

```
composer require saidqb/laravel-support
```

### Usage Api
->collection harus dari response ->paginate(10)
```php
use Saidqb\LaravelSupport\SQ

SQ::make('PaginateApi')->collection($paginate)->get();
```

```php
use Saidqb\LaravelSupport\SQ

SQ::make('QueryFilter')
->request($request->all())
->select([
	'id',
	'name',
	'email',
])
->search([
	'name',
	'email',
]);

$dbquery = DB::table('users')

$query->query($dbquery);

return $this->response($query->get());
```