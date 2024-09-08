<?php

// src/Form/CommandeFormType.php

namespace App\Form;

use App\Entity\Commande;
use App\Form\LigneCommandeFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateCommande')
            ->add('dateLivraison')
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En cours' => 'en_cours',
                    'Terminée' => 'terminee',
                    'Annulée' => 'annulee',
                    // Ajoutez d'autres options si nécessaire
                ],
                'placeholder' => 'Sélectionner un statut',
            ])
            ->add('ligneCommandes', CollectionType::class, [
                'entry_type' => LigneCommandeFormType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'choice_value' => 'id', // ou une autre propriété unique
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}