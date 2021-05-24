<?php

namespace Tests\DataFixtures\Model;

/**
 * Class Deep
 */
class Deep
{
    public const STR = 'find me';

    /**
     * @var string
     */
    private $searchValue = self::STR;

    /**
     * @return string
     */
    public function getDeepValue(): string
    {
        return $this->searchValue;
    }
}
