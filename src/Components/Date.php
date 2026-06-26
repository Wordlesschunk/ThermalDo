<?php

declare(strict_types=1);

namespace App\Components;

use Mike42\Escpos\Printer;

final readonly class Date implements ComponentInterface
{
    public Printer $printer;

    public function __construct(
        Printer $printer
    ) {
        $this->printer = $printer;
    }

    public function print(): void
    {

    }
}

