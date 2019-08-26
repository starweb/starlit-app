# Starlit App

[![Build Status](https://travis-ci.org/starweb/starlit-app.svg?branch=master)](https://travis-ci.org/starweb/starlit-app)
[![Code Coverage](https://scrutinizer-ci.com/g/starweb/starlit-app/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/starweb/starlit-app/?branch=master)

A lightweight MVC style microframework that's basically just a wiring of Symfony's HttpFoundation and Routing components.  

## Installation
Add the package as a requirement to your `composer.json`:
```bash
$ composer require starlit/app
```

## Usage example
```php
<?php
// In public index.php file
$app = new BaseApp();
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $app->handle($request);
$response->send();

// IndexController.php
class IndexController extends AbstractController
{
    public function indexAction()
    {
        $this->view->headline = 'Hello, world!"
    }
}

// index.html.php
?>
<h1><?=$this->getEscaped('headline')?></h1>

```


## Requirements
- Requires PHP 5.6 or above.

## License
This software is licensed under the BSD 3-Clause License - see the `LICENSE` file for details.

## Credits
- [@jandreasn](https://github.com/jandreasn)
- [All contributors](https://github.com/starweb/starlit-app/contributors)