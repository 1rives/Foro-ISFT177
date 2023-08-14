<?php

namespace App\Form;

use App\Entity\Post;
use Doctrine\DBAL\Types\DateTimeType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => true])
            ->add('type', ChoiceType::class, [
                'choices' => Post::POST_TYPE_CHOICES,
                'required' => true
            ])
            ->add('description', TextareaType::class, ['required' => true])
            ->add('file', FileType::class, [
                'label' => 'photo',
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
