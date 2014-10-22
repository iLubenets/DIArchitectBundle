DIArchitectBundle
=================

Symfony2 bundle for create Dependency Injection charts

## Installation
Install graphviz on your system

### Step 0: Install graphviz on your system
**Ubuntu**
``` bash
sudo apt-get install graphviz
```

### Step 1: Download DIArchitectBundle using composer

Add DIArchitectBundle by running the command:

``` bash
$ php composer.phar require ilubenets/diarchitectbundle "dev-master""
```

Composer will install the bundle to your project's `ilubenets/diarchitectbundle` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new iLubenets\DIArchitectBundle\iLubenetsDIArchitectBundle(),
    );
}
```
### Step 3: Configure the DIArchitectBundle
Add the following configuration to your `config.yml` file according to which type
of datastore you are using.

``` yaml
# app/config/config.yml
il_di_architect:
  path_to_save_graphviz: 'src/doc/DI'
  service_path_list:
    ListOfYourBundles1: 'src/List/OfYourBundles1/Resources/config/services.yml'
    ListOfYourBundles2: 'src/List/OfYourBundles2/Resources/config/services.yml'
    ...
```
    
**routing.yml**
```
il_architect:
    resource: "@iLubenetsDIArchitectBundle/Resources/config/routing.yml"
    prefix: /
```
    
## How to use
``` bash
php app/console di_architect:dump_class_structure --bundle=TestBundle
```

Then can be accessed by url /architect/di/{bundle}
``` bash
* php app/console di_architect:graphviz_generate --bundle=TestBundle --img
```
    
TODO
=================
* Refactoring duplicate code


