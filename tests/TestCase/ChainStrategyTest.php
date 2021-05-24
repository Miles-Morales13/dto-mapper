<?php

namespace Tests\TestCase;

use DataMapper\Hydrator\HydratorFactory;
use DataMapper\Hydrator\HydratorInterface;
use DataMapper\Hydrator\ObjectHydrator;
use DataMapper\Strategy\ClosureStrategy;
use DataMapper\Strategy\GetterStrategy;
use DataMapper\Strategy\XPathGetterStrategy;
use DataMapper\Type\TypeResolver;

use Tests\DataFixtures\Dto\DeepValueDto;
use Tests\DataFixtures\Model\Deep;
use Tests\DataFixtures\Model\Inner;
use Tests\DataFixtures\Model\Outer;
use Tests\DataFixtures\Traits\BaseMappingTrait;

use PHPUnit\Framework\TestCase;

/**
 * Class ChainStrategyTest
 */
class ChainStrategyTest extends TestCase
{
    use BaseMappingTrait;

    /**
     */
    public function testChainStrategyHydration(): void
    {
        $outer = new Outer();
        $pathTOValue = 'inner.deep.searchValue';
        $searchString = 'Returned from closure';

        $hydrator = $this->createChainHydrator(
            $outer,
            DeepValueDto::class,
            [
                [
                    'found',
                    [
                        new ObjectHydrator($this->createHydratedClassesFactory()),
                        $pathTOValue,
                    ],
                    XPathGetterStrategy::class,
                ],
                [
                    'inner',
                    [
                        function (Inner $inner) use ($searchString): string {
                            return $inner->getDeep()->getDeepValue() . $searchString;
                        },
                    ],
                    ClosureStrategy::class,
                ],
                [
                    'destinationGetterTarget',
                    [
                        'getTestGetter',
                    ],
                    GetterStrategy::class,
                ]
            ]
        );

        $dto = $hydrator->hydrate($outer, DeepValueDto::class);
        $this->assertEquals($dto->getFound(), $outer->getInner()->getDeep()->getDeepValue());

        $this->assertEquals(Deep::STR . $searchString, $dto->getInner());
        $this->assertEquals($dto->getDestinationGetterTarget(), $outer->getTestGetter());
        $this->assertEquals($dto->getCopiedByName(), $outer->getCopiedByName());
    }

    /**
     * @param object $source
     * @param string $destinationClass
     * @param array  $mapping
     *
     * @return HydratorInterface
     */
    private function createChainHydrator(object $source, string $destinationClass, array $mapping): HydratorInterface
    {
        $mappingRegistry = $this->createMappingRegistry();
        $hydrationRegistry = $this->createHydrationRegistry();
        $mappingRegistry
            ->getClassMappingRegistry()
            ->registerMappingClass($destinationClass);

        $typeStrategyType = TypeResolver::getStrategyType($source, $destinationClass);
        foreach ($mapping as [$destinationProperty, $args, $strategy]) {
            $mappingRegistry
                ->getStrategyRegistry()
                ->registerPropertyStrategy($typeStrategyType, $destinationProperty, new $strategy(...$args));
        }

        return (new HydratorFactory($hydrationRegistry, $mappingRegistry))->createHydrator($source, $destinationClass);
    }
}
