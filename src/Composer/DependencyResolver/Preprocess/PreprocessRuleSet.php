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
            $unitLit = $literals[0];
            $this->units[] = $unitLit;

            if (array_key_exists($unitLit, $this->occurs)) {
                $dropList = $this->occurs[$unitLit];
                foreach ($dropList as $drop) {
                    $this->rules->detach($drop);
                }
            }
            $this->occurs[$unitLit] = array();
        }

        foreach ($literals as $lit) {
            if (!array_key_exists($lit, $this->occurs)) {
                $this->occurs[$lit] = array();
            }
            $this->occurs[$lit][] = $hash;
        }

        $this->rules->attach($rule);

        return true;
    }
}
