First you need to replace namespace and text domain with it's new value using find and sed prompt together 
```console
find . -type f -iname '*.php' -not -path './includes/Packages/composer/*' -exec sed -i 's/WOAP/#NAMESPACE/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Packages/composer/*' -exec sed -i 's/woap/#TEXTDOMAIN/g' {} \;
```
Then change directory to composer path and install necessary libraries with composer
```console
cd includes/Packages/composer/ && composer install 
```
