<?php

declare(strict_types=1);

namespace Drupal\DrupalExtension\Tests;

use Drupal\DrupalExtension\Context\DrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrupalContext::class)]
class DrupalContextTest extends TestCase {

    public function testGetTranslationResourcesReturnsXliffFiles(): void {
        $resources = DrupalContext::getTranslationResources();
        $this->assertNotEmpty($resources);
        foreach ($resources as $resource) {
            $this->assertStringEndsWith('.xliff', $resource);
        }
    }

    public function testGetTranslationResourcesContainsExpectedLanguages(): void {
        $resources = DrupalContext::getTranslationResources();
        $basenames = array_map('basename', $resources);
        $this->assertContains('fr.xliff', $basenames);
        $this->assertContains('es.xliff', $basenames);
        $this->assertContains('da.xliff', $basenames);
    }

}
