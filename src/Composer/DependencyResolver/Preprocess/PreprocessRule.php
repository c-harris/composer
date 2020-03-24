<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 1:35 AM
 */

namespace Composer\DependencyResolver\Preprocess;

use Composer\DependencyResolver\GenericRule;

class PreprocessRule extends GenericRule
{
    const LITERAL_MODULUS = 32;
    public static $subsumes = 0;
    public static $subsumesPostHash = 0;
    public static $subsumesPostCount = 0;

    public $positiveLiteralHash = 0;
    public $negativeLiteralHash = 0;
    public $allLiteralHash = 0;
    private $posLiterals = array();
    private $negLiterals = array();
    private $hash = '';
    public $literalCount = 0;

    public function __construct(array $literals, $reason, $reasonData, array $job = null)
    {
        parent::__construct($literals, $reason, $reasonData, $job);

        $this->calculateHashes();
    }

    /**
     * Indicate whether rule is trivially true.
     *
     * @return bool
     */
    public function isTrivial()
    {
        if (0 == ($this->positiveLiteralHash & $this->negativeLiteralHash)) {
            return false;
        }

        return 0 < count(array_intersect($this->posLiterals, $this->negLiterals));
    }

    /**
     * Return hash of rule, to be used in whole bunch of things
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    private function literalHash(array $literals)
    {
        array_walk(
            $literals,
            function (&$value, $key) { $value = (abs($value) % PreprocessRule::LITERAL_MODULUS);}
        );

        $literals = array_unique($literals);

        $result = 0;

        foreach ($literals as $lit) {
            $scale = 1 << $lit;
            $result = $result | $scale;
        }

        return $result;
    }

    /**
     * Drop the supplied literal, if present, from the rule and update hashes to suit
     *
     * @param $int
     */
    public function dropLiteral($int)
    {
        $this->literals = array_diff($this->literals, array($int));
        $this->calculateHashes();
    }

    private function calculateHashes()
    {
        $this->posLiterals = array_filter(
            $this->literals, function ($value) {
            return 0 < $value;
        }
        );
        $this->negLiterals = array_filter(
            $this->literals, function ($value) {
            return 0 > $value;
        }
        );
        array_walk(
            $this->negLiterals, function (&$value, $key) {
            $value = abs($value);
        }
        );
        $this->positiveLiteralHash = $this->literalHash($this->posLiterals);
        $this->negativeLiteralHash = $this->literalHash($this->negLiterals);
        $this->allLiteralHash = $this->positiveLiteralHash | $this->negativeLiteralHash;
        $this->hash = md5(implode(',', $this->literals));
        $this->literalCount = count($this->literals);
    }

    /**
     * Determine whether this rule subsumes the supplied one - ie, this rule's literals are a strict, proper subset
     * of supplied rule's literals
     *
     * @param PreprocessRule $rule
     *
     * @return bool
     */
    public function subsumes(PreprocessRule &$rule)
    {
        PreprocessRule::$subsumes++;

        // If the positive hashes imply that subsumption _cannot_ happen, bail out
        if (($this->positiveLiteralHash & $rule->positiveLiteralHash) != $this->positiveLiteralHash) {
            return false;
        }

        // If the negative hashes imply that subsumption _cannot_ happen, bail out
        if (($this->negativeLiteralHash & $rule->negativeLiteralHash) != $this->negativeLiteralHash) {
            return false;
        }

        PreprocessRule::$subsumesPostHash++;

        if ($this->literalCount >= $rule->literalCount) {
            return false;
        }

        PreprocessRule::$subsumesPostCount++;

        $thisLitz = $this->literals;
        $overlap = array_intersect($thisLitz, $rule->getLiterals());

        if ($thisLitz == $overlap) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getPositiveLiteralHash()
    {
        return $this->positiveLiteralHash;
    }

    /**
     * @return int
     */
    public function getNegativeLiteralHash()
    {
        return $this->negativeLiteralHash;
    }
}
