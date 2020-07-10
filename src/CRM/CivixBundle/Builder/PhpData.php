<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Read/write a serialized data file based on PHP's var_export() format
 */
class PhpData implements Builder {

  /**
   * @var string
   */
  protected $path;

  /**
   * @var mixed
   */
  protected $data;

  /**
   * @var
   */
  protected $header;

  public function __construct($path, $header = NULL) {
    $this->path = $path;
    $this->header = $header;
  }

  /**
   * Get the xml document
   *
   * @return array
   */
  public function get() {
    return $this->data;
  }

  public function set($data) {
    $this->data = $data;
  }

  public function loadInit(&$ctx) {
    if (file_exists($this->path)) {
      $this->load($ctx);
    }
    else {
      $this->init($ctx);
    }
  }

  /**
   * Initialize a new var_export() document
   */
  public function init(&$ctx) {
  }

  /**
   * Read from file
   */
  public function load(&$ctx) {
    $this->data = include $this->path;
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    $output->writeln("<info>Write " . $this->path . "</info>");

    $content = "<?php\n";
    if ($this->header) {
      $content .= $this->header;
    }
    $content .= "\nreturn ";
    $content .= preg_replace_callback('/^ +/m',
      // VarExporter indents with 4x spaces. Civi/Drupal code standard is 2x spaces.
      function($m) {
        $spaces = $m[0];
        return substr($spaces, 0, ceil(strlen($spaces) / 2));
      },
      VarExporter::export($this->data)
    );
    $content .= ";\n";
    file_put_contents($this->path, $content);
  }

}
