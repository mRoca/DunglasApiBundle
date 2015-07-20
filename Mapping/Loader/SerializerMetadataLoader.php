<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\AttributeMetadataFactoryInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class SerializerMetadataLoader implements LoaderInterface
{
    /**
     * @var AttributeMetadataFactoryInterface
     */
    private $attributeMetadataFactory;
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $serializerClassMetadataFactory;

    public function __construct(AttributeMetadataFactoryInterface $attributeMetadataFactory, ClassMetadataFactoryInterface $serializerClassMetadataFactory)
    {
        $this->attributeMetadataFactory = $attributeMetadataFactory;
        $this->serializerClassMetadataFactory = $serializerClassMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadataInterface $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        if (!null === $normalizationGroups && null === $denormalizationGroups) {
            return;
        }

        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($classMetadata->getName());

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttribute) {
            $attributeName = $serializerAttribute->getName();
            $groups = $serializerAttribute->getGroups();
            $attributeMetadata = $this->attributeMetadataFactory->getAttributeMetadataFor(
                $classMetadata, $attributeName, $normalizationGroups, $denormalizationGroups
            );

            // Create attribute
            if ($this->hasGroups($groups, $normalizationGroups)) {
                $attributeMetadata->setReadable(true);
                $classMetadata->addAttribute($attributeMetadata);
            }

            if ($this->hasGroups($groups, $denormalizationGroups)) {
                $attributeMetadata = $this->attributeMetadataFactory->getAttributeMetadataFor($classMetadata, $attributeName, $normalizationGroups, $denormalizationGroups);
                $attributeMetadata->setWritable(true);
                $classMetadata->addAttribute($attributeMetadata);
            }

            if (
                !$classMetadata->hasAttribute($attributeName) ||
                !$attributeMetadata->isLink() ||
                ($attributeMetadata->isNormalizationLink() && $attributeMetadata->isDenormalizationLink())
            ) {
                continue;
            }

            // Populate normalization and denormalization link
            if (!($relationSerializerMetadata = $this->serializerClassMetadataFactory->getMetadataFor($attributeMetadata->getLinkClass()))
            ) {
                $attributeMetadata->setNormalizationLink(true);
                $attributeMetadata->setDenormalizationLink(true);

                $classMetadata->addAttribute($attributeMetadata);
                continue;
            }

            foreach ($relationSerializerMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
                $serializerAttributeGroups = $serializerAttributeMetadata->getGroups();

                if (null !== $normalizationGroups && 1 <= count(array_intersect($normalizationGroups, $serializerAttributeGroups))) {
                    $normalizationLink = false;
                }

                if (null !== $denormalizationGroups && 1 <= count(array_intersect($denormalizationGroups, $serializerAttributeGroups))) {
                    $denormalizationLink = false;
                }

                if (isset($normalizationLink) && isset($denormalizationLink)) {
                    $classMetadata->addAttribute($attributeMetadata);
                    continue;
                }
            }

            if (!isset($normalizationLink)) {
                $attributeMetadata->setNormalizationLink(true);
            }

            if (!isset($denormalizationLink)) {
                $attributeMetadata->setDenormalizationLink(true);
            }

            $classMetadata->addAttribute($attributeMetadata);
        }
    }

    /**
     * Checks if an attribute has the passed groups.
     *
     * @param array      $groups
     * @param array|null $currentGroups
     *
     * @return bool
     */
    private function hasGroups(array $groups, array $currentGroups = null)
    {
        return null !== $currentGroups && 0 < count(array_intersect($groups, $currentGroups));
    }
}
