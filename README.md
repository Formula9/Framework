[![Code Climate](https://codeclimate.com/github/Formula9/Framework/badges/gpa.svg)](https://codeclimate.com/github/Formula9/Framework)
[![Test Coverage](https://codeclimate.com/github/Formula9/Framework/badges/coverage.svg)](https://codeclimate.com/github/Formula9/Framework/coverage)
# Formula 9 Framework Core Classes

## Introduction

**Formula Nine** is a web framework for PHP. This is a personal project and not intended for general use. Formula Nine Framework 
is built on Silex 2. Silex is build on and around Symfony components - including the Pimple Container. This set of framework 
objects express an evolving design opinion, and therefore should not be considered for use in a production environment.
 
Documentation is currently under development. Drafts can be found (as they arrive) in the [WIKI](https://github.com/Formula9/Framework/wiki).

## Installing 

The framework installs two imported packages: `Formula9/Core` (cloned Silex 2.0.2) and `Formula9/Potion` (cloned Pimple 3.0.8). 

### From the Shell
  
To install use the following:
```shell
composer require formula9/framework
```
  
### In composer.json    

add the following to composer.json
```json    
"formula9/framework" : "dev-master"
```
    
usually with 

```json    
"minimum-stability": "dev",
```    

You may need to do the following:

```shell    
composer install
```

Or you can fork it and fill your boots.

## Coming Soon

* A complete set of tests. (Currently: [![Test Coverage](https://codeclimate.com/github/Formula9/Framework/badges/coverage.svg)](https://codeclimate.com/github/Formula9/Framework/coverage))  
* The example application.

## License

Released under MIT License (MIT)
