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

    public function testAddUnitRuleSubsumesPreviouslyAddedRules()
    {
        $rule1 = new PreprocessRule(array(42, 100), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $rule2 = new PreprocessRule(array(42, 101), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule2->isTrivial());

        $rule3 = new PreprocessRule(array(-42, 201, 202), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule3->isTrivial());

        $rule3Drop = new PreprocessRule(array(201, 202), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule3->isTrivial());

        $unit = new PreprocessRule(array(42), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($unit->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $this->assertTrue($ruleSet->add($rule1));
        $this->assertTrue($ruleSet->add($rule2));
        $this->assertTrue($ruleSet->add($rule3));
        $this->assertEquals(3, $ruleSet->count());

        $expected = array(
            42 => array($rule1->getHash(), $rule2->getHash()),
            100 => array($rule1->getHash()),
            101 => array($rule2->getHash()),
            201 => array($rule3->getHash()),
            202 => array($rule3->getHash()),
            -42 => array($rule3->getHash())
        );
        $actual = $ruleSet->getOccursList();
        $this->assertEquals($expected, $actual);

        $this->assertTrue($ruleSet->add($unit));
        $this->assertEquals(2, $ruleSet->count());

        $this->assertTrue($ruleSet->contains($unit));
        $this->assertFalse($ruleSet->contains($rule1->getHash()));
        $this->assertFalse($ruleSet->contains($rule2));
        $this->assertFalse($ruleSet->contains($rule3));
        $this->assertTrue($ruleSet->contains($rule3Drop));

        $expected = array(
            42 => array($unit->getHash()),
            100 => array(),
            101 => array(),
            201 => array($rule3Drop->getHash()),
            202 => array($rule3Drop->getHash()),
            -42 => array()
        );
        $actual = $ruleSet->getOccursList();
        $this->assertEquals($expected, $actual);
    }

    public function testAddUnitRuleSubsumesSomePreviouslyAddedRules()
    {
        $rule1 = new PreprocessRule(array(42, 100), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $rule2 = new PreprocessRule(array(43, 101), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule2->isTrivial());

        $unit = new PreprocessRule(array(42), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($unit->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $this->assertTrue($ruleSet->add($rule1));
        $this->assertTrue($ruleSet->add($rule2));
        $this->assertEquals(2, $ruleSet->count());
        $this->assertTrue($ruleSet->add($unit));
        $this->assertEquals(2, $ruleSet->count());

        $this->assertTrue($ruleSet->contains($unit));
        $this->assertFalse($ruleSet->contains($rule1->getHash()));
        $this->assertTrue($ruleSet->contains($rule2));
    }

    public function testDropOnlyRuleEmptiesAllOccursLists()
    {
        $rule1 = new PreprocessRule(array(42, 100, -12), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $this->assertTrue($ruleSet->add($rule1));
        $this->assertEquals(1, $ruleSet->count());

        $expected = array(-12 => array($rule1->getHash()), 42 => array($rule1->getHash()), 100 => array($rule1->getHash()));
        $actual = $ruleSet->getOccursList();

        $this->assertEquals($expected, $actual);

        $ruleSet->dropRule($rule1);
        $this->assertEquals(0, $ruleSet->count());

        $expected = array(-12 => array(), 42 => array(), 100 => array());
        $actual = $ruleSet->getOccursList();

        $this->assertEquals($expected, $actual);
    }

    public function testDropOneOfTwoNonOverlappingRulesEmptiesOnlySomeOccurLists()
    {
        $rule1 = new PreprocessRule(array(42, 100, -12), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $rule2 = new PreprocessRule(array(142, 1100, -112), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $this->assertTrue($ruleSet->add($rule1));
        $this->assertTrue($ruleSet->add($rule2));
        $this->assertEquals(2, $ruleSet->count());

        $expected = array(
            -12 => array($rule1->getHash()),
            42 => array($rule1->getHash()),
            100 => array($rule1->getHash()),
            -112 => array($rule2->getHash()),
            142 => array($rule2->getHash()),
            1100 => array($rule2->getHash())
        );
        $actual = $ruleSet->getOccursList();

        $this->assertEquals($expected, $actual);

        $ruleSet->dropRule($rule1);
        $this->assertEquals(1, $ruleSet->count());

        $expected = array(
            -12 => array(),
            42 => array(),
            100 => array(),
            -112 => array($rule2->getHash()),
            142 => array($rule2->getHash()),
            1100 => array($rule2->getHash())
        );
        $actual = $ruleSet->getOccursList();

        $this->assertEquals($expected, $actual);
    }

    public function testDropOneOfTwoOverlappingRulesDoesNotEmptyOverlappingOccurLists()
    {
        $rule1 = new PreprocessRule(array(42, 100, -12), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $rule2 = new PreprocessRule(array(142, 1100, -112, 100, -12), Rule::RULE_PACKAGE_REQUIRES, null);
        $this->assertFalse($rule1->isTrivial());

        $ruleSet = new PreprocessRuleSet();
        $this->assertEquals(0, $ruleSet->count());

        $this->assertTrue($ruleSet->add($rule1));
        $this->assertTrue($ruleSet->add($rule2));
        $this->assertEquals(2, $ruleSet->count());

        $expected = array(
            -12 => array($rule1->getHash(), $rule2->getHash()),
            42 => array($rule1->getHash()),
            100 => array($rule1->getHash(), $rule2->getHash()),
            -112 => array($rule2->getHash()),
            142 => array($rule2->getHash()),
            1100 => array($rule2->getHash())
        );
        $actual = $ruleSet->getOccursList();

        $this->assertEquals($expected, $actual);

        $ruleSet->dropRule($rule1);
        $this->assertEquals(1, $ruleSet->count());

        $expected = array(
            -12 => array($rule2->getHash()),
            42 => array(),
            100 => array($rule2->getHash()),
            -112 => array($rule2->getHash()),
            142 => array($rule2->getHash()),
            1100 => array($rule2->getHash())
        );
        $actual = $ruleSet->getOccursList();
        $this->assertEquals($expected, $actual);
    }
}
