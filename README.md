Scenario Based Selenium Processor 

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