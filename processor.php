<?php
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
require_once('vendor/autoload.php');

// 初期処理
$host = 'http://localhost:4444/wd/hub'; // this is the default
// メモ：start Firefox with 5 second timeout
// chrome ドライバー用の設定を準備
$capabilities = DesiredCapabilities::chrome();
$options = new ChromeOptions();
$options->addArguments(array(
    '--window-size=1200,1200',
));
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
// ドライバーを起動
$driver = RemoteWebDriver::create($host, $capabilities, 5000);

// 画像出力先
$tempPath = 'd:/temp/';
if (!file_exists($tempPath)) {
    mkdir($tempPath, 0777, true);
}

// シナリオファイルを読み込み
$config = file_exists('scenario.yaml') ? spyc_load_file('scenario.yaml') : [];

// navigate to 'http://www.seleniumhq.org/'
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
                saveScreenshot($driver, $tempPath, $operation['fileName']);
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
                saveScreenshot($driver, $tempPath, $operation['fileName']);
            }
        }
    }
}

// close the Firefox
$driver->quit();

/**
 * スクリーンショットを保存
 * @param $driver
 * @param $tempPath
 * @param $fileName
 * @param null $element
 * @return string
 * @throws Exception
 */
function saveScreenshot($driver, $tempPath, $fileName, $element=null) {
    // Change the Path to your own settings
    $screenshot = $tempPath. $fileName."_".time() . ".png";

    // Change the driver instance
    $driver->takeScreenshot($screenshot);
    if(!file_exists($screenshot)) {
        throw new Exception('Could not save screenshot');
    }

    if( ! (bool) $element) {
        return $screenshot;
    }

    $element_screenshot = $tempPath . $fileName."_".time() . ".png"; // Change the path here as well
    $element_width = $element->getSize()->getWidth();
    $element_height = $element->getSize()->getHeight();
    $element_src_x = $element->getLocation()->getX();
    $element_src_y = $element->getLocation()->getY();

    // 画面を保存
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
