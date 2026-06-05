<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Utils\Files;
use CRM\CivixBundle\Utils\Path;
use PhpArrayDocument\PhpArrayDocument;
use PhpArrayDocument\Printer;
use Symfony\Component\Console\Output\OutputInterface;

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
   * @var string[]
   */
  private $keysToExcludeTs = [];

  /**
   * @var string
   */
  private $extensionUtil;

  private $literals = [];

  private $useCallbacks = [];

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
   * Specify fields (and their children) that will not be wrapped in E::ts()
   *
   * @param array $keysToExclude
   * @return void
   */
  public function excludeTs(array $keysToExclude) {
    $this->keysToExcludeTs = $keysToExclude;
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
   * @param string[] $literals
   * @return void
   */
  public function setLiterals(array $literals) {
    $this->literals = $literals;
  }

  /**
   * Specify which items should be wrapped in an anonymous function
   *
   * @param string[] $callbacks
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
    $doc = PhpArrayDocument::create();

    $ts = 'ts';
    if ($this->extensionUtil) {
      $doc->addUse($this->extensionUtil, 'E');
      $ts = 'E::ts';
    }

    if ($this->header) {
      // FIXME: We should probably be using inner-comments instead of outer-comments.
      $doc->setOuterComments(preg_split('@(?=\n)@', $this->header));
    }

    $doc->getRoot()->importData($this->data);

    $this->applyTranslations($doc->getRoot(), $this->keysToExcludeTs ?: [], $ts);

    $content = (new Printer())->print($doc);
    file_put_contents($this->path, $content);
  }

  /**
   * Recursively processes nodes and sets factory/deferred flags.
   *
   * @param \PhpArrayDocument\BaseNode $node
   * @param array $excludeKeys
   * @param string $ts
   * @param bool $isExcluded
   * @return void
   */
  private function applyTranslations(\PhpArrayDocument\BaseNode $node, array $excludeKeys, string $ts, bool $isExcluded = FALSE) {
    if ($node instanceof \PhpArrayDocument\ArrayNode) {
      foreach ($node->getItems() as $arrayItem) {
        $key = $arrayItem->getKey();
        $itemExcluded = $isExcluded || in_array($key, $excludeKeys, TRUE);
        $value = $arrayItem->getValue();

        if (in_array($key, $this->keysToTranslate ?: [], TRUE) && !$itemExcluded && $value instanceof \PhpArrayDocument\ScalarNode) {
          if ($value->getScalar()) {
            $value->setFactory($ts);
          }
        }
        if (in_array($key, $this->useCallbacks, TRUE)) {
          $value->setDeferred(TRUE);
        }
        if (in_array($key, $this->literals, TRUE)) {
          $value->setFactory('constant');
        }

        $this->applyTranslations($value, $excludeKeys, $ts, $itemExcluded);
      }
    }
  }

}
