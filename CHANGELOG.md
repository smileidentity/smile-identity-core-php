# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.0.0] - 2022-12-06
### Added
- Test against PHP 8.1, 8.0, and 7.4 in github actions.
- Started tracking changes in changelog.

### Changed
- Fix bad signature error
- Update dependency guzzlehttp/psr7
- Update readme

### Removed 
- Travis CI integration tests.
- Removed sec key

## [3.2.0] - 2022-06-23

### Changed
- Changed the return type of submit_job to array
- Deprecate generate_sec_key
- Make generate_signature default
- Changed Signature argument order
- Update readme

### Removed
- Removed unused dependencies

## [3.1.1] - 2022-06-22
### Added
- SDK_CLIENT and VERSION to Config
### Changed
- Change arguments order in get_web_token

### Removed
- Removed DateTimeInterface::ISO8601 due to deprecation
  
## [3.1.0] - 2022-05-18
### Added
- Added use_enrolled_image to options
- Image validation


## [3.0.0] - 2022-02-15
### Added
- Add support for document verification

### Changed
- Update composer config

## [2.0.0] - 2021-10-07
### Added
- Added support for web token
- Added SmileId services call
- Added Utils

### Changed
- Update composer
- Fixed ci issues
- Refactor submit_job method in webapi
- Add Config class
- Add signature
- Add travis

### Removed
- Removed unused parameter $guzzle

## [1.0.0] - 2020-12-25

### Added
- Add composer
- Add id api class

### Updated
- Fixed $sid_idapi.initialize call