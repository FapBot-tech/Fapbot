<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Repository\ChannelRepository;
use App\Form\CustomTypes\SearchableChannelType;
use App\Form\CustomTypes\UsernameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reporterName', UsernameType::class, [
                'attr' => [
                    'placeholder' => 'Your name'
                ]
            ])
            ->add('reportedName', UsernameType::class, [
                'attr' => [
                    'placeholder' => 'User you are reporting'
                ]
            ])
            ->add('reasonSelect', ChoiceType::class, [
                'choices' => [
                    'Content posted looks too young' => 'UA content',
                    'Spam / User posting too many messages too quickly' => 'Spam',
                    'Incorrect channel' => 'Incorrect channel',
                    'Racism / Hate speech / Discrimination' => 'Racism / Discrimination',
                    'Other' => 'Other'
                ],
                'placeholder' => 'Select a reason'
            ])
            ->add('reason', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Provide some additional context to your report if needed. If you\'ve selected "Other" as your reason please describe the reason here'
                ]
            ])
            ->add('reportedMessageLink', UrlType::class, [
                'attr' => [
                    'placeholder' => 'Link to the message you are reporting',
                    'help' => 'Hover over the message, then click the three dots on the right of your page. From the dropdown select "Copy link to message" and paste it here.'
                ],
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ReportDto::class,
        ]);
    }
}