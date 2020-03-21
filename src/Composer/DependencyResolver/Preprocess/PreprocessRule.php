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


    private $positiveLiteralHash = 0;
    private $negativeLiteralHash = 0;
    private $posLiterals = array();
    private $negLiterals = array();

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
    }
}
