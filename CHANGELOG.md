# Imgix Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
