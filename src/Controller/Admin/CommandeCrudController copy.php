<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Form\LigneCommandeFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Dotenv\Dotenv;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class CommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        // Appel de la méthode d'impression après la création de la commande
       // $this->imprimerRecu($entityInstance);

        // Redirection vers la page de confirmation
        $this->addFlash('success', 'La commande a été créée avec succès.');
        $this->redirectToRoute('easyadmin', [
            'entity' => 'Commande',
            'action' => 'list',
        ]);
    }

    private function imprimerRecu(Commande $commande): void
    {
        if (!class_exists(Dotenv::class)) {
            print('Error: You probably forgot to `composer req [--dev] symfony/dotenv.` 😉') . PHP_EOL;
            exit(1);
        }
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../../../.env');
        // $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../../.env'); // Chemin relatif pour accéder à la racine du projet
        // $dotenv->load();

        // Récupération des données de la commande et des valeurs du .env
        $poids = $commande->getPoids();
        $client = $commande->getClient()->getNom();
        $prixKilo = getenv('PRIX_KILO');
        $total = $poids * $prixKilo;
        $delai = getenv('DELAI');
        $penalty = getenv('PENALTY');
        $entreprise = getenv('NAME');
        $adresse = getenv('ADRESSE');

        // Connexion à l'imprimante
        // $connector = new WindowsPrintConnector("usb://EPSON TM-T88III Receipt");
        // $printer = new Printer($connector);
        // Connexion à l'imprimante EPSON TM-T88III Receipt
        $printerName = 'ESDPRT001';
        //$printerName = '\\DESKTOP-AL7BUR0\ESDPRT001';
        $connector = new WindowsPrintConnector($printerName);
        $printer = new Printer($connector);

        // Construction du contenu du reçu
        $receiptContent = "
            $entreprise
            $adresse

           
            -----------------------------------------
            | Kg | Infos client | Prix/kg | Total |
            | $poids | $client | $prixKilo | $total |
            -----------------------------------------

            Total à payer: $total

            
            $delai
            $penalty

            Merci pour votre confiance.
        ";

        // Envoi de l'instruction d'impression
        $printer->text($receiptContent);
        $printer->cut();
        $printer->close();

    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            DateField::new('dateCommande', 'Date création de la commande')
                ->setFormat('dd/MM/yyyy') // Format de la date
                ->addCssClass('datepicker'), // Ajout de la classe pour activer le calendrier,
            AssociationField::new('client')
                ->setRequired(true), // Marquer le champ comme obligatoire si nécessaire
            NumberField::new('poids', 'Poids en Kg') // Ajout du champ pour le poids
                ->setFormTypeOptions(['scale' => 2]) // Précision à 2 décimales
                ->setRequired(true), // Champ obligatoire
            DateField::new('dateLivraison', 'Date de Livraison')
                ->setFormat('dd/MM/yyyy') // Format de la date
                ->addCssClass('datepicker'), // Ajout de la classe pour activer le calendrier,
            TextField::new('statut', 'Statut')
                ->setFormType(ChoiceType::class)
                ->setFormTypeOptions([
                    'choices' => [
                        'En cours' => 'en_cours',
                        'Terminée' => 'terminee',
                        'Annulée' => 'annulee',
                        // Ajoutez d'autres options si nécessaire
                    ],
                    'placeholder' => 'Sélectionner un statut',
                ]),
            CollectionField::new('ligneCommandes')
                ->setEntryType(LigneCommandeFormType::class)
                ->onlyOnForms(),
        ];
    }

    
}
