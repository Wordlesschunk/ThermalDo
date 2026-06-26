<?php

declare(strict_types=1);

namespace App\Components;

use App\PrintStylesNew;
use Mike42\Escpos\Printer;

final readonly class Schedule
{
    public Printer $printer;

    public function __construct(
        Printer $printer
    ) {
        $this->printer = $printer;
    }

    public function print(array $scheduledItemsToday): void
    {
        $printerComponents = new PrintStylesNew($this->printer);

        $printerComponents->printBoxTitle('Schedule For Today');;
        $printerComponents->feed();

        if (empty($scheduledItemsToday)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $printerComponents->printText('No scheduled items for today!');
            $this->printer->setJustification();
        }

        foreach ($scheduledItemsToday as $scheduledItem) {
            $printerComponents->printText($scheduledItem);
        }

        $this->printer->feed();
    }
}
