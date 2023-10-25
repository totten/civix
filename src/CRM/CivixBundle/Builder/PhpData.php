<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Write a data file in PHP format
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
   * @var string
   */
  protected $header;

  /**
   * @var string[]
   */
  private $keysToTranslate;

  /**
   * @var string
   */
  private $extensionUtil;

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
   * Specify fields that will be wrapped in E::ts()
   *
   * @param array $keysToTranslate
   * @return void
   */
  public function useTs(array $keysToTranslate) {
    $this->keysToTranslate = $keysToTranslate;
  }

  /**
   * Adds `use Foo_ExtensionUtil as E;` to the top of the file
   *
   * @param string $extensionUtilClass
   * @return void
   */
  public function useExtensionUtil(string $extensionUtilClass) {
    $this->extensionUtil = $extensionUtilClass;
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    $output->writeln("<info>Write</info> " . $this->path);

    $content = "<?php\n";
    if ($this->extensionUtil) {
      $content .= "use $this->extensionUtil as E;\n";
    }
    if ($this->header) {
      $content .= $this->header;
    }
    $content .= "\nreturn ";
    $data = $this->reduceIndentation(VarExporter::export($this->data));
    $data = $this->ucConstants($data);
    if ($this->keysToTranslate) {
      $data = $this->translateStrings($data, $this->keysToTranslate);
    }
    $content .= "$data;\n";
    file_put_contents($this->path, $content);
  }

  /**
   * VarExporter indents with 4x spaces. Civi/Drupal code standard is 2x spaces.
   */
  private function reduceIndentation(string $data): string {
    return preg_replace_callback('/^ +/m',
      function($m) {
        $spaces = $m[0];
        return substr($spaces, 0, ceil(strlen($spaces) / 2));
      },
      $data
    );
  }

  /**
   * Uppercase constants to match Civi/Drupal code standard
   */
  private function ucConstants(string $data): string {
    foreach (['null', 'false', 'true'] as $const) {
      $uc = strtoupper($const);
      $data = str_replace(" $const,", " $uc,", $data);
    }
    return $data;
  }

  /**
   * Wrap strings in E::ts()
   */
  private function translateStrings(string $data, array $keysToTranslate): string {
    $keys = implode('|', array_unique($keysToTranslate));
    $data = preg_replace("/'($keys)' => ('[^']+'),/", "'\$1' => E::ts(\$2),", $data);
    return $data;
  }

}
