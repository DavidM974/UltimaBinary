<?php

namespace UB\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;

class ParameterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('defaultRate',            PercentType::class, array('label' => 'Taux par defaut'))
                ->add('martingaleSize',         IntegerType::class, array('label' => 'Nombre de palier Martingale'))
                ->add('jokerSize',              IntegerType::class, array('label' => 'Longeur Joker'))
                ->add('startTime',              TimeType::class, array('label' => 'Heure de début des trades'))
                ->add('endTime',                TimeType::class, array('label' => 'Heure de fin des trades'))
                ->add('dayActive',              IntegerType::class, array('label' => 'Nombre de jour actif(1-7)'))
                ->add('mode',                   CheckboxType::class, array('label' => 'Programme On/Off'))
                ->add('isActiveM1',             CheckboxType::class, array('label' => 'M1 On/Off'))
                ->add('isActiveM5',             CheckboxType::class, array('label' => 'M5 On/Off'))
                ->add('state',                  CheckboxType::class, array('label' => 'Automatique/Manuel'))
                ->add('securitySequence',       IntegerType::class, array('label' => 'Sécurité bad sequence max', 'required' => false))
                ->add('maxParallelSequence',    IntegerType::class, array('label' => 'Nombre de sequence parralle max','required' => false))
                ->add('probaJokerOn',           CheckboxType::class, array('label' => 'Joker On/Off','required' => false))
                ->add('balance',                NumberType::class, array('label' => 'Balance compte'))
                ->add('save',                   SubmitType::class);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'UB\CoreBundle\Entity\Parameter'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ub_corebundle_parameter';
    }


}
