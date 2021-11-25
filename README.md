### Table of Contents
1. [Features](#features)
2. [Installing](#installing)
3. [Usage](#usage)
4. [A real use case](#a-real-use-case)
5. [A custom token name](#a-custom-token-name)
6. [A custom failure handler](#a-custom-failure-handler)
7. [A custom token storage](#a-custom-token-storage)
8. [A custom token generation algorithm](#a-custom-token-generation-algorithm)
9. [A custom CSRF token class](#a-custom-csrf-token-class)
10. [The CSRF token in custom request headers](#the-csrf-token-in-custom-request-headers)

This is a PSR-15 compatible middleware that implements protection against cross-site request forgery.

In this package, the CSRF protection is organized according to the `Synchronizer Token` pattern described on the [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#synchronizer-token-pattern) website.

### Features
This package uses token masking (randomizing by XORing with a random secret). This method is recommended for protection against [BREACH](http://www.breachattack.com/) attacks.

The CSRF token is generated and saved once per session (this can be changed). **But thanks to the mask, the token will be unique each time it is requested**.

Masking the token eliminates the problem of false CSRF triggering on the server when you click the "Back" button in the browser.

### Installing
```
// php 7.4+
composer require solventt/csrf-protection ^0.1

// php 8.0+
composer require solventt/csrf-protection ^1.0
```
### Usage

```php
$csrfToken = new MaskedCsrfToken(new SessionTokenStorage(), new SecurityHelper());

$middleware = new CsrfMiddleware($csrfToken, new ResponseFactory());

// then add the middleware to the middlewares stack
```
To get a name and valid value of the token do:
```php
// data for a hidden HTML form field

$name = $csrfToken->getName();

$value = $csrfToken->getValue();
```
Somewhere in HTML:
```html
<input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
```
When the `getValue()` method is called the first time, the CSRF token is generated and stored into a storage (usually in a user session). On subsequent method calls, a CSRF token value is taken from a storage.

By default, the `getValue()` method returns a masked token. If you need a raw value of the CSRF token that is stored in a session, specify `false` as the first argument:
```php
$value = $csrfToken->getValue(false);
```
If you want to generate the token of a certain length, specify it as the second argument in the `getValue()` method:
```php
$value = $csrfToken->getValue(true, 30);
```
The default token length is **32** characters and cannot be less than 15.

Since the CSRF token is randomly masked, there is no need to regenerate it within the same session. But if such a need occurs, do:
```php
// a default length of the token is 32 chars
$csrfToken->regenerate();

// you can specify a different length
$csrfToken->regenerate(35);
```
### A real use case
It is an example of using the CSRF protection in the [Slim](https://github.com/slimphp/Slim) micro framework.

config/csrf.php:
```php
// the DI container definition

// a constructor of the CsrfMiddleware class has 2 mandatory arguments: $token and $responseFactory.
// Thanks to the dependency injection container, the CsrfTokenInterface and ResponseFactoryInterface
// dependencies will be automatically resolved during CsrfMiddleware instantiation

return [
    CsrfTokenInterface::class => function (ContainerInterface $c) {
        return new MaskedCsrfToken(new SessionTokenStorage(), new SecurityHelper());
    },

    ResponseFactoryInterface::class => fn () => new ResponseFactory(),
];
```
routing/middleware.php:
```php 
/**
* Adding the middleware to the stack
*
* @var Slim\App $app
*/
$app->add(CsrfMiddleware::class);
```
config/twig.php:
```php 
// the DI container definition

Environment::class => function (ContainerInterface $c) {
    
    ...
    
    $csrf = new TwigFunction('csrf', function () use ($c): string {

        /** @var MaskedCsrfToken $csrf */
        $csrf = $c->get(CsrfTokenInterface::class);

        $name = $csrf->getName();
        $token = $csrf->getValue();

        return sprintf('<input type="hidden" name="%s" value="%s">', $name, $token);
    });
    
    $twig->addFunction($csrf);
    
    ...
}
```
views/template.twig:
```php
...

{{ csrf()|raw }}

...
```

### A custom token name
The default token name is `_csrf`. But you can specify your own name by adding it as the third argument to the `MaskedCsrfToken` constructor:
```php
$csrfToken = new MaskedCsrfToken(
    new SessionTokenStorage(),
    new SecurityHelper(),
    'customTokenName'
);
```

### A custom failure handler
By default, if the CSRF tokens do not match, the client receives code 400, and the 'Bad Request' message.

But you can define your own logic for handling CSRF fails. Just add an anonymous function as the third argument to the `CsrfMiddleware` constructor:
```php
...

$session = $container->get(SessionInterface::class);

$logger = $container->get(LoggerInterface::class);

$responseFactory = $container->get(ResponseFactoryInterface::class);

$failureHandler = function () use ($session, $logger, $responseFactory): ResponseInterface {
    $session->destroy();
    $logger->error('CSRF check failed');
    $response = $responseFactory->createResponse(403);
    $response->getBody()->write('Forbidden');
    
    return $response;
};

$middleware = new CsrfMiddleware(
    $csrfToken, 
    new ResponseFactory(),
    $failureHandler
);
```
Notice: an anonymous function must return an instance that implements `ResponseInterface`.

### A custom token storage
Out of the box, this package provides the `SessionTokenStorage` class that works directly with the superglobal `$_SESSION`. If that's not what you need, you can write your own version of the token storage. Then your class must implement `TokenStorageInterface` interface:
```php
interface TokenStorageInterface
{
    public function get(string $tokenName): ?string;
    public function set(string $tokenName, string $value): void;
    public function remove(string $tokenName): void;
}
```
For example, your code uses an abstraction over `$_SESSION` to handle sessions. Then your token storage might look like this:
```php 
use Solventt\Csrf\Interfaces\TokenStorageInterface;
use Odan\Session\SessionInterface;

class CsrfSessionTokenStorage implements TokenStorageInterface
{
    public function __construct(private SessionInterface $session) {}

    public function get(string $tokenName): ?string
    {
        /** @var mixed|null $value */
        $value = $this->session->get($tokenName);

        return is_string($value) ? $value : null;
    }

    public function set(string $tokenName, string $value): void
    {
        $this->session->set($tokenName, $value);
    }

    public function remove(string $tokenName): void
    {
        $this->session->remove($tokenName);
    }
```
### A custom token generation algorithm
You can define your own logic for generating the CSRF token and adding/removing the token mask. To do this, your class must implement `SecurityInterface`:
```php
interface SecurityInterface
{
    /** 
    * Generates a cryptographically secure value  
    */
    public function generateToken(int $length): string;
    
    /** 
    * Applies a random mask to the CSRF token making it unique when its requested 
    */
    public function addMask(string $token): string;
    
    /** 
    * Removes the mask from the CSRF token previously masked with the 'addMask' method 
    */
    public function removeMask(string $token): string;
}
```
### A custom CSRF token class
This package provides the `MaskedCsrfToken` class representing the CSRF token. But you can write your own implementation of the token according to the `CsrfTokenInterface`:
```php
interface CsrfTokenInterface
{
    public const DEFAULT_NAME = '_csrf';

    public function getName(): string;
    public function getValue(): string;
    
    /** 
    * Compares the token from the request with the token found in a token storage
    */
    public function equals(string $requestToken): bool;
}
```
### The CSRF token in custom request headers
If no CSRF token is found in request body, the middleware checks for the `X-CSRF-Token` header. You can provide your own header name using the `setHeaderName` method:
```php
/**  
 * @var CsrfMiddleware $middleware 
 */
$middleware->setHeaderName('X-CUSTOM-HEADER');
```
It is relevant, for example, for AJAX requests.
