<?php

namespace App\Controller;

use App\PrintStylesNew;
use App\Services\Weather;
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
        $printer = new PrintStylesNew();
        $weather = new Weather($httpClient, $printer->printer);


        $printer->printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->printBig($now->format('l dS Y'));
            $printer->printText(sprintf('Day %s / 365', $now->format('z')));
        $printer->resetJustification();

        $printer->feed(4);

        $printer->hr();
        $printer->feed();

        $weather->print('Grimsby');


        $weather->printer->cut();
        $weather->printer->close();


        return $this->render('print/index.html.twig', [
            'controller_name' => 'PrintController',
        ]);
    }
}
