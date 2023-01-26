# Mnemosyne - a PDO Database Layer

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Latest Stable Version](https://poser.pugx.org/mrmadclown/mnemosyne/v/stable.svg)](https://packagist.org/packages/mrmadclown/mnemosyne)
[![Total Downloads](https://poser.pugx.org/mrmadclown/mnemosyne/downloads)](https://packagist.org/packages/mrmadclown/mnemosyne)
![example workflow](https://github.com/mrmadclown/mnemosyne/actions/workflows/tests.yml/badge.svg?event=push)
![example workflow](https://github.com/mrmadclown/mnemosyne/actions/workflows/static%20code%20analysis.yml/badge.svg?event=push)

This is a simple PDO based mysql Query Builder

### Installation

```bash
composer require mrmadclown/mnemosyne
```

### Usage

The Builder gets constructed by passing an instance of the ```PDO::class``` to
the ```\MrMadClown\Mnemosyne\Builder::class```

```php
use \PDO;

$pdo = new \PDO();
$builder = new \MrMadClown\Mnemosyne\Builder($pdo);
```

With the Builder Object a mysql query can be build similar to how a query would be written:

```php
...

$builder->select('*')->from('users')->where('id', 1);
```

### Important

By default, the PDO Fetch Mode is set to ```PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;``` which means you have to have a
class which represents your table

```php
#User.php
class User {
    public int $id;
    public string $name;
}

$builder->setClassName(User::class)->fetchAll(); // returns an array of Users
```

you can use the magic ``__set`` method to map your database columns into your model.
If you don`t want a different FetchMode you can call ```$builder->setFetchMode(\PDO::FETCH_ASSOC)```
to change the fetch mode of the Builder instance.
