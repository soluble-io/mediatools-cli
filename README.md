[![PHP Version](https://img.shields.io/badge/php-7.1+-ff69b4.svg)](https://packagist.org/packages/soluble/mediatools-cli)
[![Build Status](https://travis-ci.org/soluble-io/soluble-mediatools-cli.svg?branch=master)](https://travis-ci.org/soluble-io/soluble-mediatools-cli)
[![codecov](https://codecov.io/gh/soluble-io/soluble-mediatools-cli/branch/master/graph/badge.svg)](https://codecov.io/gh/soluble-io/soluble-mediatools-cli)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/soluble-io/soluble-mediatools-cli/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/soluble-io/soluble-mediatools-cli/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/soluble/mediatools-cli/v/stable.svg)](https://packagist.org/packages/soluble/mediatools-cli)
![PHPStan](https://img.shields.io/badge/style-level%207-brightgreen.svg?style=flat-square&label=phpstan)
[![License](https://poser.pugx.org/soluble/mediatools-cli/license.png)](https://packagist.org/packages/soluble/mediatools)

![Logo](./docs/assets/images/mediatools.png)

**WIP**

Console

## Scanning medias

```
php bin/mediatools-cli.php scan:videos --dir ./tests/data
```

## Converting medias

```
php bin/mediatools-cli.php convert:directory \
  --dir tests/data \
  --preset "Soluble\MediaTools\Preset\Prod\ResolvePreset" \
  --exts mp4,m4v \
  --output /tmp
```

 
