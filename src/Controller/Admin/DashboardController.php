<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\LigneCommande;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestion Pressing');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        yield MenuItem::section('Gestion du Pressing');
        yield MenuItem::linkToCrud('Clients', 'fa fa-user', Client::class);
        yield MenuItem::linkToCrud('Articles', 'fa fa-tags', Article::class);
        yield MenuItem::linkToCrud('Commandes', 'fa fa-book', Commande::class);
        yield MenuItem::section('Gestion des Commandes');
        yield MenuItem::linkToCrud('Ligne des commandes', 'fa fa-book', LigneCommande::class);
    }
}
