First you need to replace namespace and text domain with it's new value using find and sed prompt together 
at root of plugin boilerplate run this prompts :
```console
find . -type f -iname '*.php' -not -path './includes/Packages/composer/*' -exec sed -i 's/WOAP/#NAMESPACE/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Packages/composer/*' -exec sed -i 's/woap/#TEXTDOMAIN/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Packages/composer/*' -exec sed -i 's/woocommerce_application/woocommerce_payment_exchange/g' {} \;
```
```console
find . -type f -iname '*.php' -not -path './includes/Packages/composer/*' -exec sed -i 's/woocommerce_application/#PLUGINNAME/g' {} \;
```
Then change directory to composer path and install necessary libraries with composer
```console
cd includes/Packages/composer/ && composer install 
```
