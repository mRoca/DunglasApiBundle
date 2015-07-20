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

/**
 * Uses serialization groups or alternatively reflection to populate attributes.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ReflectionLoader implements LoaderInterface
{
    /**
     * @var AttributeMetadataFactoryInterface
     */
    private $attributeMetadataFactory;

    public function __construct(AttributeMetadataFactoryInterface $attributeMetadataFactory)
    {
        $this->attributeMetadataFactory = $attributeMetadataFactory;
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
        if (null !== $normalizationGroups && null !== $denormalizationGroups) {
            return;
        }

        $reflectionClass = $classMetadata->getReflectionClass();

        // Methods
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $numberOfRequiredParameters = $reflectionMethod->getNumberOfRequiredParameters();
            $methodName = $reflectionMethod->name;

            if ($this->populateFromSetter(
                $classMetadata, $methodName, $numberOfRequiredParameters, $normalizationGroups, $denormalizationGroups
            )) {
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
            $attribute = $this->attributeMetadataFactory->getAttributeMetadataFor(
                $classMetadata, $reflectionProperty->name, $normalizationGroups, $denormalizationGroups
            );

            if (null === $normalizationGroups) {
                $attribute->setReadable(true);
                $classMetadata->addAttribute($attribute);
            }

            if (null === $denormalizationGroups) {
                $attribute->setWritable(true);
                $classMetadata->addAttribute($attribute);
            }
        }

        return true;
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

        $attribute = $this->attributeMetadataFactory->getAttributeMetadataFor(
            $classMetadata, lcfirst($matches[2]), $normalizationGroups, $denormalizationGroups
        );
        $attribute->setWritable(true);
        $classMetadata->addAttribute($attribute);

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

        $attribute = $this->attributeMetadataFactory->getAttributeMetadataFor($classMetadata, lcfirst(substr($methodName, 3)), $normalizationGroups, $denormalizationGroups);
        $attribute->setReadable(true);
        $classMetadata->addAttribute($attribute);

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

        $attribute = $this->attributeMetadataFactory->getAttributeMetadataFor($classMetadata, lcfirst(substr($methodName, 2)), $normalizationGroups, $denormalizationGroups);
        $attribute->setReadable(true);
        $classMetadata->addAttribute($attribute);
    }
}
