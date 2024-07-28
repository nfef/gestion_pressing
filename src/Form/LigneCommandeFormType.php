<?php

// src/Form/LigneCommandeFormType.php

namespace App\Form;

use App\Entity\LigneCommande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Article;

class LigneCommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantite')
            ->add('Article', EntityType::class, [
                'class' => Article::class,
                'choice_label' => 'nom', // Champ à afficher dans la liste déroulante
                'multiple' => false, // Permet de choisir un seul élément
                'expanded' => false, // Affiche la liste déroulante en format standard
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LigneCommande::class,
        ]);
    }
}