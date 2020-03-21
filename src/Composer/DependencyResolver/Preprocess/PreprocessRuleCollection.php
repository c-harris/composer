<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/03/20
 * Time: 2:32 AM
 */

namespace Composer\DependencyResolver\Preprocess;

class PreprocessRuleCollection extends \SplObjectStorage
{
    /**
     *  Use PreprocessRule's hash value to save effort of coming up with something new
     * @param PreprocessRule $object
     * @return string
     */
    public function getHash($object)
    {
        return strval($object->getHash());
    }
}
