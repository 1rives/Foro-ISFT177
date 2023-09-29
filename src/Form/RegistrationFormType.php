<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
            ])
            ->add('dni', IntegerType::class, [
                'required' => true,
                'label' => 'DNI',
            ])
            ->add('password', RepeatedType::class, [
                'required' => true,
                'type' => PasswordType::class,
                'invalid_message' => 'Las contraseñas no coinciden.',
                'options' => ['attr' => ['class' => 'password-field']],
                'first_options'  =>
                    ['label' => 'Contraseña'],
                'second_options' =>
                    ['label' => 'Repite la contraseña']
            ])
            ->add('photo', FileType::class, [
                'required' => false,
                'label' => 'Avatar',
                'constraints' => [
                    new File([
                        'maxSize' => '1M',
                        'filenameMaxLength' => 150
                    ]),
                    new Image([
                        'minWidth' => 100,
                        'maxWidth' => 1024,
                        'maxHeight' => 1024,
                        'minHeight' => 100,
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Descripción'
            ])
            ->add('submit', SubmitType::class)
        ;
    }

//    public function buildForm(FormBuilderInterface $builder, array $options): void
//    {
//        $builder
//            ->add('email')
//            ->add('agreeTerms', CheckboxType::class, [
//                'mapped' => false,
//                'constraints' => [
//                    new IsTrue([
//                        'message' => 'You should agree to our terms.',
//                    ]),
//                ],
//            ])
//            ->add('plainPassword', PasswordType::class, [
//                // instead of being set onto the object directly,
//                // this is read and encoded in the controller
//                'mapped' => false,
//                'attr' => ['autocomplete' => 'new-password'],
//                'constraints' => [
//                    new NotBlank([
//                        'message' => 'Please enter a password',
//                    ]),
//                    new Length([
//                        'min' => 6,
//                        'minMessage' => 'Your password should be at least {{ limit }} characters',
//                        // max length allowed by Symfony for security reasons
//                        'max' => 4096,
//                    ]),
//                ],
//            ])
//        ;
//    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
