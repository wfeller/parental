# Parental

[![Plant Tree](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20Tree&query=%24.total&url=https%3A%2F%2Fpublic.offset.earth%2Fusers%2Ftreeware%2Ftrees)](https://plant.treeware.earth/wfeller/parental)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen?style=for-the-badge)](https://plant.treeware.earth/wfeller/parental)

Parental is a Laravel package, forked from [calebporzio/parental](https://github.com/calebporzio/parental), that brings STI (Single Table Inheritance) capabilities to Eloquent.

### What is single table inheritance (STI)?

It's a fancy name for a simple concept: Extending a model (usually to add specific behavior), but referencing the same table.

## Licence

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/wfeller/parental) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

You can buy trees here [offset.earth/treeware](https://plant.treeware.earth/{vendor}/{package})

Read more about Treeware at [treeware.earth](http://treeware.earth)

## Installation

```bash
composer require "wfeller/parental"
```

Each time you add or remove child classes, you'll want to do the following:
```bash
php artisan parental:discover-children
```

This artisan command will simplify the following:
- Laravel Nova resource inheritance (when children don't already have a dedicated Nova resource)
- Adding child global scopes when querying parent

## Simple Usage

```php
// The "parent"
class User extends Model
{
    //
}
```

```php
// The "child"
class Admin extends User
{
    use \WF\Parental\HasParent;

    public function impersonate($user) {
        ...
    }
}
```

```php
// Returns "Admin" model, but reference "users" table:
$admin = Admin::first();

// Can now access behavior exclusive to "Admin"s
$admin->impersonate($user);
```

### What problem did we just solve?
Without Parental, calling `Admin::first()` would throw an error because Laravel would be looking for an `admins` table. Laravel generates expected table names, as well as foreign keys and pivot table names, using the model's class name. By adding the `HasParent` trait to the Admin model, Laravel will now reference the parent model's class name `users`.

## Accessing Child Models from Parents

```php
// First, we need to create a `type` column on the `users` table
Schema::table('users', function ($table) {
    $table->string('type')->nullable();
});
```

```php
// The "parent"
class User extends Model
{
    use WF\Parental\HasChildren;

    protected $fillable = ['type'];
}
```

```php
// A "child"
class Admin extends User
{
    use \WF\Parental\HasParent;
}
```

```php
// Another "child"
class Guest extends User
{
    use \WF\Parental\HasParent;
}
```


```php
// Adds row to "users" table with "type" column set to: "App/Admin"
Admin::create(...);

// Adds row to "users" table with "type" column set to: "App/Guest"
Guest::create(...);

// Returns 2 model instances: Admin, and Guest
User::all();
```

### What problem did we just solve?
Before, if we ran: `User::first()` we would only get back `User` models. By adding the `HasChildren` trait and a `type` column to the `users` table, running `User::first()` will return an instance of the child model (`Admin` or `Guest` in this case).

## Type Aliases
If you don't want to store raw class names in the type column, you can override them using the `$childTypes` property.

```php
class User extends Model
{
    use \WF\Parental\HasChildren;

    protected $fillable = ['type'];

    protected $childTypes = [
        'admin' => App\Admin::class,
        'guest' => App\Guest::class,
    ];
}
```

Now, running `Admin::create()` will set the `type` column in the `users` table to `admin` instead of `App\Admin`.

This feature is useful if you are working with an existing type column, or if you want to decouple application details from your database.

## Custom Type Column Name
You can override the default type column by setting the `$childColumn` property on the parent model.

```php
class User extends Model
{
    use \WF\Parental\HasChildren;

    protected $fillable = ['parental_type'];

    protected $childColumn = 'parental_type';
}
```
