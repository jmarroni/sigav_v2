<?php

namespace App\Exceptions;

use RuntimeException;

/** Ya existe una venta confirmada con esa referencia para este usuario de API. */
class ReferenciaDuplicada extends RuntimeException
{
}
