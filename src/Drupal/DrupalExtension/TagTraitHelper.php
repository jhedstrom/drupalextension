<?php

namespace Drupal\DrupalExtension;

/**
 * Helper class for the TagTrait.
 *
 * Traits in PHP can not easily depend on other traits. This class wraps the
 * depending traits so that they can be injected using composition. This allows
 * TagTrait to be freely used in classes without needing to remember to include
 * the depending traits as well.
 */
class TagTraitHelper
{
    use FeatureTrait;
    use ScenarioTrait;

    /**
     * Escalate visibility to access the protected methods in the traits.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this, $name], $arguments);
    }
}
