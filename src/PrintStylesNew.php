<?php

namespace App;

use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

class PrintStylesNew
{
    public Printer $printer;

    /**
     * @throws \Exception
     */
    public function __construct(
        Printer $printer
    )
    {
        $this->printer = $printer;
    }

    public function close(): void
    {
        $this->printer->cut();
        $this->printer->close();
    }

    public function feed(int $lines = 1): void
    {
        $this->printer->feed($lines);
    }

    public function hr(string $char = "-"): void
    {
        $this->printer->text(str_repeat($char, 48) . "\n");
    }

    public function dashedRule(): void
    {
        $this->printer->text("- - - - - - - - - - - - - - - - - - - -\n");
    }

    public function printText(string $text): void
    {
        $this->printer->text($text . "\n");
    }

    public function printTaskText(string $text): void
    {
        $this->printer->text(sprintf("[] %s", $text) . "\n");
    }

    public function printCentered(string $text): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->text($text . "\n");
        $this->printer->setJustification();
    }

    public function printRight(string $text): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
        $this->printer->text($text . "\n");
        $this->printer->setJustification();
    }

    public function printBold(string $text): void
    {
        $this->printer->setEmphasis(true);
        $this->printer->text($text . "\n");
        $this->printer->setEmphasis(false);
    }

    public function printUnderline(string $text): void
    {
        $this->printer->setUnderline(true);
        $this->printer->text($text . "\n");
        $this->printer->setUnderline(false);
    }

    public function printBig(string $text): void
    {
        $this->printer->selectPrintMode(
            Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT
        );

        $this->printer->text($text . "\n");
        $this->printer->selectPrintMode();
    }

    public function resetJustification(): void
    {
        $this->printer->setJustification();
    }

    public function printDoubleWidth(string $text): void
    {
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $this->printer->text($text . "\n");
        $this->printer->selectPrintMode();
    }

    public function printDoubleHeight(string $text): void
    {
        $this->printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $this->printer->text($text . "\n");
        $this->printer->selectPrintMode();
    }

    public function printSmall(string $text): void
    {
        $this->printer->selectPrintMode(Printer::MODE_FONT_B);
        $this->printer->text($text . "\n");
        $this->printer->selectPrintMode();
    }

    public function printReverse(string $text): void
    {
        $this->printer->setReverseColors(true);
        $this->printer->text($text . "\n");
        $this->printer->setReverseColors(false);
    }

    public function printUpsideDown(string $text): void
    {
        $this->printer->setUpsideDown(true);
        $this->printer->text($text . "\n");
        $this->printer->setUpsideDown(false);
    }

    public function printWarning(string $text): void
    {
        $this->printer->setReverseColors(true);
        $this->printer->setEmphasis(true);
        $this->printer->text($text . "\n");
        $this->printer->setReverseColors(false);
        $this->printer->setEmphasis(false);
    }

    public function printBanner(string $text): void
    {
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setReverseColors(true);

        $this->printer->selectPrintMode(
            Printer::MODE_DOUBLE_WIDTH |
            Printer::MODE_DOUBLE_HEIGHT
        );

        $this->printer->text($text . "\n");

        $this->printer->selectPrintMode();
        $this->printer->setReverseColors(false);
        $this->printer->setJustification();
    }

    public function printBoxTitle(string $text): void
    {
        $this->hr("=");

        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
        $this->printer->setEmphasis(true);
        $this->printer->text($text . "\n");
        $this->printer->setEmphasis(false);
        $this->printer->setJustification();

        $this->hr("=");
    }

    public function printTwoColumns(string $left, string $right): void
    {
        $leftWidth = 48 - strlen($right);

        $this->printer->text(
            str_pad($left, $leftWidth) .
            $right .
            "\n"
        );
    }

    public function printLabelValue(string $label, string $value): void
    {
        $this->printer->setEmphasis(true);
        $this->printer->text($label . ": ");
        $this->printer->setEmphasis(false);
        $this->printer->text($value . "\n");
    }

    public function printBarcode(string $code): void
    {
        $this->printer->barcode($code, Printer::BARCODE_CODE39);
        $this->printer->feed();
    }

    public function printQrCode(string $data): void
    {
        $this->printer->qrCode(
            $data,
            Printer::QR_ECLEVEL_L,
            8
        );

        $this->printer->feed();
    }

    public function printImage(string $path): void
    {
        $image = EscposImage::load($path);
        $this->printer->graphics($image);
        $this->printer->feed();
    }

    public function printWeather(
        string $location,
        string $condition,
        float $temperature,
        float $feelsLike,
        float $wind,
        int $humidity,
        int $pressure,
        float $rain = 0
    ): void {

        $icons = [
            'sunny' => [
                "   \\   /",
                "    .-. ",
                " ― (   ) ―",
                "    `-' ",
                "   /   \\",
            ],
            'cloudy' => [
                "           ",
                "     .--.  ",
                "  .-(    ).",
                " (___.__)__)",
                "           ",
            ],
            'rain' => [
                "     .--.  ",
                "  .-(    ).",
                " (___.__)__)",
                "  ' ' ' '  ",
                " ' ' ' '   ",
            ],
            'snow' => [
                "     .--.  ",
                "  .-(    ).",
                " (___.__)__)",
                "   *  *  * ",
                "  *  *  *  ",
            ],
            'storm' => [
                "     .--.  ",
                "  .-(    ).",
                " (___.__)__)",
                "    / /    ",
                "   /_/     ",
            ],
        ];

        $icon = $icons[strtolower($condition)] ?? $icons['cloudy'];

        $info = [
            sprintf("Location:    %s", $location),
            sprintf("Condition:   %s", ucfirst($condition)),
            sprintf("Temperature: %.1f°C", $temperature),
            sprintf("Feels Like:  %.1f°C", $feelsLike),
            sprintf("Wind:        %.1f m/s", $wind),
            sprintf("Humidity:    %d%%", $humidity),
            sprintf("Rain:        %.1f mm", $rain),
            sprintf("Pressure:    %d hPa", $pressure),
        ];

        $height = max(count($icon), count($info));

        for ($i = 0; $i < $height; $i++) {
            $left = $icon[$i] ?? "";
            $right = $info[$i] ?? "";

            $this->printer->text(
                str_pad($left, 16) .
                $right .
                "\n"
            );
        }

        $this->printer->feed();
    }

    public function testWeatherArt(): void
    {
        $examples = [
            'Sunny' => [
                '      \  |  /',
                '    -- .---. --',
                '      (     )',
                '    -- \'---\' --',
                '      /  |  \ ',
            ],

            'Cloudy' => [
                '       .---.',
                '    .-(     ).',
                '   (___,___,___)',
            ],

            'Light Rain' => [
                '       .---.',
                '    .-(     ).',
                '   (___,___,___)',
                '      \'  \'  \'',
                '    \'  \'  \'',
            ],

            'Heavy Rain' => [
                '       .---.',
                '    .-(     ).',
                '   (___,___,___)',
                '     |  |  |  |',
                '     |  |  |  |',
            ],

            'Thunderstorm' => [
                '       .---.',
                '    .-(     ).',
                '   (___,___,___)',
                '       / _/',
                '      / _/',
                '        /',
            ],

            'Snow' => [
                '       .---.',
                '    .-(     ).',
                '   (___,___,___)',
                '      *  *  *',
                '    *  *  *  *',
            ],

            'Fog' => [
                '    -  -  -  -',
                '      -  -  -',
                '    -  -  -  -',
                '      -  -  -',
            ],

            'Windy' => [
                '    ~~~~~~~~~>',
                '      ~~~~~~~>',
                '    ~~~~~~~~~>',
            ],
        ];

        $this->printBoxTitle('WEATHER ICON TEST');

        foreach ($examples as $name => $icon) {
            $this->printWeatherIconWithLabel($name, $icon);
            $this->feed();
        }
    }

    /**
     * Prints an ASCII weather icon with its label on the right.
     *
     * Designed to fit a 32-character receipt printer line.
     *
     * @param string[] $icon
     */
    private function printWeatherIconWithLabel(
        string $name,
        array $icon
    ): void {
        $labelColumn = 20;

        // Put the label roughly in the vertical centre of the icon.
        $labelRow = (int) floor((count($icon) - 1) / 2);

        foreach ($icon as $row => $line) {
            $this->printer->setEmphasis(false);

            // Print icon and move the label to a consistent column.
            $this->printer->text(
                str_pad($line, $labelColumn)
            );

            if ($row === $labelRow) {
                $this->printer->setEmphasis(true);
                $this->printer->text($name);
                $this->printer->setEmphasis(false);
            }

            $this->printer->text("\n");
        }
    }

    /**
     * Prints every style so you can see what your printer supports.
     */
    public function test(): void
    {
        $this->printBoxTitle("STYLE TEST");

        $this->printText("Normal Text");
        $this->printBold("Bold");
        $this->printUnderline("Underline");
        $this->printSmall("Small Font");
        $this->printDoubleWidth("Double Width");
        $this->printDoubleHeight("Double Height");
        $this->printBig("Big Text", Printer::JUSTIFY_LEFT);
        $this->printCentered("Centered");
        $this->printRight("Right Aligned");
        $this->printReverse("Reverse Colours");
        $this->printUpsideDown("Upside Down");

        $this->feed();

        $this->printBanner("SALE!");

        $this->feed();

        $this->printWarning("WARNING MESSAGE");

        $this->feed();

        $this->hr();
        $this->dashedRule();
        $this->hr("=");

        $this->feed();

        $this->printLabelValue("Order", "#12345");
        $this->printLabelValue("Customer", "John Smith");
        $this->printLabelValue("Table", "7");

        $this->feed();

        $this->printTwoColumns("Burger", "£8.95");
        $this->printTwoColumns("Fries", "£2.50");
        $this->printTwoColumns("Drink", "£1.99");

        $this->hr();

        $this->printTwoColumns("TOTAL", "£13.44");

        $this->feed(2);

        $this->printBarcode("ABC123456");

        $this->feed();

        $this->printQrCode("https://example.com");

        $this->feed();

        $this->hr();

        $this->feed();

        $this->printWeather(
            location: "Grimsby, UK",
            condition: "rain",
            temperature: 19.97,
            feelsLike: 20.48,
            wind: 2.06,
            humidity: 94,
            pressure: 1018,
            rain: 0
        );

        $this->feed();

        $this->testWeatherArt();

        $this->feed(4);

        $this->close();
    }
}
