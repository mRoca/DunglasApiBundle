<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\Mapping\Loader;

use Dunglas\ApiBundle\Mapping\Loader\AnnotationLoader;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadClassMetadata()
    {
        $classAnnotation = new \stdClass();
        $classAnnotation->value = 'http://example.com';

        $attributeAnnotation = new \stdClass();
        $attributeAnnotation->value = 'http://example.org';

        $reflectionProperty = $this->prophesize('ReflectionProperty')->reveal();

        $reflectionClassProphecy = $this->prophesize('ReflectionClass');
        $reflectionClassProphecy->hasProperty('attr')->willReturn(true)->shouldBeCalled();
        $reflectionClassProphecy->getProperty('attr')->willReturn($reflectionProperty)->shouldBeCalled();
        $reflectionClass = $reflectionClassProphecy->reveal();

        $readerProphecy = $this->prophesize('Doctrine\Common\Annotations\Reader');
        $readerProphecy->getClassAnnotation($reflectionClass, AnnotationLoader::IRI_ANNOTATION_NAME)->willReturn($classAnnotation)->shouldBeCalled();
        $readerProphecy->getPropertyAnnotation($reflectionProperty, AnnotationLoader::IRI_ANNOTATION_NAME)->willReturn($attributeAnnotation)->shouldBeCalled();
        $reader = $readerProphecy->reveal();

        $attributeMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\AttributeMetadataInterface');
        $attributeMetadataProphecy->getName()->willReturn('attr')->shouldBeCalled();
        $attributeMetadataProphecy->setIri($attributeAnnotation->value)->shouldBeCalled();
        $attributeMetadata = $attributeMetadataProphecy->reveal();

        $classMetadataProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\ClassMetadataInterface');
        $classMetadataProphecy->getReflectionClass()->willReturn($reflectionClass)->shouldBeCalled();
        $classMetadataProphecy->setIri($classAnnotation->value)->shouldBeCalled();
        $classMetadataProphecy->getAttributes()->willReturn([$attributeMetadata])->shouldBeCalled();
        $classMetadata = $classMetadataProphecy->reveal();

        $loader = new AnnotationLoader($reader);

        $this->assertInstanceOf('Dunglas\ApiBundle\Mapping\Loader\LoaderInterface', $loader);
        $loader->loadClassMetadata($classMetadata, ['a'], ['b'], ['c']);
    }
}
