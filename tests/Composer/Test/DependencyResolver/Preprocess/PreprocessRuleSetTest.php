<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 11:53 AM
 */

namespace Composer\Test\DependencyResolver\Preprocess;

use Composer\DependencyResolver\Preprocess\PreprocessRule;
use Composer\DependencyResolver\Preprocess\PreprocessRuleSet;
use Composer\DependencyResolver\Rule;
use Composer\Test\TestCase;

class PreprocessRuleSetTest extends TestCase
{
    public function testAddTrivialRuleFails()
    {
        $rule = new PreprocessRule(array(42, -42), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertTrue($rule->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $result = $ruleSet->add($rule);
        $this->assertFalse($result);

        $this->assertEquals(0, $ruleSet->count());
    }

    public function testAddNonTrivialUnitRuleSucceeds()
    {
        $rule = new PreprocessRule(array(42), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $result = $ruleSet->add($rule);
        $this->assertTrue($result);

        $this->assertEquals(1, $ruleSet->count());
    }

    public function testAddNonTrivialNonUnitRuleAfterUnitRuleFails()
    {
        $unit = new PreprocessRule(array(42), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($unit->isTrivial());

        $rule = new PreprocessRule(array(42, 100), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $result = $ruleSet->add($unit);
        $this->assertTrue($result);

        $this->assertEquals(1, $ruleSet->count());

        $result = $ruleSet->add($rule);
        $this->assertFalse($result);

        $this->assertEquals(1, $ruleSet->count());
    }
}
