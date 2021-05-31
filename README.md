# Saigns Web Framework


## 1 Include class folder
```php
foreach ( glob('include/*.{php}', GLOB_BRACE) as $file )
{
    require_once($file);
}
```
