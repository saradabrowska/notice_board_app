<?php
/**
 * Offer type.
 */
namespace Form;

use Repository\OfferRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class OfferType.
 *
 * @package Form
 */
class FindOfferType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder  ->add(
            'offer_types_id',
            ChoiceType::class,
            [
                'label' => 'label.offer_type',
                'required' => true,
                'choices' => $this->prepareTypesForChoices($options['type_repository'], 'offer_types'),
            ]
        )
            ->add(
                'property_types_id',
                ChoiceType::class,
                [
                    'label' => 'label.property_type',
                    'required' => true,
                    'choices' => $this->prepareTypesForChoices($options['type_repository'], 'property_types'),
                ]
            )
            ->add(
                'cities_id',
                TextType::class,
                [
                    'label' => 'label.city',
                    'required' => true,
                    'attr' => [
                        'max_length' => 128,
                    ]
                ]
            );




    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'bookmark-default',
                'type_repository' => null,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'offer_type';
    }

    protected function prepareTypesForChoices($typeRepository, $table)
    {
        $propertyTypes = $typeRepository->findAll($table);
        $choices = [];

        foreach ($propertyTypes as $propertyType) {
            $choices[$propertyType['name']] = $propertyType['id'];
        }
        return $choices;
    }

}