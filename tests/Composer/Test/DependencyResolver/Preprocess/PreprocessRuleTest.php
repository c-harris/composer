<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 1:42 AM
 */

namespace Composer\Test\DependencyResolver\Preprocess;

use Composer\DependencyResolver\Preprocess\PreprocessRule;
use Composer\DependencyResolver\Rule;
use Composer\Test\TestCase;

class PreprocessRuleTest extends TestCase
{
    public function testRuleIsTrivial()
    {
        $rule = new PreprocessRule(array(42, -42), Rule::RULE_PACKAGE_REQUIRES, null);

        $this->assertTrue($rule->isTrivial());
    }

    public function testRuleIsNotTrivialHashNoCollision()
    {
        $rule = new PreprocessRule(array(42, -41), Rule::RULE_PACKAGE_REQUIRES, null);

        $this->assertFalse($rule->isTrivial());
    }

    public function testRuleIsNotTrivialHashCollision()
    {
        $rule = new PreprocessRule(array(42, -74), Rule::RULE_PACKAGE_REQUIRES, null);

        $this->assertFalse($rule->isTrivial());
    }

    public function testRemoveLiteralFromSinglyTrivialRuleRendersItNonTrivial()
    {
        $rule = new PreprocessRule(array(42, -42, 99), Rule::RULE_PACKAGE_REQUIRES, null);

        $this->assertTrue($rule->isTrivial());

        $rule->dropLiteral(42);

        $this->assertFalse($rule->isTrivial());
    }

    public function testDoesNotSubsumeSelf()
    {
        $rule = new PreprocessRule(array(42, -74), Rule::RULE_PACKAGE_REQUIRES, null);

        $this->assertFalse($rule->subsumes($rule));
    }

    public function testDoesSubsumeStrictSuperset()
    {
        $rule1 = new PreprocessRule(array(42, -74), Rule::RULE_PACKAGE_REQUIRES, null);
        $rule2 = new PreprocessRule(array(42, -74, 98), Rule::RULE_PACKAGE_REQUIRES, null);

        $this->assertTrue($rule1->subsumes($rule2));
    }
}
