<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StarRatingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Terrible' => 1,
                'Bad' => 2,
                'Okay' => 3,
                'Good' => 4,
                'Great' => 5,
            ],
            'expanded' => true,
            'multiple' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
