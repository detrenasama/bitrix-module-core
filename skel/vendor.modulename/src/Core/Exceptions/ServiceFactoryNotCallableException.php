<?php

namespace Vendor\ModuleName\Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ServiceFactoryNotCallableException extends \Exception implements NotFoundExceptionInterface {}