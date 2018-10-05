### Codeception Setup

#### 1.0) install Chromium Browser + "ChromeDriver"

  ```bash
  apt-get install chromium-browser chromium-chromedriver
  ```
  
#### 2.0) start "ChromeDriver"

  - https://sites.google.com/a/chromium.org/chromedriver/

  ```bash
  /usr/local/bin/chromedriver --url-base=/wd/hub
  ```
  
#### 2.x) prepare

  ##### check the config
  ```bash
  thirdparty/codecept_php55.phar config:validate
  ```
  
#### 2.1) run tests

  System-Environments (defined in "codeception.yml")
  - testing-foo
  - testing-foo-non-headless
  - testing-bar
  - testing-bar-non-headless
  - testing-lall
  - testing-lall-non-headless
  - local
  - local-headless
  
  #### start the application, first ...
  
  ```bash
  cd src/public/ && php -S localhost:8887 index.php
  ```  

  e.g.:
  

  ##### run all tests (and it also run the "ChromeDriver" in the background)
  
  ```bash
  bin/tests_run.php [--env="local"] [--debug] [--coverage]
  ```

  ##### run all tests local in "headless"-mode

  ```bash
  thirdparty/codecept_php55.phar --env="local-headless" run
  ```

  ##### run all unit-test
  
  ```bash
  thirdparty/codecept_php55.phar --env="local" run unit
  ```
  
  ##### run all acceptance-test in non "headless"-mode

  ```bash
  thirdparty/codecept_php55.phar --env="local" run acceptance
  ```
  
  ##### run one test (and it also run the "ChromeDriver" in the background)
  
  ```bash
  bin/tests_run.php [--env="local"] [--debug] --test="SomethingExamplePageView_Lall_AcceptanceCest[.php]"
  ```

  ##### run one unit-test
  
  ```bash
  thirdparty/codecept_php55.phar --env="local" run unit src/modules/something/example/SomethingExamplePageView_Lall_UnitCest.php
  ```
  
  ##### run one unit-test (with code-coverage)
  
  ```bash
  bin/tests_run.php --env="local" --coverage --test="SomethingExamplePageView_Lall_UnitCest.php"
  ```

  ##### run one acceptance-test

  ```bash
  thirdparty/codecept_php55.phar --env="local" run acceptance src/modules/something/example/SomethingExamplePageView_Lall_AcceptanceCest.php
  ```

------------------------------------------------------------------------------------------------------------

### INFO

.*Cept.php is a scenario-based format and .*Cest.php is a class based format for testing files.

------------------------------------------------------------------------------------------------------------

### LINKS

- [Codeception - WebDriver](http://codeception.com/docs/modules/WebDriver)
- [Headless Chrome in Codeception](http://phptest.club/t/how-to-run-headless-chrome-in-codeception/1544)
