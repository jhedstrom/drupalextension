<?php

/**
 * @file
 * Blackbox HTML server context.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

declare(strict_types=1);

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use DrevOps\BehatPhpServer\PhpServerContext;

/**
 * PhpServerContext variant that serves blackbox HTML fixtures unconditionally.
 *
 * Upstream 'PhpServerContext' only starts the server for scenarios tagged
 * '@phpserver'. Every blackbox scenario in this suite visits a URL, so we
 * start the server for every scenario instead of tagging each one.
 */
class BlackboxServerContext extends PhpServerContext {

  #[BeforeScenario]
  public function blackboxStartServer(BeforeScenarioScope $scope): void {
    $this->start();
  }

  #[AfterScenario]
  public function blackboxStopServer(AfterScenarioScope $scope): void {
    $this->stop();
  }

}
