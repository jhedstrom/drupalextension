<?php

namespace Drupal\MinkExtension\ServiceContainer;

use Behat\MinkExtension\ServiceContainer\MinkExtension as BaseMinkExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MinkExtension extends BaseMinkExtension
{

    /**
     * Default wait time for AJAX to finish (in seconds).
     *
     * @var int
     */
    const AJAX_TIMEOUT = 5;

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        parent::configure($builder);

        // Add extended options.
        $builder->
        children()->
        scalarNode('ajax_timeout')->
            defaultValue(static::AJAX_TIMEOUT)->
            info(sprintf('Change the maximum time to wait for AJAX calls to complete. Defaults to %s seconds.', static::AJAX_TIMEOUT))->
        end();
    }
}
