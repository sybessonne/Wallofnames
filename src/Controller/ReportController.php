<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class ReportController extends AbstractController
{
    /**
     * @Route("/csp/report", name="cspReport")
     */
    public function cspReport()
    {
        return new JsonResponse(array(), 200);
    }

    /**
     * @Route("/xss/report", name="xssReport")
     */
    public function xssReport()
    {
        return new JsonResponse(array(), 200);
    }
}
