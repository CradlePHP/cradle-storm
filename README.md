# Cradle Storm

Integrates storm as a Cradle Package

## Requires

 - [cradlephp/package](https://github.com/CradlePHP/package)
 - [cradlephp/storm](https://github.com/CradlePHP/storm)

## Install

```bash
composer install cradlephp/cradle-storm
```

Once installed set up a PDO package.

```php
cradle()->register('pdo');
cradle('pdo')->mapPackageMethods(new PDO(...));
```

Then you are all set.

```php
cradle('event')->emit('storm-insert', ...);
```

To change databases on the fly use the following code.

```php
use Cradle\Storm\SqlFactory;

cradle('storm')->mapPackageMethods(SqlFactory::load(new PDO(...)));
```

More information can be found in the [Storm](https://github.com/CradlePHP/storm)
library.
