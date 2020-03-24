<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 2:30 AM
 */

namespace Composer\DependencyResolver\Preprocess;

class PreprocessRuleSet
{
    /** @var PreprocessRuleCollection */
    private $rules;

    /**
     * List of unit literals that have previously been seen
     *
     * @var array
     */
    private $units = array();

    /**
     * Track which rules have which literals.  Speeds up, for example, subsumption tracking, as you only need to
     * consider the rules which share at least one literal with the candidate.
     */
    private $occurs = array();

    public function __construct()
    {
        $this->rules = new PreprocessRuleCollection();
    }

    /**
     * Return number of rules in this rule set
     *
     * @return int
     */
    public function count()
    {
        return $this->rules->count();
    }

    /**
     * Attempt to add rule to rule set.
     *
     * @param PreprocessRule $rule
     * @return bool                 Was rule successfully added?
     */
    public function add(PreprocessRule $rule)
    {
        if ($rule->isTrivial()) {
            return false;
        }

        $literals = $rule->getLiterals();
        // If candidate rule contains a unit literal we've previously seen, it's subsumed by said unit literal and thus
        // cannot affect the result, so drop it
        if (0 < count(array_intersect($this->units, $literals))) {
            return false;
        }

        $hash = $rule->getHash();
        if ($rule->isAssertion()) {
            $unitLit = $literals[array_keys($literals)[0]];
            $falseUnit = -$unitLit;
            $this->units[] = $unitLit;

            if (!array_key_exists($unitLit, $this->occurs)) {
                $this->occurs[$unitLit] = array();
            }

            $dropList = $this->occurs[$unitLit];
            foreach ($dropList as $drop) {
                $this->dropRule($this->rules->get($drop));
            }
            $this->occurs[$unitLit] = array();

            if (!array_key_exists($falseUnit, $this->occurs)) {
                $this->occurs[$falseUnit] = array();
            }

            if (!empty($this->occurs[$falseUnit])) {
                foreach ($this->occurs[$falseUnit] as $dropRule) {
                    /** @var PreprocessRule $newRule */
                    $newRule = clone($this->rules->get($dropRule));
                    $newRule->dropLiteral($falseUnit);
                    $this->dropRule($this->rules->get($dropRule));
                    $this->add($newRule);
                    unset($newRule);
                }
            }
        }

        $smallLit = null;
        $litCount = PHP_INT_MAX;
        foreach ($literals as $lit) {
            if (!array_key_exists($lit, $this->occurs)) {
                $this->occurs[$lit] = array();
            }
            // check to see if any of the already-added rules that share this particular literal subsume it
            $occurList = $this->occurs[$lit];
            $occurCount = count($occurList);
            if (0 < $occurCount) {
                if ($occurCount < $litCount) {
                    $smallLit = $lit;
                    $litCount = $occurCount;
                }
            }
        }

        if (false && null !== $smallLit) {
            $occurList = $this->occurs[$smallLit];
            if ($this->rules->checkIsSubsumedBy($rule, $occurList)) {
                return false;
            }
        }

        $combo = array();
        // check to see if rule subsumes any existing rules
        foreach ($literals as $lit) {
            if (empty($combo)) {
                $combo = $this->occurs[$lit];
            } else {
                $combo = array_intersect($combo, $this->occurs[$lit]);
            }
        }

        if (0 < count($combo)) {
            $result = $this->rules->checkSubsumes($rule, $combo);
            if (!empty($result)) {
                foreach ($result as $dropRule) {
                    $this->dropRule($dropRule);
                }
            }
        }

        foreach ($literals as $lit) {
            $this->occurs[$lit][] = $hash;
        }

        $this->rules->attach($rule);

        return true;
    }

    public function contains($object)
    {
        return $this->rules->contains($object);
    }

    /**
     * Return the occurs lists - tracking which rules have what literal.
     *
     * @return array
     */
    public function getOccursList()
    {
        return $this->occurs;
    }

    /**
     * Remove rule from rule set
     *
     * @param PreprocessRule $rule
     */
    public function dropRule(PreprocessRule $rule)
    {
        $hash = $rule->getHash();
        $this->rules->detach($hash);

        $literals = $rule->getLiterals();
        $hashArray = array($hash);
        foreach ($literals as $lit) {
            $this->occurs[$lit] = array_values(array_diff($this->occurs[$lit], $hashArray));
        }
    }
}
