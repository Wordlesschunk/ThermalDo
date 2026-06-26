<?php

namespace App;

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

class PrintStyles
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $connector = new NetworkPrintConnector("192.168.1.100", 9100);
        $printer = new Printer($connector);
    }

    public function printBigTextCentre(Printer $printer, string $text): void
    {
        $printer->selectPrintMode(
            Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH
        );
        $printer->text($text . "\n");
        $printer->selectPrintMode();
        $printer->feed();
    }

    public function printText(Printer $printer, string $text, bool $underlined): void
    {
        if ($underlined) {
            $printer->setUnderline(true);
        }
        $printer->text($text . "\n");
        $printer->setUnderline(false);
    }

    public function printWarningMessage(Printer $printer, string $text): void
    {
        $printer->setEmphasis(true);
        $printer->setReverseColors(true);
        $printer->text($text . "\n");
        $printer->setReverseColors(false);
        $printer->setEmphasis(false);
    }




}
