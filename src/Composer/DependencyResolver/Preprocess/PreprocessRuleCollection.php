<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 2:32 AM
 */

namespace Composer\DependencyResolver\Preprocess;

class PreprocessRuleCollection implements \Countable
{
    private $rules = array();

    /**
     * Add rule to collection
     *
     * @param PreprocessRule $object
     */
    public function attach(PreprocessRule $object)
    {
        $this->rules[$object->getHash()] = $object;
    }

    /**
     * Remove rule from collection
     *
     * @param string $hash
     */
    public function detach($hash)
    {
        unset($this->rules[$hash]);
    }

    /**
     *  Use PreprocessRule's hash value to save effort of coming up with something new
     * @param PreprocessRule $object
     * @return string
     */
    public function getHash(PreprocessRule $object)
    {
        return strval($object->getHash());
    }

    public function count()
    {
        return count($this->rules);
    }

    /**
     * Return whether rule set contains the supplied hash or object
     *
     * @param $object
     *
     * @return bool
     */
    public function contains($object)
    {
        if (is_string($object)) {
            return array_key_exists($object, $this->rules);
        }
        if ($object instanceof PreprocessRule) {
            return array_key_exists($object->getHash(), $this->rules);
        }
        throw new \InvalidArgumentException('Must check either string or PreprocessRule');
    }

    /**
     * Check if any of the existing candidates subsume the supplied candidate rule
     *
     * @param PreprocessRule $rule
     * @param array $candidates
     *
     * @return bool
     */
    public function checkSubsumed(PreprocessRule &$rule, array $candidates)
    {
        $allHash = $rule->allLiteralHash;
        $allCount = $rule->literalCount;
        foreach ($candidates as $hash) {
            if (array_key_exists($hash, $this->rules)) {
                if ($this->rules[$hash]->literalCount < $allCount) {
                    if (($this->rules[$hash]->allLiteralHash & $allHash) == $this->rules[$hash]->allLiteralHash) {
                        if ($this->rules[$hash]->subsumes($rule)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
