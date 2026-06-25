<?php

namespace App\Controller;

use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DebugController extends AbstractController
{
    private string $ip = "192.168.1.100";
    private int $port = 9100;

    /* =========================
       HOME PAGE (BUTTONS)
       ========================= */

    #[Route('/debug', name: 'app_debug_home')]
    public function index(): Response
    {
        return $this->render('debug/index.html.twig');
    }

    /* =========================
       ROUTES
       ========================= */

    #[Route('/debug/cyberpunk', name: 'print_cyberpunk')]
    public function cyberpunk(): Response
    {
        $this->run(fn($p) => $this->templateCyberpunk($p));
        return new Response("Cyberpunk printed");
    }

    #[Route('/debug/fastfood', name: 'print_fastfood')]
    public function fastfood(): Response
    {
        $this->run(fn($p) => $this->templateFastFood($p));
        return new Response("Fastfood printed");
    }

    #[Route('/debug/horror', name: 'print_horror')]
    public function horror(): Response
    {
        $this->run(fn($p) => $this->templateHorror($p));
        return new Response("Horror printed");
    }

    #[Route('/debug/retail', name: 'print_retail')]
    public function retail(): Response
    {
        $this->run(fn($p) => $this->templateRetail($p));
        return new Response("Retail printed");
    }

    #[Route('/debug/minimal', name: 'print_minimal')]
    public function minimal(): Response
    {
        $this->run(fn($p) => $this->templateMinimal($p));
        return new Response("Minimal printed");
    }

    /* =========================
       PRINTER CORE
       ========================= */

    private function run(callable $callback): void
    {
        $connector = new NetworkPrintConnector($this->ip, $this->port);
        $printer = new Printer($connector);

        $this->reset($printer);

        $callback($printer);

        $printer->feed(2);
        $printer->cut();
        $printer->close();
    }

    private function reset(Printer $printer): void
    {
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(false);
        $printer->setUnderline(false);
        $printer->setReverseColors(false);
        $printer->selectPrintMode();
    }

    /* =========================
       BLOCKS (REUSABLE LAYOUT)
       ========================= */

    private function blockHeader(Printer $printer, string $title): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text($title . "\n");
        $printer->setEmphasis(false);
        $printer->feed();
    }

    private function blockItem(Printer $printer, string $name, string $price): void
    {
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_pad($name, 20) . $price . "\n");
    }

    private function blockDivider(Printer $printer): void
    {
        $printer->text(str_repeat("-", 32) . "\n");
    }

    private function blockTotal(Printer $printer, string $total): void
    {
        $printer->feed();
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setEmphasis(true);
        $printer->text("TOTAL: £" . $total . "\n");
        $printer->setEmphasis(false);
    }

    private function asciiCat(Printer $printer): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("
  /\\_/\\
 ( o.o )
  > ^ <   MEOW RECEIPT
");
    }

    private function asciiRobot(Printer $printer): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("
 [=====]
 | o o |
 |  ^  |
 | '-' |
 ROBOT POS
");
    }

    private function asciiCyber(Printer $printer): void
    {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("
   /\\
  /__\\  ⚡
  \\  /
   \\/   NEON CORE
");
    }

    /* =========================
       TEMPLATES (UPDATED)
       ========================= */

    private function templateCyberpunk(Printer $printer): void
    {
        $this->printLogo($printer);

        $this->blockHeader($printer, "NEON POS SYSTEM");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("SESSION: #" . rand(1000,9999) . "\n");

        $this->blockDivider($printer);

        $this->blockItem($printer, "Cyber Energy Drink", "£" . rand(2, 8));

        $printer->feed();

        $printer->setReverseColors(true);
        $printer->text(" PAYMENT ACCEPTED IN FUTURE CREDITS \n");
        $printer->setReverseColors(false);

        $this->asciiCyber($printer);
    }

    private function templateFastFood(Printer $printer): void
    {
        $this->blockHeader($printer, "🍔 QUICK FOOD ORDER");

        $items = ["Burger", "Fries", "Cola", "Nuggets", "Shake"];
        $total = 0;

        for ($i = 0; $i < 5; $i++) {
            $item = $items[array_rand($items)];
            $price = rand(2, 7);
            $total += $price;

            $this->blockItem($printer, $item, "£$price");
        }

        $this->blockDivider($printer);
        $this->blockTotal($printer, (string)$total);

        $this->asciiCat($printer);
    }

    private function printLogo(Printer $printer): void
    {
        $imgPath = __DIR__ . '/../../public/logo.png';

        $logo = EscposImage::load($imgPath, false);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->graphics($logo);
        $printer->feed();
    }

    private function templateHorror(Printer $printer): void
    {
        $this->blockHeader($printer, "DO NOT TRUST THIS");

        $lines = [
            "it is still printing",
            "why are you here",
            "SYSTEM CORRUPTED",
            "000000000000",
            "ERROR ERROR ERROR"
        ];

        for ($i = 0; $i < 12; $i++) {
            $printer->text($lines[array_rand($lines)] . "\n");
        }

        $printer->setReverseColors(true);
        $printer->text("VOID TRANSACTION DETECTED\n");
        $printer->setReverseColors(false);
    }

    private function templateRetail(Printer $printer): void
    {
        $this->blockHeader($printer, "PREMIUM STORE");

        $items = [
            "T-Shirt" => 20,
            "Jeans" => 45,
            "Jacket" => 80,
            "Hat" => 15
        ];

        foreach ($items as $name => $price) {
            $this->blockItem($printer, $name, "£$price");
        }

        $this->blockDivider($printer);
        $this->blockTotal($printer, (string)array_sum($items));

        $this->asciiRobot($printer);
    }

    private function templateMinimal(Printer $printer): void
    {
        $this->blockHeader($printer, "Store XYZ");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(date('Y-m-d H:i') . "\n");

        $this->blockDivider($printer);

        $this->blockItem($printer, "Item A", "£10");
        $this->blockItem($printer, "Item B", "£5");

        $this->blockDivider($printer);
        $this->blockTotal($printer, "15");
    }
}
