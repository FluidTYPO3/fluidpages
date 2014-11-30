Fluidpages: Fluid Page Templates
================================

> **Fluid Pages** enables page template selection and rendering á la TemplaVoila - but using Fluid templates. The feature was born
> in the extension FED and ported into this extension, making a very light (actually bordering on simple) extension. It uses Flux
> to enable highly dynamic configuration of variables used when rendering the template.

[![Build Status](https://img.shields.io/jenkins/s/https/jenkins.fluidtypo3.org/fluidpages.svg?style=flat-square)](https://jenkins.fluidtypo3.org/job/fluidpages) [![Coverage Status](https://img.shields.io/coveralls/FluidTYPO3/fluidpages/development.svg?style=flat-square)](https://coveralls.io/r/FluidTYPO3/fluidpages)

## What does it do?

EXT:fluidpages enables the use of Fluid templates as page templates, each template file acting like an individual template. The
template files are processed by the extension and a simplified method of selecting templates (two selector boxes, one for current
page and one for subpage templates, like TemplaVoila).

## Why use it?

Although the FLUIDTEMPLATE object in TypoScript allows Fluid templates to be used as page templates, it has limited capabilities.
Using EXT:fluidtemplate the page templates are not only selectable in page properties but also rendered from a proper Controller.

In addition to this, Fluid Pages uses all the power of Flux to allow the template to contain dynamic but very compact configuration
sections - which translate to fields in the page properties, the values of which are available when rendering the template.

When combined with the VHS extension this becomes a very powerful tool: the dynamic nature of Fluid templates and the flexibility
of ViewHelpers - combined with extremely easy-to-use configuration options.

## How does it work?

Fluid Pages are registered through TypoScript. The template files are then processed and the configuration contained in each is
recorded and used to identify the page template when being selected by content editors.

When editing the page a Flux ConfigurationProvider takes care of processing the specific template's configuration and presenting
it as fields available to the content editor much like TemplaVoila.

Page templates work best if they are shipped (and created) in an extension, the key of which is used by identify the page
templates in relation to the Fluid Pages extension. This makes the templates excellently portable and allow you to quickly add
custom ViewHelpers used by your specific page templates. Such an extension need only contain an `ext_emconf.php` file and
optionally a static TypoScript configuration and an `ext_localconf.php` to register that TypoScript static configuration. Using
a static file makes it easy to include the page template.

> Note: You can of course place your template files in fileadmin or another location, but this has disadvantages. The short
> description of these disadvantages: everything you can normally do in Extbase and Fluid when working in an extension, becomes
> impossible - or requires the use of many workarounds / additional attributes just to operate the most basic ViewHelpers.

## References

Other extensions which are either dependencies of or closely related to this extension:

* https://github.com/FluidTYPO3/flux is a dependency and is used to configure how the page template variable are defined.
* https://github.com/FluidTYPO3/vhs is a highly suggested companion for Fluid Pages templates, providing useful ViewHelpers.
* https://github.com/FluidTYPO3/fluidcontent is a suggested companion for sites built with Fluid Pages.
* https://github.com/FluidTYPO3/schemaker is a nice-to-have tool to generate XSD schemas for a great Fluid experience.
