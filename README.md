## About

A set of custom fixers for [ PHP CS Fixer ](https://github.com/FriendsOfPHP/PHP-CS-Fixer).

> [!NOTE]
> This is in active development. New fixers will be introduced gradually.

<br>

## Fixers

<br>

### MethodChainingIndentationFixer

Indents each chained methods.

```diff
- Foo::bar()->baz()->qux()->quux()->corge();

+ Foo::bar()
+     ->baz()
+     ->qux()
+     ->quux()
+     ->corge();
```
> [!TIP]
> `single-line` : Set chains on single line `{true|false}`\
> `multi-line` : Set chains on next line if `{number}` chains

<br>

### MultipleLinesAfterImportsFixer

Adds a given number of lines after imports.

```diff
- use Baz;
- class Qux {}

+ use Baz;
+
+
+ class Qux {}
```
> [!TIP]
> `lines` : Set `{number}` blank lines after the use statements block

<br>

### SpacesInsideSquareBracesFixer :

Adds spaces inside squared braces.

```diff
- $foo = ["bar", "baz", "qux"];

+ $foo = [ "bar", "baz", "qux" ];
```
> [!TIP]
> `space` : Set space inside parentheses `{single|none}`.

<br>
<br>
<br>
<br>

## Installation

1. Install dependency

```bash
composer require --dev capsulescodes/php-cs-fixer-custom-fixers
```

<br>

## Usage


- Using `.php-cs-fixer.php` config file by [ PHP CS Fixer ](https://github.com/junstyle/vscode-php-cs-fixer)



```php
<?php

use PhpCsFixer\Config;


return ( new PhpCsFixer\Config() )
    ...
    ->registerCustomFixers( [
        ...
        new \CapsulesCodes\PhpCsFixerCustomFixers\Fixers()
        ...

        or

        ...
        new \CapsulesCodes\PhpCsFixerCustomFixers\MethodChainingIndentationFixer(),
        new \CapsulesCodes\PhpCsFixerCustomFixers\MultipleLinesAfterImportsFixer(),
        new \CapsulesCodes\PhpCsFixerCustomFixers\SpacesInsideSquareBracesFixer()
        ...

    ] )
    ->setRules( [

        ...
        "CapsulesCodes/method_chaining_indentation" : { "multi-line" : 4 },
        "CapsulesCodes/multiple_lines_after_imports" : { "lines" : 2 },
        "CapsulesCodes/spaces_inside_square_braces" : { "space" : "single" }
        ...

   ] )
;
```

<br>

---

<br>

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
Please make sure to update tests as appropriate.

## Credits

[Capsules Codes](https://github.com/capsulescodes)

## License

[MIT](https://choosealicense.com/licenses/mit/)
