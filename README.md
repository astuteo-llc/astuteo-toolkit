# Astuteo Toolkit plugin for Craft CMS 3.x

Various tools that we use across client sites. Only useful for Astuteo projects

## What it does
Currently, it only does one thing -- provide a way to read in our manifest.json file to cache bust assets. This isn't even configurable yet and expects the json file to reside in /site-assets/

## Usage 
{{ '/site-assets/css/global.css' | astuteoRev }}

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require astuteo-llc/astuteo-toolkit

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Astuteo Toolkit.

## Astuteo Toolkit Roadmap

Some things to do, and ideas for potential features:

* Add path configuration
* Add our logo area calculator 

Brought to you by [Astuteo](https://astuteo.com)
