<?php

declare(strict_types=1);

namespace App\Components;

use App\PrintStylesNew;
use Mike42\Escpos\Printer;

final readonly class TodoList
{
    public Printer $printer;

    public function __construct(
        Printer $printer
    ) {
        $this->printer = $printer;
    }

    public function print(array $todoItems): void
    {
        $printerComponents = new PrintStylesNew($this->printer);

        $printerComponents->printBoxTitle('Todo List');
        $printerComponents->feed();

        if (empty($todoItems)) {
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $printerComponents->printText('No todo items for today!');
            $this->printer->setJustification();
        }

        foreach ($todoItems as $item) {
            $printerComponents->printTaskText($item);
        }

        $this->printer->feed();
    }
}
