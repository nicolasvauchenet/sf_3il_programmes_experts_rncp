<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/administration', name: 'app_admin_home')]
    public function index(): Response
    {
        return $this->render('admin/default/index.html.twig');
    }
}
