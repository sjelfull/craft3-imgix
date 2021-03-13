# Imgix Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.1.0] - 2021-03-13

> {warning} [Imgix have deprecated their old API](https://blog.imgix.com/2020/10/16/api-deprecation), and you will have to generate a new API key before March 31st if you are using the purge function. The plugin will continue to spuport both the old and the new version, but you will get a deprecation error if you use an old API key, and they will eventually stop working.

### Added
- Added support for the new Imgix Purge API.

### Changed
- Added deprecation warning when using a deprecated API key.

## [2.0.5] - 2020-06-24
### Fixed
- Fixed secure URLs if imgix source is setup with path prefix ([#18](https://github.com/sjelfull/craft3-imgix/pull/18))

## [2.0.4] - 2018-07-21
### Fixed
- Fixed a bug where default options was overriding whatever attributes passed via transforms
- Fixed getUri deprecated error (#4)
- Fixed wrong repo URls

### Changed
- Increased imgix-php dependency to 2.1.0
- Removed RC1 from Craft dependency
- Cleaned up list of translated attributes

### Added
- Added x/y (crop points) to translated attributes
- Added example config to readme

## [2.0.3] - 2018-01-25
### Added
- Added support for signed URLs

## [2.0.2] - 2017-12-08
### Changed
- Changed composer constraint to Craft RC1

## [2.0.1] - 2017-12-04
### Added
- Added cache purging

### Fixed
- Fixed missing class reference
- Fixed getting first imgix domain when passing in url string 

## [2.0.0] - 2017-10-29
### Added
- Initial release
