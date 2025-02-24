<?php

namespace Unish;

/**
 * @group commands
 */
class WatchdogTest extends UnishIntegrationTestCase
{
    public function testWatchdog()
    {
        $this->drush('pm-install', ['dblog']);
        $this->drush('watchdog-delete', ['all'], ['yes' => true]);
        $output = $this->getErrorOutput();
        $this->assertStringContainsString('All watchdog messages have been deleted', $output);

        $eval1 = "\\Drupal::logger('drush')->notice('Unish rocks.');";
        $this->drush('php-eval', [$eval1]);
        $this->drush('watchdog-show', [], ['count' => 50]);
        $output = $this->getOutput();
        $this->assertStringContainsString('Unish rocks.', $output);

        // Add a new entry with a long message with the letter 'd' and verify that watchdog-show does
        // not print it completely in the listing unless --full is given.
        // As the output is formatted so lines may be splitted, assertStringContainsString does not work
        // in this scenario. Therefore, we will count the number of times a character is present.
        $message_chars = 300;
        $char = '*';
        $message = str_repeat($char, $message_chars);
        $eval2 = "\\Drupal::logger('drush')->notice('$message');";
        $this->drush('php-eval', [$eval2]);
        $this->drush('watchdog-show');
        $output = $this->getOutput();
        $this->assertStringContainsString('Unish rocks', $output);
        $this->assertGreaterThan(substr_count($output, $char), $message_chars);
        $this->drush('watchdog-show', [], ['extended' => null]);
        $output = $this->getOutput();
        $this->assertGreaterThanOrEqual($message_chars, substr_count($output, $char));

        // Tests message deletion
        $this->drush('watchdog-delete', ['all'], ['yes' => true]);
        $output = $this->getErrorOutput();
        $this->assertStringContainsString('All watchdog messages have been deleted', $output);
        $this->drush('watchdog-show');
        $output = $this->getOutput();
        $this->assertEquals('', $output);
    }
}
