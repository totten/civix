<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Utils\Files;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Write a data file in PHP format
 */
class PhpData implements Builder {

  const COMMON_LOCALIZBLE = 'title,label,description,text';

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

  private array $literals = [];

  private array $useCallbacks = [];

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
   * Specify which items should be unwrapped and used literally
   *
   * @param string $literals
   * @return void
   */
  public function setLiterals(array $literals) {
    $this->literals = $literals;
  }

  /**
   * Specify which items should be wrapped in an anonymous function
   *
   * @param string $callbacks
   * @return void
   */
  public function setCallbacks(array $callbacks) {
    $this->useCallbacks = $callbacks;
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    $output->writeln("<info>Write</info> " . Files::relativize($this->path));
    Path::for(dirname($this->path))->mkdir();

    $content = "<?php\n";
    if ($this->extensionUtil) {
      $content .= "use $this->extensionUtil as E;\n";
    }
    if ($this->header) {
      $content .= $this->header;
    }
    $content .= "\nreturn ";
    $content .= $this->varExport($this->data);
    $content .= ";\n";
    file_put_contents($this->path, $content);
  }

  private function varExport($values) {
    $output = VarExporter::export($values);
    $output = $this->reduceIndentation($output);
    $output = $this->ucConstants($output);
    if ($this->keysToTranslate) {
      $output = $this->translateStrings($output, $this->keysToTranslate);
    }
    foreach ($this->useCallbacks as $key) {
      $output = str_replace("  '$key' => ", "  '$key' => fn() => ",  $output);
    }
    foreach ($this->literals as $key) {
      $output = preg_replace("/  '$key' => '(.*)',/", "  '$key' => \$1,",  $output);
    }
    return $output;
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
    $ts = ($this->extensionUtil) ? 'E::ts' : 'ts';

    $keys = implode('|', array_unique($keysToTranslate));
    $data = preg_replace("/'($keys)' => ('[^']+'),/", "'\$1' => $ts(\$2),", $data);
    return $data;
  }

}
