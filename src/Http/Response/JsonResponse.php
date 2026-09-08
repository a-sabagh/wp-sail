<?php

namespace WPSail\Http\Response;

use Aimeos\Macro\Macroable;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

class JsonResponse extends SymfonyJsonResponse 
{
    use Macroable;
}
