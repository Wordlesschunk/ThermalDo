<?php

declare(strict_types=1);

namespace App\Controller;

use App\Components\Schedule;
use App\Components\TodoList;
use App\PrintStylesNew;
use App\Components\Weather;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PrintController extends AbstractController
{
    #[Route('/print', name: 'app_print')]
    public function index(HttpClientInterface $httpClient): Response
    {
        $now = new \DateTimeImmutable();

        $connector = new NetworkPrintConnector("192.168.1.100", 9100);
        $printer = new Printer($connector);
        $printerStyles = new PrintStylesNew($printer);

// --------------------------------------------------------------------------------------
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printerStyles->printBig($now->format('l dS Y'));
        $printerStyles->printText(sprintf('Day %s / 365', $now->format('z')));
        $printerStyles->resetJustification();
// --------------------------------------------------------------------------------------

        $printer->feed(2);

        $printerStyles->hr();
        $printer->feed();

        new Weather($httpClient, $printer)->print('Grimsby');

        $printer->feed(1);

        new TodoList($printer)->print([
            'Finish the report',
            'Call the client',
            'Prepare presentation slides',
            'Schedule team meeting',
            'Review project plan',
        ]);

        $printer->feed(1);

        new Schedule($printer)->print([
            '9:00 AM - Team Standup',
            '10:30 AM - Client Call',
            '1:00 PM - Lunch Break',
            '2:00 PM - Project Review Meeting',
            '4:00 PM - Wrap Up and Plan for Tomorrow',
        ]);

//        new Schedule($printer)->print([]);


        $printer->feed(2);

        $printer->cut();
        $printer->close();

        return $this->render('print/index.html.twig', [
            'controller_name' => 'PrintController',
        ]);
    }
}
