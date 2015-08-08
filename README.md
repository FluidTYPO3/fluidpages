<img src="https://fluidtypo3.org/logo.svgz" width="100%" />

Fluidpages: Fluid Page Templates
================================

[![Build Status](https://img.shields.io/travis/FluidTYPO3/fluidpages.svg?style=flat-square&label=package)](https://travis-ci.org/FluidTYPO3/fluidpages) [![Coverage Status](https://img.shields.io/coveralls/FluidTYPO3/fluidpages/development.svg?style=flat-square)](https://coveralls.io/r/FluidTYPO3/fluidpages) [![Documentation](http://img.shields.io/badge/documentation-online-blue.svg?style=flat-square)](https://fluidtypo3.org/templating-manual/introduction.html) [![Build Status](https://img.shields.io/travis/FluidTYPO3/fluidtypo3-testing.svg?style=flat-square&label=framework)](https://travis-ci.org/FluidTYPO3/fluidtypo3-testing/) [![Coverage Status](https://img.shields.io/coveralls/FluidTYPO3/fluidtypo3-testing/master.svg?style=flat-square)](https://coveralls.io/r/FluidTYPO3/fluidtypo3-testing)

## What does it do?

EXT:fluidpages enables the use of Fluid templates as page templates, each template file acting like an individual template. The
template files are processed by the extension and a simplified method of selecting templates (two selector boxes, one for current
page and one for subpage templates, like TemplaVoila).

## Why use it?

Although the FLUIDTEMPLATE object in TypoScript allows Fluid templates to be used as page templates, it has limited capabilities.
Using EXT:fluidpages the page templates are not only selectable in page properties but also rendered from a proper Controller.

In addition to this, Fluid Pages uses all the power of Flux to allow the template to contain dynamic but very compact configuration
sections - which translate to fields in the page properties, the values of which are available when rendering the template.

When combined with the VHS extension this becomes a very powerful tool: the dynamic nature of Fluid templates and the flexibility
of ViewHelpers - combined with extremely easy-to-use configuration options.

## How does it work?

Fluid Pages are registered through TypoScript. The template files are then processed and the configuration contained in each is
recorded and used to identify the page template when being selected by content editors.

When editing the page a Flux ConfigurationProvider takes care of processing the specific template's configuration and presenting
it as fields available to the content editor much like TemplaVoila.

View the [online templating manual](https://fluidtypo3.org/documentation/templating-manual/introduction.html) for more information.
