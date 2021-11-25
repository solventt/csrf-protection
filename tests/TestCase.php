<?php

declare(strict_types=1);

namespace Solventt\Csrf\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use ReflectionMethod;
use ReflectionProperty;

class TestCase extends PhpUnitTestCase
{
    protected static function assertInaccessiblePropertySame($expected, $obj, string $name)
    {
        $prop = new ReflectionProperty($obj, $name);
        $prop->setAccessible(true);
        self::assertSame($expected, $prop->getValue($obj));
    }

    protected static function invokeInaccessibleMethod($objectOrMethod, $method = null, ...$args)
    {
        $method = new ReflectionMethod($objectOrMethod, $method);
        $method->setAccessible(true);

        if (is_string($objectOrMethod)) {
            $objectOrMethod = null;
        }

        return $method->invoke($objectOrMethod, ...$args);
    }
}