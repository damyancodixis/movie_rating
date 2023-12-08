<?php

namespace App\Form;

use App\Entity\Review;
use App\Form\Type\StarRatingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewFormType extends AbstractType
{
    const LABEL_CLASS = 'ml-2 text-sm text-white font-medium leading-6';
    const INPUT_CLASS = 'bg-gray-800 block w-full rounded-md border-0 py-1.5 text-gray-300 shadow-sm ring-1 ring-inset  placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userReview = $builder->getData();

        $builder
            ->add('rating', StarRatingType::class, [
                'label' => false,
            ])
            ->add('isReview', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'false_values' => ['false', '0'],
                'label' => 'I want to submit a review',
                'label_attr' => ['class' => self::LABEL_CLASS],
                'attr' => ['class' => "w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-2 focus:ring-indigo-600"],
                'data' => $userReview->getTitle() ? true : false,
            ]);

        $formModifier = function (FormInterface $form) use ($userReview): void {
            $isReview = $form->get('isReview')->getData();

            if ($isReview) {
                $form
                    ->add('title', TextType::class, [
                        'label_attr' => ['class' => self::LABEL_CLASS],
                        'attr' => ['class' => self::INPUT_CLASS],
                        'empty_data' => $userReview->getTitle(),
                    ])
                    ->add('content', TextareaType::class, [
                        'label_attr' => ['class' => self::LABEL_CLASS],
                        'attr' => [
                            'class' => self::INPUT_CLASS,
                            'rows' => 4
                        ],
                        'empty_data' => $userReview->getContent(),
                    ]);
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                $formModifier($event->getForm());
            }
        );

        $builder->get('isReview')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier): void {
                $formModifier($event->getForm()->getParent());
            }
        );

        $builder->setAction($options['action']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'validation_groups' => false,
        ]);
    }
}
