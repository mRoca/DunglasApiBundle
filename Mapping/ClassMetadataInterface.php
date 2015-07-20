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

/**
 * Class metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ClassMetadataInterface
{
    /**
     * Gets the class name.
     *
     * @return string
     */
    public function getName();

    /**
     * Sets description.
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Gets the description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Sets IRI of this attribute.
     *
     * @param string $iri
     */
    public function setIri($iri);

    /**
     * Gets IRI of this attribute.
     *
     * @return string|null
     */
    public function getIri();

    /**
     * Adds an {@link AttributeMetadataInterface}.
     *
     * @param AttributeMetadataInterface $attributeMetadata
     */
    public function addAttribute(AttributeMetadataInterface $attributeMetadata);

    /**
     * Gets attributes.
     *
     * @return AttributeMetadataInterface[]
     */
    public function getAttributes();

    /**
     * Returns a {@see \ReflectionClass} instance for this class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass();

    /**
     * Gets the attribute identifier of the class.
     *
     * @return AttributeMetadataInterface|null
     */
    public function getIdentifier();
}
