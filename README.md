**Scenario Based Selenium Processor** 

Command options:
* -f:  set scenario yaml file.
* -o:  set image output path.
* -h:  set selenium server url.

Example 1:
<pre>
php processor.php -f scenario.yaml -o .\
</pre>
Example 2:
<pre>
php processor.php -f scenario.yaml -o .\ -h http://localhost:4445/wd/hub
</pre>

**Scenario format**

* Based on Selenium IDE json format.
* Scenario files are written by yaml or json format.
* Not all commands are supported.

<pre>
url: https://www.yahoo.co.jp
name: ヤフーテスト
tests:
 - name: 'step 1. '
   category: 検索テスト
   title: ヤフーに接続して、検索フォームに「大谷翔平」を入力して検索ボタンを押下します。
   expect: 検索結果を正しく表示します。
   commands:
    - command: snapshot
      fileName: '検索前画面'
    - command: open
      target: "/"
      value: ""
    - command: type
      target: id=srchtxt
      value: 大谷翔平
    - command: sendKeys
      target: id=srchtxt
      value: ${KEY_ENTER}
    - action: snapshot
      fileName: '検索後画面'
</pre>