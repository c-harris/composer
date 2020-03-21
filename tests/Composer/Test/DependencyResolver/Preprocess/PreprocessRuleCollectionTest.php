<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 2:35 AM
 */

namespace Composer\Test\DependencyResolver\Preprocess;

use Composer\DependencyResolver\Preprocess\PreprocessRule;
use Composer\DependencyResolver\Preprocess\PreprocessRuleCollection;
use Composer\DependencyResolver\Rule;
use Composer\Test\TestCase;

class PreprocessRuleCollectionTest extends TestCase
{
    public function testObjectHashGeneration()
    {
        $rule = new PreprocessRule(array(42, -74), Rule::RULE_PACKAGE_REQUIRES, null);

        $collection = new PreprocessRuleCollection();

        $this->assertEquals(0, $collection->count());
        $collection->attach($rule);

        $this->assertEquals(1, $collection->count());
    }
}
