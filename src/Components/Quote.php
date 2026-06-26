<?php

declare(strict_types=1);

namespace App\Components;

use App\PrintStylesNew;
use Mike42\Escpos\Printer;

final readonly class Quote
{
    public Printer $printer;

    public function __construct(
        Printer $printer
    ) {
        $this->printer = $printer;
    }

    public function print(): void
    {
        $printerComponents = new PrintStylesNew($this->printer);

        $printerComponents->printBoxTitle('Quote Of The Day');
        $printerComponents->feed();

        $printerComponents->printText('Everyday stand guard at the door of your mind!');

        $this->printer->feed();
    }
}
