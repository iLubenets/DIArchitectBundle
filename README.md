DIArchitectBundle
=================

Symfony2 bundle for create Dependency Injection charts

Integration
--------
Install graphviz on your system
Ubuntu
  sudo apt-get install graphviz

* config.yml
il_di_architect:
  path_to_save_graphviz: 'src/doc/DI'
  service_path_list:
    IlTestBundle: 'src/Il/Bundle/TestBundle/Resources/config/services.yml'
    
* routing.yml
il_architect:
    resource: "@IlDIArchitectBundle/Resources/config/routing.yml"
    prefix:   /
    
How to use
--------
* php app/console il:architect:dump_class_structure --bundle=TestBundle
Then can be accessed by url /architect/di/{bundle}

* php app/console il:architect:graphviz_generate --bundle=FrameworkBundle --img
    
TODO
=================
* Installation via composer
* Refactoring duplicate code


