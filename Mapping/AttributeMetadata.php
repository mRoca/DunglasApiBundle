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

use PropertyInfo\Type;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AttributeMetadata implements AttributeMetadataInterface
{
    const DEFAULT_IDENTIFIER_NAME = 'id';

    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isIdentifier()} instead.
     */
    public $identifier;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getName()} instead.
     */
    public $name;
    /**
     * @var Type[]
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getType()} instead.
     */
    public $type;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getDescription()} instead.
     */
    public $description;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isReadable()} instead.
     */
    public $readable = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isWritable()} instead.
     */
    public $writable = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isRequired()} instead.
     */
    public $required = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isLink()} instead.
     */
    public $link = false;
    /**
     * @var string
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getLinkClass()} instead.
     */
    public $linkClass;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isNormalizationLink()} instead.
     */
    public $normalizationLink = false;
    /**
     * @var bool
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link isDenormalizationLink()} instead.
     */
    public $denormalizationLink = false;
    /**
     * @var string|null
     *
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getIri()} instead.
     */
    public $iri;

    /**
     * @param string    $name
     * @param bool|null $identifier
     */
    public function __construct($name, $identifier = null)
    {
        $this->name = $name;
        $this->identifier = null === $identifier ? $name === self::DEFAULT_IDENTIFIER_NAME : $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function withType(Type $type)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->type = $type;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * {@inheritdoc}
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * {@inheritdoc}
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;
    }

    /**
     * {@inheritdoc}
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * {@inheritdoc}
     */
    public function withLink($link)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->link = $link;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function isLink()
    {
        return $this->link;
    }

    /**
     * {@inheritdoc}
     */
    public function withLinkClass($linkClass)
    {
        $attributeMetadata = clone $this;
        $attributeMetadata->linkClass = $linkClass;

        return $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkClass()
    {
        return $this->linkClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setNormalizationLink($normalizationLink)
    {
        $this->normalizationLink = $normalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isNormalizationLink()
    {
        return $this->normalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function setDenormalizationLink($denormalizationLink)
    {
        $this->denormalizationLink = $denormalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isDenormalizationLink()
    {
        return $this->denormalizationLink;
    }

    /**
     * {@inheritdoc}
     */
    public function setIri($iri)
    {
        $this->iri = $iri;
    }

    /**
     * {@inheritdoc}
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * @return bool
     */
    public function isIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param bool $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Returns the names of the properties that should be serialized.
     *
     * @return string[]
     */
    public function __sleep()
    {
        return [
            'name',
            'identifier',
            'type',
            'description',
            'readable',
            'writable',
            'required',
            'link',
            'linkClass',
            'normalizationLink',
            'denormalizationLink',
            'iri',
        ];
    }
}
