# FileManager for Yii2

[![Build Status](https://travis-ci.org/rkit/filemanager-yii2.svg?branch=master)](https://travis-ci.org/rkit/filemanager-yii2)
[![Code Coverage](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/?branch=master)
[![codecov.io](http://codecov.io/github/rkit/filemanager-yii2/coverage.svg?branch=master)](http://codecov.io/github/rkit/filemanager-yii2?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rkit/filemanager-yii2/?branch=master)

## Features

- AJAX upload
- Support multiple file uploads
- Validations of file
- Presets for files
- Create thumbnails on the fly or after upload
- Check the owner of a file
- Storing information about a file in the database
- Ability to use any component for working with files (resize, crop, etc)
- Ability to change the way of storing files

## Introduction

The basic idea is that each file has an owner (model).  
After saving the model is verified that the file (or files) have link to the current model.

## Installation

1. Installing using Composer

   ```
   composer require rkit/filemanager-yii2
   ```

2. Run migrations

   ```
   php yii migrate --migrationPath=@vendor/rkit/filemanager-yii2/src/migrations/ --interactive=0
   ```

## Documentation

[Guide](/guide)  
[API Reference](/docs)

## Development

### Tests

[See docs](/tests/#tests)

### Coding Standard

- PHP Code Sniffer — [phpcs.xml](./phpcs.xml)
- PHP Mess Detector — [ruleset.xml](./ruleset.xml)
