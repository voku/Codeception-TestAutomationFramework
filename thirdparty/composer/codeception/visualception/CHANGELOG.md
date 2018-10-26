# 1.0.1

* Added fullpage screenshot for Chrome and Firefox (new configuration option `fullScreenShot`)
* Fixed using namespaces on Windows
* Fixed compatibility with Codeception 2.4
* Removed duplicity code

# 1.0.0

* **Removed jQuery**; switched to native JavaScript to hide elements
* Configuration of reports changed
* Hardcoded timeouts (`wait(1)`) replaced with corresponding waiter (`waitFor*`) methods
* Added optional parameter `$deviation` to `seeVisualChanges` and `dontSeeVisualChanges`.
* Added `getReferenceImageDir` method to return full path to reference images directory.
* Added new report template `templToJpg.php`, 

# 0.9.0

Released under Codeception organization. Changes:

* *Possible BC* Codeception 2.2+ Compatibility
* *Possible BC* `referenceImageDir` config is now relative to data directory of Codeception (`tests/_data`)
* *Possible BC* `currentImageDir` config is now relative to output directory of Codeception (`tests/_output`)
* *Possible BC*  VisualCeptionReport merged with VisualCeption module and can be enabled with `report: true` config option
