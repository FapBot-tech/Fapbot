<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\PageContent;
use App\Form\CustomTypes\WysiwygAreaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PageContentType extends AbstractType {
    public const IDENTIFIERS = [
        'Guide index' => 'guide_index',
        'Guide mutes' => 'guide_mutes',
        'Guide warnings' => 'guide_warnings',
        'Guide deactivations' => 'guide_deactivations',
        'Guide chat commands' => 'guide_commands',
        'Guide announcements' => 'guide_announcements',
        'Guide FAQ' => 'guide_faq',
        'Report how to' => 'report_how_to',
        'How to mod?' => 'how_to_mod',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identifier', ChoiceType::class, [
                'choices' => array_diff(self::IDENTIFIERS, $options['added_identifiers']),
            ])
            ->add('content', WysiwygAreaType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dataClass' => PageContent::class,
            'added_identifiers' => []
        ]);
    }
}