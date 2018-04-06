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

* Every scenario needs operation set.
* Operation set contains actions.
* Action has 4 types.
 * snapshot, click, sendKeys, move
* Select target dom by id, tag, class, xpath 

<pre>
- url: https://www.yahoo.co.jp
  pageTitle: ヤフーテスト
  scenario:
   - label: 'step 1. '
     category: 検索テスト
     title: ヤフーに接続して、検索フォームに「大谷翔平」を入力して検索ボタンを押下します。
     expect: 検索結果を正しく表示します。
     operation:
      - action: snapshot
        fileName: '保存ファイル名'
      - id: srchtxt
        action: sendKeys
        text: 大谷翔平
      - action: click
        id: srchbtn
      - action: snapshot
        fileName: '検索後画面'
</pre>