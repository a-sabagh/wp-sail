 First you need to replace namespace and text domain with it's new value using find and sed prompt together 
At root of plugin boilerplate run this prompts :
```console
find . -type f -iname '*.php' -not -path './includes/Requirements/vendor/*' -exec sed -i 's/SAIL/#NAMESPACE/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Requirements/vendor/*' -exec sed -i 's/sail/#TEXTDOMAIN/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Requirements/vendor/*' -exec sed -i 's/wp_sail_plugin/#PLUGINNAME/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Requirements/vendor/*' -exec sed -i 's/wp_sail_plugin_init/#GLOBAL/g' {} \;
```
Then change directory to composer path and install necessary libraries with composer
```console
cd includes/Requirements/ && composer install && cd -
```
