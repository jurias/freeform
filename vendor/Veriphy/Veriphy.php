<?php

$input = $argv[1];

if (is_dir($input))
{
  $files = glob($input . DIRECTORY_SEPARATOR . '*');
}
elseif (is_file($input)) {
  $files = array($input);
}
else
{
  echo "Veriphy\nExample: php path/to/Veriphy.php (file|directory)\n";
  exit();
}

ob_start();
// ini_set('error_reporting', null);

foreach ($files as $file) {
  try {include $file; } catch (Exception $e) {} 
}

$output = ob_get_clean();
$output .= join("\n", Veriphy::$errors);
$output .= "\nPassed: " . Veriphy::$successes;
$output .= "\nFailed: " . count(Veriphy::$errors);
$output = nl2br($output);
if (php_sapi_name() == 'cli')
{
  $output = strip_tags($output);
}
echo $output;
echo "\n";

function test($message, $callback)
{
  $backtrace = array_reverse(debug_backtrace());
  $fn = __FUNCTION__;

  $message = array();
  foreach ($backtrace as $line) {
    if ($line['function'] == __FUNCTION__)
    {
      $message[] = $line['args'][0];
    }
  }
  $message = join(' ', $message);

  try {
    $callback();
    Veriphy::$successes++;
  }
  catch (\Exception $e)
  {
    Veriphy::$errors[] = "\n$message [failed]\n{$e->getMessage()}\n";
  }
}

function verify($input)
{
  return new Tester($input);
}

class Veriphy
{
  public static $successes = 0; 
  public static $errors = array(); 
}

class Tester
{
  public function __construct($input)
  {
    $this->input = $input;
  }
  
  public function is_true()
  {
    $this->input = (bool) $this->input;
    $this->is(true);
  }
  
  public function is_false()
  {
    $this->input = (bool) $this->input;
    $this->is(false);
  }

  public function is_empty()
  {
    $this->input = empty($this->input);
    $this->is(true);
  }
  
  public function is_array()
  {
    $this->input = gettype($this->input);
    $this->is('array');
  }
  
  public function is_object()
  {
    $this->input = gettype($this->input);
    $this->is('object');
  }

  public function is_class($class)
  {
    $this->input = get_class($this->input);
    $this->is($class);
  }

  public function is_greater_than($count)
  {
    $this->test($this->input > $count, "> $count");
  }

  public function is_less_than($count)
  {
    $this->test($this->input < $count, "< $count");
  }

  public function is($expected)
  {
    $this->test($this->input === $expected, $expected);
  }

  public function count()
  {
    return new Tester(count($this->input));
  }

  private function test($condition, $expected)
  {
    if (!$condition)
    {
      echo 'X';
      throw new \Exception(
        "Expected: " . print_r($expected, true) . "\n" .
        "Got: " . print_r($this->input, true)
      );
    }    

    echo '* ';

    return true;
  }
}