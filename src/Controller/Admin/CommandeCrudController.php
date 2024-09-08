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
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Dotenv\Dotenv;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Dompdf\Dompdf;
use Dompdf\Options;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
        //$this->imprimerRecu($entityInstance);

        // Génération du fichier PDF du reçu
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($pdfOptions);
        $receiptContent = $this->generateReceiptContent($entityInstance);
        $dompdf->loadHtml($receiptContent);
        $dompdf->render();
        
          // Chemin et nom du fichier PDF
        $pdfDirectory = __DIR__ . '/../../documents/';
        if (!file_exists($pdfDirectory)) {
            mkdir($pdfDirectory, 0777, true);
        }
        $pdfFilePath = $pdfDirectory . 'receipt_' . uniqid() . '.pdf';
        file_put_contents($pdfFilePath, $dompdf->output());

        // Affichage de l'alerte pour imprimer le reçu
        $response = $this->render('print_receipt.html.twig', [
            'pdfFilePath' => $pdfFilePath,
        ]);
        $entityManager->flush();
    }

    public function persistEntity2(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        // Appel de la méthode d'impression après la création de la commande
        $this->imprimerRecu($entityInstance);

        // Redirection vers la page de confirmation
        $this->addFlash('success', 'La commande a été créée avec succès.');
        $this->redirectToRoute('easyadmin', [
            'entity' => 'Commande',
            'action' => 'list',
        ]);
    }

    

    private function generateReceiptContent(Commande $commande): string
    {
        // Construction du contenu du reçu
        $entreprise = getenv('NAME');
        $adresse = getenv('ADRESSE');
        $poids = $commande->getPoids();
        $client = $commande->getClient()->getNom();
        $prixKilo = getenv('PRIX_KILO');
        $total = $poids * $prixKilo;
        $delai = getenv('DELAI');
        $penalty = getenv('PENALTY');

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

        return $receiptContent;
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

    public function configureActions(Actions $actions): Actions
    {
        // Créer une action personnalisée "imprimer"
        $imprimerAction = Action::new('imprimer', 'Imprimer', 'fa fa-print')
            ->linkToRoute('imprimer_commande', function (Commande $entity) {
                return [
                    'id' => $entity->getId(),
                ];
            })
            //->setHtmlAttributes(['target' => '_blank']); // Ouvrir dans un nouvel onglet
            ->addCssClass('btn btn-primary')
            ->setHtmlAttributes([
                'onclick' => 'openPrintWindow(this.href); return false;'
            ]);

        // Créer une action personnalisée "ouvrir_popup"
        $ouvrirPopupAction = Action::new('ouvrir_popup', 'Ouvrir Popup', 'fa fa-window-restore')
            ->linkToCrudAction('ouvrirPopup')
            ->displayIf(static function ($entity) {
        // Vous pouvez définir ici les conditions pour afficher l'action
            return true;
        }); 


        // Ajouter l'action "imprimer" après Edit et Delete
        return $actions
            ->add(Crud::PAGE_INDEX, $imprimerAction)
            ->add(Crud::PAGE_INDEX, $ouvrirPopupAction);
            //->setPermission('imprimer', 'ROLE_ADMIN'); // Ajoute des permissions si nécessaire
    }

        public function ouvrirPopup(AdminContext $context): Response
        {
            // Code pour ouvrir une nouvelle fenêtre en tant que popup
            $htmlContent = '<h1>Contenu du Popup</h1>';
            
            // Générer une réponse avec le contenu HTML
            $response = new Response($htmlContent);
            $response->headers->set('Content-Type', 'text/html');

            // Ajouter un script JavaScript pour ouvrir la fenêtre en tant que popup
            $response->setContent('<script>window.open("", "Popup", "width=400,height=400");</script>'.$htmlContent);

            return $response;
        }


    
    public function imprimerCommande($id): Response
    {
        // Récupérer la commande à imprimer en fonction de l'identifiant $id
        $commande = $this->getDoctrine()->getRepository(Commande::class)->find($id);
       
        // Générer le contenu à imprimer (par exemple, en utilisant un template Twig)
        $contenuAImprimer = $this->renderView('impression.html.twig', [
            'commande' => $commande,
            'entreprise' => getenv('NAME'),
            'adresse' => getenv('ADRESSE'),
            'poids' => $commande->getPoids(),
            'client' => $commande->getClient()->getNom(),
            'prixKilo' => getenv('PRIX_KILO'),
            //'total' => $poids * $prixKilo,
            'delai' => getenv('DELAI'),
            'penalty' => getenv('PENALTY'),
            'whatsapp' => getenv('WHATSAPP'),
            'prix_penalty' => getenv('PRIX_PENALTY'),
        ]);

        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        // Créer une instance de Dompdf
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($contenuAImprimer);

        

        // Générer le PDF
        $dompdf->render();

        // Renvoyer le PDF en tant que réponse
        $response = new Response($dompdf->output());

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE, 'commande.pdf'
        );

        $response->headers->set('Content-Type', 'application/pdf');

        $response->headers->set('Content-Disposition', $disposition);

        // Vous pouvez personnaliser davantage la réponse PDF si nécessaire

         // Ajouter un attribut pour ouvrir le PDF dans une nouvelle fenêtre
        $response->headers->set('target', '_blank');

        return $response;
    }
    
}
