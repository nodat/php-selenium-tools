<?php
//namespace Facebook\WebDriver;

use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
require_once('vendor/autoload.php');

// Get command options
// -f:  set scenario yaml file.
// -o:  set image output path.
// -h:  set selenium server url.
$options = getopt("f:o:h:");

if (!isset($options['f'])) {
    print "No scenario file.\n";
    print "-f:  set scenario yaml file. ";
    exit;
}
$scenarioFile = $options['f'];

if (!isset($options['o'])) {
    print "No image output path.\n";
    print "-o:  set image output path. ";
    exit;
}
$imagePath = $options['o'];

if (!isset($options['h'])) {
    $host = 'http://localhost:4444/wd/hub'; // default
} else {
    $host = $options['h'];
}


// start Firefox with 5 second timeout

// Chrome Config
$capabilities = DesiredCapabilities::chrome();
$options = new ChromeOptions();
$options->addArguments(array(
    '--window-size=1200,1200',
));
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = RemoteWebDriver::create($host, $capabilities, 50000, 50000);

// テストケースを読み込み
if (preg_match("/\.(yaml|yml)$/", $scenarioFile)) {
    $config = file_exists($scenarioFile) ? spyc_load_file($scenarioFile) : [];
} else {
    $config = file_exists($scenarioFile) ? json_decode(load_file($scenarioFile), true) : [];
}

// シナリオを順々に実行する
$url = array_select('url', $config);
$driver->get($url);

print array_select('name', $config)."\n";
$tests = array_select('tests', $config, []);
foreach ($tests as $scenario) {
    print array_select('name', $scenario)."\n";
    $driver->manage()->timeouts()->implicitlyWait(0);

    $commands = array_select('commands', $scenario, []);
    foreach ($commands as $cmd) {
        $element = null;
        // スクリーンショットを保存
        if (isset($cmd['fileName']) && isset($cmd['command']) && $cmd['command'] == 'snapshot') {
            saveScreenshot($driver, $imagePath, $cmd['fileName']);
            continue;
        }

        // ID
        $target = array_select('target', $cmd);
        $elementTarget = substr($target, strpos($target, "=") + 1);
        if (preg_match("/^id=/", $target)) {
            $element = $driver->findElement(WebDriverBy::id($elementTarget));
            // TAG
        } else if (preg_match("/^tag=/", $target)) {
            $element = $driver->findElement(WebDriverBy::tagName($elementTarget));
            // Class
        } else if (preg_match("/^class=/", $target)) {
            $element = $driver->findElement(WebDriverBy::className($elementTarget));
            // XPath
        } else if (preg_match("/^xpath=/", $target)) {
            $element = $driver->findElement(WebDriverBy::xpath($elementTarget));
        }

        // 対象エレメントがなかった場合は抜ける
        if (is_null($element)) {
            continue;
        }

        // 操作
        if (isset($cmd['value']) && isset($cmd['command']) && $cmd['command'] == 'sendKeys') {
            // 入力
            $element->sendKeys(convertValue($cmd['value']));
        } else if (isset($cmd['value']) && isset($cmd['command']) && $cmd['command'] == 'type') {
            // 入力
            $element->sendKeys(convertValue($cmd['value']));
        } else if (isset($cmd['command']) && $cmd['command'] == 'click') {
            // クリック
            $element->click();
//				$driver->manage()->timeouts()->implicitlyWait(10);
            // 画面スクロール系
//            } else if (isset($cmd['id']) && isset($operation['action']) && $operation['action'] == 'move') {
//                $cmd = 'document.getElementById("'.$operation['id'].'").scrollIntoView(true)';
//                print $cmd."\n";
//                $driver->executeScript($cmd);
//            } else if (isset($operation['class']) && isset($operation['action']) && $operation['action'] == 'move') {
//                $cmd = 'document.getElementsByClassName("'.$operation['class'].'")[0].scrollIntoView(true)';
//                print $cmd."\n";
//                $driver->executeScript($cmd);
        }
        // スクリーンショットを残す
        if (isset($cmd['snapshot']) && $cmd['snapshot'] == 'true') {
            saveScreenshot($driver, $imagePath, $cmd['fileName']);
        }
    }
}

// ブラウザを閉じる
$driver->quit();
exit;

/**
 * 要素について保存した場合は、elementを指定する。
 * @param $driver
 * @param $imagePath
 * @param $fileName
 * @param null $element
 * @return string
 * @throws Exception
 */
function saveScreenshot($driver, $imagePath, $fileName, $element=null) {
    // Change the Path to your own settings
    $screenshot = $imagePath. $fileName."_".time() . ".png";

    // Change the driver instance
    $driver->takeScreenshot($screenshot);
    if(!file_exists($screenshot)) {
        throw new Exception('Could not save screenshot');
    }

    if( ! (bool) $element) {
        return $screenshot;
    }

    $element_screenshot = $imagePath . $fileName."_".time() . ".png"; // Change the path here as well

    $element_width = $element->getSize()->getWidth();
    $element_height = $element->getSize()->getHeight();

    $element_src_x = $element->getLocation()->getX();
    $element_src_y = $element->getLocation()->getY();

    // Create image instances
    $src = imagecreatefrompng($screenshot);
    $dest = imagecreatetruecolor($element_width, $element_height);

    // Copy
    imagecopy($dest, $src, 0, 0, $element_src_x, $element_src_y, $element_width, $element_height);

    imagepng($dest, $element_screenshot);

    // unlink($screenshot); // unlink function might be restricted in mac os x.

    if( ! file_exists($element_screenshot)) {
        throw new Exception('Could not save element screenshot');
    }

    return $element_screenshot;
}

/**
 * 値を選択します。
 * @param $key
 * @param $array
 * @param string $default
 * @return string
 */
function array_select($key, $array, $default = ""){
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

/**
 * キー入力値の場合、置き換えます。
 * @param $value
 * @return string
 */
function convertValue($value) {
    if ($value == '${KEY_ENTER}') {
        return WebDriverKeys::ENTER;
    }
    return $value;
}

/**
 * ファイルを読み込みます
 * @param $fileName
 * @return string
 */
function load_file($fileName) {
    $ret = false;
    // ファイル名がない場合にはfalseを返す
    if (!file_exists($fileName)) {
        return $ret;
    }

    $fp = fopen($fileName,'rb');
    while(!feof($fp)){
        $line = fgets($fp, 4096);
        $ret .= $line;
    }
    fclose($fp);
    return $ret;
}