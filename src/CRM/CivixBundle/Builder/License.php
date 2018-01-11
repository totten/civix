<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use DOMDocument;
use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update a file based on a template
 */
class License implements Builder {

  protected $name;

  /**
   * @param $overwrite scalar; TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
   */
  public function __construct(\LicenseData\License $license, $path, $overwrite) {
    $this->license = $license;
    $this->path = $path;
    $this->overwrite = $overwrite;
  }

  public function loadInit(&$ctx) {
  }

  public function init(&$ctx) {
  }

  public function load(&$ctx) {
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    if (file_exists($this->path) && $this->overwrite === 'ignore') {
      // do nothing
    }
    elseif (file_exists($this->path) && !$this->overwrite) {
      $output->writeln("<error>Skip " . $this->path . ": file already exists</error>");
    }
    else {
      $output->writeln("<info>Write " . $this->path . "</info>");
      $text = strtr($this->license->getText(), [
        '<YEAR>' => date('Y'),
        '<OWNER>' => sprintf('%s <%s>', $ctx['author'], $ctx['email']),
        '<TITLE>' => sprintf('Package: %s', $ctx['fullName']),
      ]);
      file_put_contents($this->path, $text);
    }
  }

}
