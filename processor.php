<?php
//namespace Facebook\WebDriver;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
require_once('vendor/autoload.php');

// Get command options
// -f:  set scenario yaml file.
// -o:  set image output path.
// -h:  set selenium server url.
$options = getopt("f:o:h::");

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
$driver = RemoteWebDriver::create($host, $capabilities, 5000);

// シナリオを読み込み
$config = file_exists($scenarioFile) ? spyc_load_file($scenarioFile) : [];

// シナリオを順々に実行する
foreach ($config as $key => $units) {
    $driver->get($units['url']);

    print $units['pageTitle']."\n";
    foreach ($units['scenario'] as $scenario) {
        print $scenario['label']."\n";

        $driver->manage()->timeouts()->implicitlyWait(0);

        foreach ($scenario['operation'] as $operation) {
            $element = null;
            // スクリーンショットを保存
            if (isset($operation['fileName']) && isset($operation['action']) && $operation['action'] == 'snapshot') {
                saveScreenshot($driver, $imagePath, $operation['fileName']);
                continue;
            }

            // ID
            if (isset($operation['id'])) {
                $element = $driver->findElement(WebDriverBy::id($operation['id']));
                // TAG
            } else if (isset($operation['tag'])) {
                $element = $driver->findElement(WebDriverBy::tagName($operation['tag']));
                // Class
            } else if (isset($operation['class'])) {
                $element = $driver->findElement(WebDriverBy::className($operation['class']));
                // XPath
            } else if (isset($operation['xpath'])) {
                $element = $driver->findElement(WebDriverBy::xpath($operation['xpath']));
            }

            // 対象エレメントがなかった場合は抜ける
            if (is_null($element)) {
                continue;
            }

            // 操作
            // 入力
            if (isset($operation['text']) && isset($operation['action']) && $operation['action'] == 'sendKeys') {
                $element->sendKeys($operation['text']);
                // クリック
            } else if (isset($operation['action']) && $operation['action'] == 'click') {
                $element->click();
//				$driver->manage()->timeouts()->implicitlyWait(10);
                // 画面スクロール系
            } else if (isset($operation['id']) && isset($operation['action']) && $operation['action'] == 'move') {
                $cmd = 'document.getElementById("'.$operation['id'].'").scrollIntoView(true)';
                print $cmd."\n";
                $driver->executeScript($cmd);
            } else if (isset($operation['class']) && isset($operation['action']) && $operation['action'] == 'move') {
                $cmd = 'document.getElementsByClassName("'.$operation['class'].'")[0].scrollIntoView(true)';
                print $cmd."\n";
                $driver->executeScript($cmd);
            }
            // スクリーンショットを残す
            if (isset($operation['snapshot']) && $operation['snapshot'] == 'true') {
                saveScreenshot($driver, $imagePath, $operation['fileName']);
            }
        }
    }
}

// ブラウザを閉じる
$driver->quit();
exit;


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
