Firelit-Library
===============

Firelit's standard PHP library provides a set of helpful classes for developing a website. The are created so that they can easily be used with an auto-loader, following the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).

Classes Included
----------------

### ApiResponse

### Email

### Encrypt

### HttpRequest

### Query

### Session

### User

### Vars

### Visitor

Auto-Loader Example
-------------------

The beauty of the auto-loader is that it will only load & parse PHP files that it needs. To use it, however, you must define an autoloader function. Here is one exmaple that could be used, created by the [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) Framework Interop Group:

```php
<?php

function autoload($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require $fileName;
}
```