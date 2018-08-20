<?php

namespace App\Form;

use App\Entity\Action;
use App\Entity\Player;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', ChoiceType::class, [
                'label' => 'Action',
                'choices' => array_flip($options['action_choices']),
            ])
            ->add('params', TextType::class, [
                'label' => 'Paramètres',
                'required' => false,
            ])
        ;
        if (!$options['required']) {
            $builder->add('declined', CheckboxType::class, [
                'label' => 'Renoncer',
                'required' => false,
            ]);
        }
        $builder->add('player', EntityType::class, [
                'label' => 'Joueur',
                'class' => Player::class,
                'disabled' => true,
            ])
            ->add('choices', TextareaType::class, [
                'label' => 'Choix',
                'disabled' => true,
            ])
            ->add('extraDatas', TextareaType::class, [
                'label' => 'extra datas',
                'disabled' => true,
            ])
        ;

        $builder->get('params')
            ->addModelTransformer(new CallbackTransformer(
                function ($paramsAsArray) {
                return json_encode($paramsAsArray, true);
            }, function ($paramsAsString) {
                return json_decode($paramsAsString, true);
            }
            ))
        ;
        $builder->get('choices')
            ->addModelTransformer(new CallbackTransformer(
                function ($paramsAsArray) {
                return json_encode($paramsAsArray, true);
            }, function ($paramsAsString) {
                return json_decode($paramsAsString, true);
            }
            ))
        ;
        $builder->get('extraDatas')
            ->addModelTransformer(new CallbackTransformer(
                function ($paramsAsArray) {
                return json_encode($paramsAsArray, true);
            }, function ($paramsAsString) {
                return json_decode($paramsAsString, true);
            }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'action_choices' => array(),
            'required' => true,
        ]);
    }

}
