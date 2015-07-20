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

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Dunglas\ApiBundle\Mapping\AttributeMetadata;
use Dunglas\ApiBundle\Mapping\AttributeMetadataInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface;
use Dunglas\ApiBundle\Util\ReflectionTrait;
use PropertyInfo\PropertyInfoInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

/**
 * Uses serialization groups or alternatively reflection to populate attributes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributesLoader implements LoaderInterface
{
    use ReflectionTrait;

    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var PropertyInfoInterface
     */
    private $propertyInfo;
    /**
     * @var ClassMetadataFactoryInterface|null
     */
    private $serializerClassMetadataFactory;

    public function __construct(
        ResourceCollectionInterface $resourceCollection,
        PropertyInfoInterface $propertyInfo,
        ClassMetadataFactoryInterface $serializerClassMetadataFactory = null
    ) {
        $this->resourceCollection = $resourceCollection;
        $this->propertyInfo = $propertyInfo;
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
        $this->populateFromSerializerMetadata($classMetadata, $normalizationGroups, $denormalizationGroups);
        $this->populateUsingReflection($classMetadata, $normalizationGroups, $denormalizationGroups);

        return true;
    }

    /**
     * Populates attributes from serializer metadata.
     *
     * @param ClassMetadataInterface $classMetadata
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     */
    private function populateFromSerializerMetadata(
        ClassMetadataInterface $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (!$this->serializerClassMetadataFactory || (null === $normalizationGroups && null === $denormalizationGroups)) {
            return;
        }

        $serializerClassMetadata = $this->serializerClassMetadataFactory->getMetadataFor($classMetadata->getName());

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttribute) {
            $groups = $serializerAttribute->getGroups();

            if (null !== $normalizationGroups && 0 < count(array_intersect($groups, $normalizationGroups))) {
                $attribute = $this->getOrCreateAttribute($classMetadata, $serializerAttribute->getName(), $normalizationGroups, $denormalizationGroups);

                if (!$attribute->isIdentifier()) {
                    $attribute->setReadable(true);
                }
            }

            if (null !== $denormalizationGroups && 0 < count(array_intersect($groups, $denormalizationGroups))) {
                $attribute = $this->getOrCreateAttribute($classMetadata, $serializerAttribute->getName(), $normalizationGroups, $denormalizationGroups);

                if (!$attribute->isIdentifier()) {
                    $attribute->setWritable(true);
                }
            }
        }
    }

    /**
     * Populates attributes using reflection.
     *
     * @param ClassMetadataInterface $classMetadata
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     */
    private function populateUsingReflection(
        ClassMetadataInterface $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (null !== $normalizationGroups && null !== $denormalizationGroups) {
            return;
        }

        $reflectionClass = $classMetadata->getReflectionClass();

        // Methods
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $numberOfRequiredParameters = $reflectionMethod->getNumberOfRequiredParameters();
            $methodName = $reflectionMethod->name;

            if ($this->populateFromSetter(
                $classMetadata,
                $methodName,
                $numberOfRequiredParameters,
                $normalizationGroups,
                $denormalizationGroups)
            ) {
                continue;
            }

            if (0 !== $numberOfRequiredParameters) {
                continue;
            }

            if ($this->populateFromGetterAndHasser($classMetadata, $methodName, $normalizationGroups, $denormalizationGroups)) {
                continue;
            }

            $this->populateFromIsser($classMetadata, $methodName, $normalizationGroups, $denormalizationGroups);
        }

        // Properties
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $attribute = $this->getOrCreateAttribute(
                $classMetadata,
                $reflectionProperty->name,
                $normalizationGroups,
                $denormalizationGroups
            );

            if (null === $normalizationGroups) {
                $attribute->setReadable(true);
            }

            if (null === $denormalizationGroups) {
                $attribute->setWritable(true);
            }
        }
    }

    /**
     * Populates attributes from setter methods.
     *
     * @param ClassMetadataInterface $classMetadata
     * @param string                 $methodName
     * @param int                    $numberOfRequiredParameters
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     *
     * @return bool
     */
    private function populateFromSetter(
        ClassMetadataInterface $classMetadata,
        $methodName,
        $numberOfRequiredParameters,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (
            null !== $denormalizationGroups ||
            1 !== $numberOfRequiredParameters ||
            !preg_match('/^(set|add|remove)(.+)$/i', $methodName, $matches)
        ) {
            return false;
        }

        $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst($matches[2]), $normalizationGroups, $denormalizationGroups);
        $attribute->setWritable(true);

        return true;
    }

    /**
     * Populates attributes from getters and hassers.
     *
     * @param ClassMetadataInterface $classMetadata
     * @param string                 $methodName
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     *
     * @return bool
     */
    private function populateFromGetterAndHasser(
        ClassMetadataInterface $classMetadata,
        $methodName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (
            null !== $normalizationGroups ||
            (0 !== strpos($methodName, 'get') && 0 !== strpos($methodName, 'has'))
        ) {
            return false;
        }

        $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($methodName, 3)), $normalizationGroups, $denormalizationGroups);
        $attribute->setReadable(true);

        return true;
    }

    /**
     * Populates attributes from issers.
     *
     * @param ClassMetadataInterface $classMetadata
     * @param string                 $methodName
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     */
    private function populateFromIsser(
        ClassMetadataInterface $classMetadata,
        $methodName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (null !== $normalizationGroups || 0 !== strpos($methodName, 'is')) {
            return;
        }

        $attribute = $this->getOrCreateAttribute($classMetadata, lcfirst(substr($methodName, 2)), $normalizationGroups, $denormalizationGroups);
        $attribute->setReadable(true);
    }

    /**
     * Gets or creates the {@see AttributeMetadataInterface} of the given name.
     *
     * @param ClassMetadataInterface $classMetadata
     * @param string                 $attributeName
     * @param array|null             $normalizationGroups
     * @param array|null             $denormalizationGroups
     *
     * @return AttributeMetadataInterface
     */
    private function getOrCreateAttribute(
        ClassMetadataInterface $classMetadata,
        $attributeName,
        array $normalizationGroups = null,
        array $denormalizationGroups = null
    ) {
        if (isset($classMetadata->getAttributes()[$attributeName])) {
            return $classMetadata->getAttributes()[$attributeName];
        }

        $attributeMetadata = new AttributeMetadata($attributeName);
        $classMetadata->addAttribute($attributeMetadata);

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

        if (!$this->resourceCollection->getResourceForEntity($class) && !(
            $types[0]->isCollection() &&
            $types[0]->getCollectionType() &&
            ($class = $types[0]->getCollectionType()->getClass()) &&
            $this->resourceCollection->getResourceForEntity($class)
        )) {
            return $attributeMetadata;
        }

        if (null === $normalizationGroups) {
            $attributeMetadata->setNormalizationLink(true);
        }

        if (null === $denormalizationGroups) {
            $attributeMetadata->setDenormalizationLink(true);
        }

        if ($attributeMetadata->isNormalizationLink() && $attributeMetadata->isDenormalizationLink()) {
            return $attributeMetadata;
        }

        if (!$this->serializerClassMetadataFactory ||
            !($relationSerializerMetadata = $this->serializerClassMetadataFactory->getMetadataFor($class))
        ) {
            $attributeMetadata->setNormalizationLink(true);
            $attributeMetadata->setDenormalizationLink(true);

            return $attributeMetadata;
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
                return $attributeMetadata;
            }
        }

        if (!isset($normalizationLink)) {
            $attributeMetadata->setNormalizationLink(true);
        }

        if (!isset($denormalizationLink)) {
            $attributeMetadata->setDenormalizationLink(true);
        }

        return $attributeMetadata;
    }
}
