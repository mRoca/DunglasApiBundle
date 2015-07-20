<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Mapping;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Util\ReflectionTrait;
use PropertyInfo\PropertyInfoInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadataFactory implements AttributeMetadataFactoryInterface
{
    use ReflectionTrait;

    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;

    public function __construct(PropertyInfoInterface $propertyInfo, ResourceCollectionInterface $resourceCollection)
    {
        $this->propertyInfo = $propertyInfo;
        $this->resourceCollection = $resourceCollection;
    }

    public function getAttributeMetadataFor(
        ClassMetadataInterface $classMetadata,
        $attributeName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if ($classMetadata->hasAttribute($attributeName)) {
            return clone $classMetadata->getAttribute($attributeName);
        }

        $attributeMetadata = new AttributeMetadata($attributeName);

        $reflectionProperty = $this->getReflectionProperty($classMetadata->getReflectionClass(), $attributeName);

        if (!$reflectionProperty) {
            return $attributeMetadata;
        }

        $types = $this->propertyInfo->getTypes($reflectionProperty);
        if (null !== $types) {
            $attributeMetadata->setTypes($types);
        }

        if (!isset($types[0])) {
            return $attributeMetadata;
        }

        $class = $types[0]->getClass();
        $link = $this->resourceCollection->getResourceForEntity($class) ||
            (
                $types[0]->isCollection() &&
                $types[0]->getCollectionType() &&
                ($class = $types[0]->getCollectionType()->getClass()) &&
                $this->resourceCollection->getResourceForEntity($class)
            );

        $attributeMetadata = $attributeMetadata
            ->withLink($link)
            ->withLinkClass($class)
        ;

        if (!$link) {
            return $attributeMetadata;
        }

        if (null === $normalizationGroups) {
            $attributeMetadata->setNormalizationLink(true);
        }

        if (null === $denormalizationGroups) {
            $attributeMetadata->setDenormalizationLink(true);
        }

        return $attributeMetadata;
    }
}
