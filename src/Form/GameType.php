<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, ['label' => 'Titre']);
        if ($options['minPlayers'] !== null && $options['maxPlayers'] !== null){
            $builder->add('players', IntegerType::class, [
                'label' => 'Nombre de joueurs',
                'attr' => [
                    'min' => $options['minPlayers'],
                    'max' => $options['maxPlayers'],
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(['minPlayers' => null]);
        $resolver->setDefaults(['maxPlayers' => null]);
    }
}