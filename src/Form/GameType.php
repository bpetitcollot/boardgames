<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('participants', IntegerType::class, [
                'label' => 'Nombre de joueurs',
                'attr' => [
                    'min' => 2,
                    'max' => 5,
                ],
                'mapped' => false,
            ])
        ;
        if (count($options['extensions']) > 0)
        {
            $builder->add('extensions', ChoiceType::class, [
                'label' => 'Extensions',
                'choices' => array_flip($options['extensions']),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
            'extensions' => array(),
        ]);
    }

}
